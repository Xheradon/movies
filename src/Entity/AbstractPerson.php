<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

abstract class AbstractPerson
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid", unique=true, nullable=false)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected Uuid $id;
    /**
     * @ORM\Column(name="full_name", type="string", unique=true, nullable=false)
     */
    protected string $fullName;
    /**
     * @ORM\Column(name="birthday", type="date", nullable=true)
     */
    protected ?DateTimeInterface $birthday = null;
    protected Collection $films;
    /**
     * @ORM\Version
     * @ORM\Column(name="version", type="integer")
     */
    protected int $version;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->films = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getBirthday(): ?DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;
        return $this;
    }

    public function getFilms(): ArrayCollection|Collection
    {
        return $this->films;
    }

    public function setFilms(ArrayCollection|Collection $films): self
    {
        $this->films = $films;
        return $this;
    }

    public function addFilm(Film $film): self
    {
        if (!$this->films->contains($film)) {
            $this->films->add($film);
        }

        return $this;
    }

    public function removeFilm(Film $film): self
    {
        if ($this->films->contains($film)) {
            $this->films->removeElement($film);
        }

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf("%s", $this->getFullName());
    }
}
