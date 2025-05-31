<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
class Profile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $ofUser = null;

    /**
     * @var Collection<int, Proverbe>
     */
    #[ORM\OneToMany(targetEntity: Proverbe::class, mappedBy: 'auhtor', orphanRemoval: true)]
    private Collection $proverbes;

    public function __construct()
    {
        $this->proverbes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOfUser(): ?User
    {
        return $this->ofUser;
    }

    public function setOfUser(User $ofUser): static
    {
        $this->ofUser = $ofUser;

        return $this;
    }

    /**
     * @return Collection<int, Proverbe>
     */
    public function getProverbes(): Collection
    {
        return $this->proverbes;
    }

    public function addProverbe(Proverbe $proverbe): static
    {
        if (!$this->proverbes->contains($proverbe)) {
            $this->proverbes->add($proverbe);
            $proverbe->setAuhtor($this);
        }

        return $this;
    }

    public function removeProverbe(Proverbe $proverbe): static
    {
        if ($this->proverbes->removeElement($proverbe)) {
            // set the owning side to null (unless already changed)
            if ($proverbe->getAuhtor() === $this) {
                $proverbe->setAuhtor(null);
            }
        }

        return $this;
    }
}
