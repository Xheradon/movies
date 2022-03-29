<?php

namespace App\Controller\Admin;

use App\Entity\Director;

class DirectorCrudController extends AbstractPersonCrudController
{
    public static function getEntityFqcn(): string
    {
        return Director::class;
    }
}
