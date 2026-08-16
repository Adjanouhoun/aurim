<?php

namespace App\Controller\Admin;

use App\Entity\Market;
use App\Entity\PaymentMethod;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PaymentMethodsByMarketController extends AbstractController
{
    #[AdminRoute('/paiements-par-pays', name: 'payment_methods_by_market', options: ['methods' => ['GET', 'POST']])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $markets = $entityManager->createQueryBuilder()
            ->select('market')
            ->from(Market::class, 'market')
            ->andWhere('market.countryCode != :internalMarket')
            ->setParameter('internalMarket', 'US')
            ->addOrderBy("CASE WHEN market.countryCode = 'MR' THEN 1 WHEN market.countryCode = 'SN' THEN 2 WHEN market.countryCode = 'ML' THEN 3 WHEN market.countryCode = 'GN' THEN 4 ELSE 5 END", 'ASC')
            ->getQuery()
            ->getResult();

        $selectedMarket = $this->selectMarket($markets, strtoupper(trim((string) $request->query->get('pays'))));
        $methods = $selectedMarket instanceof Market
            ? $entityManager->getRepository(PaymentMethod::class)->findBy(['market' => $selectedMarket], ['type' => 'DESC', 'name' => 'ASC'])
            : [];

        if ($request->isMethod('POST') && $selectedMarket instanceof Market) {
            return $this->save($request, $entityManager, $selectedMarket, $methods);
        }

        $configuredMobileCount = count(array_filter(
            $methods,
            static fn (PaymentMethod $method): bool => 'mobile_money_manual' === $method->getType() && $method->isReadyForCheckout(),
        ));
        $mobileCount = count(array_filter(
            $methods,
            static fn (PaymentMethod $method): bool => 'mobile_money_manual' === $method->getType(),
        ));

        return $this->render('admin/payment_method/by_market.html.twig', [
            'markets' => $markets,
            'selectedMarket' => $selectedMarket,
            'methods' => $methods,
            'configuredMobileCount' => $configuredMobileCount,
            'mobileCount' => $mobileCount,
        ]);
    }

    /**
     * @param list<Market> $markets
     */
    private function selectMarket(array $markets, string $requestedCode): ?Market
    {
        foreach ($markets as $market) {
            if ($market->getCountryCode() === $requestedCode) {
                return $market;
            }
        }
        foreach ($markets as $market) {
            if ($market->isActive()) {
                return $market;
            }
        }

        return $markets[0] ?? null;
    }

    /**
     * @param list<PaymentMethod> $methods
     */
    private function save(
        Request $request,
        EntityManagerInterface $entityManager,
        Market $market,
        array $methods,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('payment-methods-market-'.$market->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Le formulaire a expiré. Rechargez la page et réessayez.');
        }

        $accounts = $request->request->all('accounts');
        $holders = $request->request->all('holders');
        $activeMethods = $request->request->all('active');
        $missingAccountCount = 0;

        foreach ($methods as $method) {
            $id = (string) $method->getId();
            $requestedActive = isset($activeMethods[$id]);
            if ('mobile_money_manual' === $method->getType()) {
                $account = trim((string) ($accounts[$id] ?? ''));
                $holder = trim((string) ($holders[$id] ?? ''));
                if (mb_strlen($account) > 80 || mb_strlen($holder) > 160) {
                    $this->addFlash('danger', sprintf('Les informations de « %s » sont trop longues.', $method->getName()));

                    return $this->redirectToRoute('admin_payment_methods_by_market', ['pays' => $market->getCountryCode()]);
                }
                $method
                    ->setRecipientAccount('' === $account ? null : $account)
                    ->setAccountHolder('' === $holder ? null : $holder)
                    ->setActive($requestedActive && '' !== $account);
                if ($requestedActive && '' === $account) {
                    ++$missingAccountCount;
                }
            } else {
                $method->setActive($requestedActive);
            }
            $entityManager->persist($method);
        }

        $entityManager->flush();
        if ($missingAccountCount > 0) {
            $this->addFlash('warning', 'Les opérateurs sans numéro bénéficiaire sont restés désactivés.');
        }
        $this->addFlash('success', sprintf('Les paiements du marché %s ont été enregistrés.', $market->getName()));

        return $this->redirectToRoute('admin_payment_methods_by_market', ['pays' => $market->getCountryCode()]);
    }
}
