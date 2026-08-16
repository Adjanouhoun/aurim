<?php

namespace App\Controller;

use App\Entity\CustomerOrder;
use App\Entity\Inventory;
use App\Entity\OrderItem;
use App\Entity\Payment;
use App\Entity\PaymentMethod;
use App\Entity\ShippingRate;
use App\Entity\Warehouse;
use App\Notification\OrderMailer;
use App\Inventory\StockMovementRecorder;
use App\Store\Cart;
use App\Store\StoreContext;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CheckoutController extends AbstractController
{
    #[Route('/commande', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request, Cart $cart, StoreContext $context, EntityManagerInterface $entityManager, OrderMailer $orderMailer, StockMovementRecorder $movementRecorder): Response
    {
        $market = $context->getSelectedMarket();
        $lines = $cart->getLines();
        $subtotal = $cart->getTotal();
        if (null === $market || [] === $lines || null === $subtotal) {
            $this->addFlash('checkout_error', 'checkout.error.country_price');
            return $this->redirectToRoute('app_cart');
        }
        if ($request->isMethod('GET') && !$cart->hasSufficientStock()) {
            $this->addFlash('cart_error', 'checkout.error.stock_changed');
            return $this->redirectToRoute('app_cart');
        }

        $shippingRates = $entityManager->getRepository(ShippingRate::class)->findBy(['market' => $market, 'active' => true], ['city' => 'ASC']);
        $paymentMethods = array_values(array_filter(
            $entityManager->getRepository(PaymentMethod::class)->findBy(['market' => $market, 'active' => true], ['name' => 'ASC']),
            static fn (PaymentMethod $method): bool => $method->isReadyForCheckout(),
        ));
        $errors = [];
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('checkout', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }
            $rate = $entityManager->getRepository(ShippingRate::class)->findOneBy(['id' => $request->request->getInt('shipping_rate'), 'market' => $market, 'active' => true]);
            $paymentMethod = $entityManager->getRepository(PaymentMethod::class)->findOneBy(['id' => $request->request->getInt('payment_method'), 'market' => $market, 'active' => true]);
            if ($paymentMethod instanceof PaymentMethod && !$paymentMethod->isReadyForCheckout()) {
                $paymentMethod = null;
            }
            $name = trim((string) $request->request->get('name'));
            $email = trim((string) $request->request->get('email'));
            $phone = trim((string) $request->request->get('phone'));
            $address = trim((string) $request->request->get('address'));
            if (!$rate instanceof ShippingRate) { $errors[] = 'checkout.error.fulfillment'; }
            if (!$paymentMethod instanceof PaymentMethod) { $errors[] = 'checkout.error.payment'; }
            if ($rate instanceof ShippingRate && $paymentMethod instanceof PaymentMethod && !$paymentMethod->supportsFulfillment($rate->getFulfillmentType())) { $errors[] = 'checkout.error.payment_incompatible'; }
            if (mb_strlen($name) < 3) { $errors[] = 'checkout.error.name'; }
            if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'checkout.error.email'; }
            if (mb_strlen($phone) < 7) { $errors[] = 'checkout.error.phone'; }
            if (mb_strlen($address) < 8) { $errors[] = 'checkout.error.address'; }

            $warehouse = $entityManager->getRepository(Warehouse::class)->findOneBy(['market' => $market, 'active' => true, 'central' => false]);
            if (!$warehouse instanceof Warehouse) { $errors[] = 'checkout.error.warehouse'; }
            if ($warehouse instanceof Warehouse) {
                foreach ($lines as $line) {
                    $inventory = $entityManager->getRepository(Inventory::class)->findOneBy(['product' => $line['product'], 'warehouse' => $warehouse]);
                    if (!$inventory instanceof Inventory || $inventory->getAvailableQuantity() < $line['quantity']) {
                        $errors[] = 'checkout.error.product_stock';
                    }
                }
            }

            if ([] === $errors && $rate instanceof ShippingRate && $warehouse instanceof Warehouse && $paymentMethod instanceof PaymentMethod) {
                $connection = $entityManager->getConnection();
                $order = null;
                $payment = null;
                $connection->beginTransaction();
                try {
                    /** @var array<int, Inventory> $lockedInventories */
                    $lockedInventories = [];
                    foreach ($lines as $line) {
                        $inventory = $entityManager->getRepository(Inventory::class)->findOneBy(['product' => $line['product'], 'warehouse' => $warehouse]);
                        if ($inventory instanceof Inventory) {
                            // Recharge la valeur sous verrou afin que deux commandes simultanées
                            // ne puissent pas réserver les mêmes dernières unités.
                            $entityManager->refresh($inventory, LockMode::PESSIMISTIC_WRITE);
                        }
                        if (!$inventory instanceof Inventory || $inventory->getAvailableQuantity() < $line['quantity']) {
                            $errors[] = 'checkout.error.concurrent_stock';
                            continue;
                        }
                        $lockedInventories[(int) $line['product']->getId()] = $inventory;
                    }

                    if ([] !== $errors) {
                        $connection->rollBack();
                    } else {
                        $order = (new CustomerOrder())
                            ->setReference('AUR-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(6))))
                            ->setMarket($market)->setLocale($request->getLocale())->setCustomerName($name)->setEmail($email)->setPhone($phone)
                            ->setAddressLine($address)->setCity($rate->getCity())
                            ->setFulfillmentType($rate->getFulfillmentType())->setFulfillmentLabel($rate->getLocalizedLabel($request->getLocale()))->setFulfillmentAddress($rate->getAddressLine())
                            ->setPaymentMethodName($paymentMethod->getLocalizedName($request->getLocale()))->setPaymentMethodType($paymentMethod->getType())
                            ->setStatus('cash' === $paymentMethod->getType() ? 'preparing' : 'pending_payment')
                            ->setCurrencyCode($market->getCurrencyCode())
                            ->setSubtotalMinor($subtotal)->setShippingMinor($rate->getAmountMinor())->setTotalMinor($subtotal + $rate->getAmountMinor());
                        foreach ($lines as $line) {
                            $unitPrice = $line['price']?->getAmountMinor();
                            if (null === $unitPrice) { continue; }
                            $order->addItem((new OrderItem())->setProduct($line['product'])->setProductName($line['product']->getLocalizedName($request->getLocale()))->setUnitPriceMinor($unitPrice)->setQuantity($line['quantity'])->setTotalMinor($unitPrice * $line['quantity']));
                            $inventory = $lockedInventories[(int) $line['product']->getId()];
                            $inventory->setQuantityReserved($inventory->getQuantityReserved() + $line['quantity']);
                            $movementRecorder->record(
                                $inventory,
                                'order_reserved',
                                0,
                                $line['quantity'],
                                'Réservation automatique lors de la création de la commande.',
                                $order->getReference(),
                            );
                        }
                        $entityManager->persist($order);
                        $payment = (new Payment())->setCustomerOrder($order)->setMethod($paymentMethod)->setStatus('pending')->setAmountMinor($order->getTotalMinor())->setCurrencyCode($market->getCurrencyCode())->setPayerPhone($phone);
                        $entityManager->persist($payment);
                        $entityManager->flush();
                        $connection->commit();
                    }
                } catch (\Throwable $exception) {
                    if ($connection->isTransactionActive()) {
                        $connection->rollBack();
                    }
                    throw $exception;
                }

                if ($order instanceof CustomerOrder && $payment instanceof Payment) {
                    $orderMailer->sendOrderCreated($order, $payment);
                    $cart->clear();

                    return $this->redirectToRoute('app_order_confirmation', ['reference' => $order->getReference()]);
                }
            }
        }

        return $this->render('checkout/index.html.twig', ['lines' => $lines, 'subtotal' => $subtotal, 'market' => $market, 'shippingRates' => $shippingRates, 'paymentMethods' => $paymentMethods, 'errors' => $errors]);
    }

    #[Route('/commande/confirmation/{reference}', name: 'app_order_confirmation', methods: ['GET'])]
    public function confirmation(string $reference, EntityManagerInterface $entityManager): Response
    {
        $order = $entityManager->getRepository(CustomerOrder::class)->findOneBy(['reference' => $reference]);
        if (!$order instanceof CustomerOrder) { throw $this->createNotFoundException(); }
        $payment = $entityManager->getRepository(Payment::class)->findOneBy(['customerOrder' => $order]);
        return $this->render('checkout/confirmation.html.twig', ['order' => $order, 'payment' => $payment]);
    }
}
