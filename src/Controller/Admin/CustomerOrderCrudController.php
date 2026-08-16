<?php

namespace App\Controller\Admin;

use App\Entity\CustomerOrder;
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
use App\Order\OrderStockManager;
use App\Notification\OrderMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class CustomerOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return CustomerOrder::class; }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['market.name' => 'ASC', 'createdAt' => 'DESC']);
    }
    public function configureActions(Actions $actions): Actions
    {
        $process = Action::new('process', 'Traiter', 'fa fa-list-check')
            ->linkToRoute('admin_order_workflow', static fn (CustomerOrder $order): array => ['id' => $order->getId()]);

        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::EDIT)
            ->add(Crud::PAGE_INDEX, $process)
            ->add(Crud::PAGE_DETAIL, $process);
    }
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(['En attente de paiement' => 'pending_payment', 'En préparation' => 'preparing', 'Expédiée / prête au retrait' => 'shipped', 'Livrée / remise' => 'delivered', 'Livraison échouée' => 'delivery_failed', 'Paiement échoué / annulée' => 'payment_failed', 'Annulée' => 'cancelled', 'Remboursée' => 'refunded']))
            ->add(EntityFilter::new('market', 'Marché'))
            ->add(ChoiceFilter::new('fulfillmentType', 'Réception')->setChoices(['Livraison' => 'delivery', 'Retrait dépôt' => 'pickup']))
            ->add(DateTimeFilter::new('createdAt', 'Date de création'));
    }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('market', 'Marché')->setFormTypeOption('disabled', true);
        yield TextField::new('reference', 'Référence')->setFormTypeOption('disabled', true);
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield TextField::new('customerName', 'Client')->setFormTypeOption('disabled', true);
        yield TextField::new('email', 'Adresse e-mail')->setFormTypeOption('disabled', true)->hideOnIndex();
        yield TextField::new('phone', 'Téléphone')->setFormTypeOption('disabled', true);
        yield TextField::new('addressLine', 'Adresse du client')->setFormTypeOption('disabled', true)->hideOnIndex();
        yield TextField::new('city', 'Ville')->setFormTypeOption('disabled', true);
        yield ChoiceField::new('fulfillmentType', 'Réception')->setChoices(['Livraison' => 'delivery', 'Retrait dépôt' => 'pickup'])->setFormTypeOption('disabled', true);
        yield TextField::new('fulfillmentLabel', 'Option choisie')->setFormTypeOption('disabled', true)->hideOnIndex();
        yield TextField::new('fulfillmentAddress', 'Adresse du dépôt')->setFormTypeOption('disabled', true)->hideOnIndex();
        yield TextField::new('paymentMethodName', 'Paiement')->setFormTypeOption('disabled', true);
        yield ChoiceField::new('status', 'Statut')->setChoices(['En attente de paiement' => 'pending_payment', 'En préparation' => 'preparing', 'Expédiée / prête au retrait' => 'shipped', 'Livrée / remise' => 'delivered', 'Livraison échouée' => 'delivery_failed', 'Paiement échoué / annulée' => 'payment_failed', 'Remboursée' => 'refunded', 'Annulée' => 'cancelled'])->renderAsBadges()->setFormTypeOption('disabled', true);
        yield ChoiceField::new('inventoryStatus', 'État du stock')->setChoices(['Réservé' => 'reserved', 'Déduit' => 'committed', 'Libéré' => 'released'])->setFormTypeOption('disabled', true);
        yield IntegerField::new('totalMinor', 'Total')->setFormTypeOption('disabled', true);
        yield TextField::new('currencyCode', 'Devise')->setFormTypeOption('disabled', true);
    }

    public function __construct(
        private readonly OrderStockManager $stockManager,
        private readonly OrderMailer $orderMailer,
    ) {}

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $previousStatus = $entityInstance instanceof CustomerOrder
            ? ($entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance)['status'] ?? null)
            : null;
        if ($entityInstance instanceof CustomerOrder) {
            $this->stockManager->synchronize($entityInstance);
        }
        parent::updateEntity($entityManager, $entityInstance);
        if ($entityInstance instanceof CustomerOrder && $previousStatus !== $entityInstance->getStatus()) {
            $this->orderMailer->sendStatusChanged($entityInstance, $entityInstance->getStatus());
        }
    }
}
