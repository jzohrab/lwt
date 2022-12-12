<?php

namespace App\Entity;

use App\Repository\TermRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TermRepository::class)]
class Term
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 250)]
    private ?string $WoText = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWoText(): ?string
    {
        return $this->WoText;
    }

    public function setWoText(string $WoText): self
    {
        $this->WoText = $WoText;

        return $this;
    }
}
