<?php

namespace App\Controller\Admin;

use App\Entity\MarketPrice;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class MarketPriceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return MarketPrice::class; }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular('Prix local')->setEntityLabelInPlural('Prix par marché');
    }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('product', 'Produit');
        yield AssociationField::new('market', 'Marché');
        yield IntegerField::new('amountMinor', 'Montant en unité minimale')->setHelp('Exemple : 25000 pour 25 000 XOF. Laissez vide tant que le prix n’est pas validé.');
        yield BooleanField::new('published', 'Prix publié');
    }
}
