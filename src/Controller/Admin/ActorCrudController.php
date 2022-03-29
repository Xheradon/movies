<?php

namespace App\Controller\Admin;

use App\Entity\Actor;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ActorCrudController extends AbstractPersonCrudController
{
    public static function getEntityFqcn(): string
    {
        return Actor::class;
    }


    public function configureFields(string $pageName): iterable
    {
        $baseFields = $this->configureBaseFields($pageName);
        $baseFields[] = TextField::new('birthplace');
        $baseFields[] = DateField::new('deathDate');
        return $baseFields;
    }

}
