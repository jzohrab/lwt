<?php

namespace App\Entity;

use App\Repository\BlargRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlargRepository::class)]
#[ORM\Table(name: 'texts')]
class Blarg
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $TxID = null;

    #[ORM\Column(length: 255)]
    public ?string $TxTitle = null;

    public ?int $Extra = null;
}
