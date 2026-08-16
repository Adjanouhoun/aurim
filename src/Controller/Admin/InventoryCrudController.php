<?php

namespace App\Controller\Admin;

use App\Entity\Inventory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

final class InventoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Inventory::class; }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Stock')
            ->setEntityLabelInPlural('Stocks par entrepôt')
            ->setDefaultSort(['warehouse' => 'ASC', 'product' => 'ASC']);
    }
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('warehouse', 'Entrepôt'))
            ->add(EntityFilter::new('product', 'Produit'));
    }
    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('product', 'Produit');
        yield AssociationField::new('warehouse', 'Entrepôt / marché');
        yield IntegerField::new('quantityOnHand', 'Quantité physique');
        yield IntegerField::new('quantityReserved', 'Quantité réservée');
        yield IntegerField::new('availableQuantity', 'Disponible')->onlyOnIndex();
        yield IntegerField::new('lowStockThreshold', 'Seuil d’alerte');
    }
}
