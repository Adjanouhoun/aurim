<?php

namespace App\Controller\Admin;

use App\Entity\PaymentMethod;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class PaymentMethodCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return PaymentMethod::class; }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('market', 'Marché');
        yield TextField::new('name', 'Nom affiché');
        yield TextField::new('code', 'Code interne')->setHelp('Exemple : especes_dakar ou orange_money_sn.');
        yield ChoiceField::new('type', 'Type')->setChoices(['Mobile Money manuel' => 'mobile_money_manual', 'Espèces' => 'cash']);
        yield ChoiceField::new('fulfillmentScope', 'Compatible avec')->setChoices(['Retrait et livraison' => 'both', 'Retrait uniquement' => 'pickup', 'Livraison uniquement' => 'delivery']);
        yield TextareaField::new('instructions', 'Instructions client')->hideOnIndex();
        yield TextField::new('recipientAccount', 'Numéro bénéficiaire')
            ->setHelp('Obligatoire pour activer un paiement Mobile Money. Le client enverra réellement son paiement à ce numéro.');
        yield TextField::new('accountHolder', 'Titulaire du compte')->hideOnIndex();
        yield BooleanField::new('readyForCheckout', 'Prêt pour les clients')->onlyOnIndex();
        yield BooleanField::new('active', 'Actif')
            ->setHelp('Un paiement Mobile Money sans numéro bénéficiaire sera automatiquement désactivé.');
    }

    public function persistEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if ($entityInstance instanceof PaymentMethod) {
            $this->secureConfiguration($entityInstance);
        }
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if ($entityInstance instanceof PaymentMethod) {
            $this->secureConfiguration($entityInstance);
        }
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function secureConfiguration(PaymentMethod $method): void
    {
        if ('mobile_money_manual' === $method->getType() && !$method->getRecipientAccount()) {
            $method->setActive(false);
        }
    }
}
