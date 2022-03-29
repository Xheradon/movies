<?php

namespace App\Controller\Admin;

use App\Command\ImportMoviesCsvCommand;
use App\Entity\Film;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FilmCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Film::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('edit', fn(Film $film) => sprintf('Editing %s [%s]', $film->getTitle(), $film->getId()))
            ->setSearchFields(['title', 'genres', 'publicationDate']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnDetail(),
            TextField::new('title'),
            DateField::new('publicationDate'),
            ArrayField::new('genres'),
            AssociationField::new('directors')
                ->autocomplete(),
            AssociationField::new('actors')
                ->autocomplete(),
            IntegerField::new('duration'),
            TextField::new('producer'),
            ChoiceField::new('importSource')
                ->setChoices([
                    'IMDB' => ImportMoviesCsvCommand::IMPORT_SOURCE
                ]),
            TextField::new('importId')
                ->hideOnIndex(),
            HiddenField::new('version')
                ->onlyOnForms()
        ];
    }
}
