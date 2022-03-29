<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="directors")
 */
class Director extends AbstractPerson
{
    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Film", mappedBy="directors")
     */
    protected Collection $films;
}
