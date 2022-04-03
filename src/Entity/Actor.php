<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="actors")
 */
class Actor extends AbstractPerson
{
    /**
     * @ORM\Column(name="death_date", type="date", nullable=true)
     */
    protected ?DateTimeInterface $deathDate = null;
    /**
     * @ORM\Column(name="birthplace", type="string", nullable=true)
     */
    protected ?string $birthplace = null;
    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Film", mappedBy="actors")
     */
    protected Collection $films;

    public function addFilm(Film $film): self
    {
        if (!$this->films->contains($film)) {
            $this->films->add($film);
            $film->addActor($this);
        }

        return $this;
    }

    public function getDeathDate(): ?DateTimeInterface
    {
        return $this->deathDate;
    }

    public function setDeathDate(?DateTimeInterface $deathDate): self
    {
        $this->deathDate = $deathDate;
        return $this;
    }

    public function getBirthplace(): ?string
    {
        return $this->birthplace;
    }

    public function setBirthplace(?string $birthplace): self
    {
        $this->birthplace = $birthplace;
        return $this;
    }
}
