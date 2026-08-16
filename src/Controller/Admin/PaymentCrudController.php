<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Doctrine\ORM\EntityManagerInterface;
use App\Order\OrderStockManager;
use App\Notification\OrderMailer;

final class PaymentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly OrderStockManager $stockManager,
        private readonly OrderMailer $orderMailer,
    ) {}
    public static function getEntityFqcn(): string { return Payment::class; }
    public function configureCrud(Crud $crud): Crud { return $crud->setEntityLabelInSingular('Paiement')->setEntityLabelInPlural('Paiements')->setDefaultSort(['createdAt' => 'DESC']); }
    public function configureActions(Actions $actions): Actions
    {
        $process = Action::new('processOrder', 'Traiter la commande', 'fa fa-list-check')
            ->linkToRoute('admin_order_workflow', static fn (Payment $payment): array => ['id' => $payment->getCustomerOrder()->getId()]);

        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::EDIT)
            ->add(Crud::PAGE_INDEX, $process)
            ->add(Crud::PAGE_DETAIL, $process);
    }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('customerOrder', 'Commande')->setFormTypeOption('disabled', true);
        yield AssociationField::new('method', 'Moyen')->setFormTypeOption('disabled', true);
        yield ChoiceField::new('status', 'Statut')->setChoices(['En attente' => 'pending', 'Reçu' => 'received', 'Échoué' => 'failed', 'Remboursé' => 'refunded']);
        yield IntegerField::new('amountMinor', 'Montant')->setFormTypeOption('disabled', true);
        yield TextField::new('currencyCode', 'Devise')->setFormTypeOption('disabled', true);
        yield TextField::new('externalReference', 'Référence opérateur')->hideOnIndex();
        yield TextField::new('payerPhone', 'Téléphone payeur')->setFormTypeOption('disabled', true);
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(['En attente' => 'pending', 'Reçu' => 'received', 'Échoué' => 'failed', 'Remboursé' => 'refunded']))
            ->add(EntityFilter::new('method', 'Moyen de paiement'))
            ->add(DateTimeFilter::new('createdAt', 'Date de création'));
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $previousStatus = $entityInstance instanceof Payment
            ? ($entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance)['status'] ?? null)
            : null;
        if ($entityInstance instanceof Payment) {
            $order = $entityInstance->getCustomerOrder();
            $order->setStatus(match ($entityInstance->getStatus()) {
                'received' => in_array($order->getStatus(), ['preparing', 'shipped', 'delivered'], true) ? $order->getStatus() : 'preparing',
                'refunded' => 'refunded',
                'failed' => 'payment_failed',
                default => 'pending_payment',
            });
            $this->stockManager->synchronize($order);
        }
        parent::updateEntity($entityManager, $entityInstance);
        if ($entityInstance instanceof Payment
            && 'received' === $entityInstance->getStatus()
            && 'received' !== $previousStatus
            && 'mobile_money_manual' === $entityInstance->getMethod()->getType()
        ) {
            $this->orderMailer->sendPaymentReceived($entityInstance);
        }
        if ($entityInstance instanceof Payment && 'failed' === $entityInstance->getStatus() && 'failed' !== $previousStatus) {
            $this->orderMailer->sendStatusChanged($entityInstance->getCustomerOrder(), 'payment_failed');
        }
        if ($entityInstance instanceof Payment && 'refunded' === $entityInstance->getStatus() && 'refunded' !== $previousStatus) {
            $this->orderMailer->sendStatusChanged($entityInstance->getCustomerOrder(), 'refunded');
        }
    }
}
