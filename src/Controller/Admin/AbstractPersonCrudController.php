<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AbstractPerson;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

abstract class AbstractPersonCrudController extends AbstractCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('edit', fn(AbstractPerson $person) => sprintf('Editing %s [%s]', $person->getFullName(), $person->getId()))
            ->setSearchFields(['fullName', 'films.title', 'birthday']);
    }

    public function configureFields(string $pageName): iterable
    {
        return $this->configureBaseFields($pageName);
    }

    protected function configureBaseFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnDetail(),
            TextField::new('fullName'),
            AssociationField::new('films')
                ->autocomplete()
                ->setFormTypeOptionIfNotSet('by_reference', false)
            ,
            DateField::new('birthday'),
            HiddenField::new('version')
                ->onlyOnForms()
        ];
    }
}
