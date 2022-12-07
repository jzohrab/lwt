<?php

namespace App\Entity;

use App\Repository\TextRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TextRepository::class)]
#[ORM\Table(name: 'texts')]
class Text
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'TxID', type: Types::SMALLINT)]
    private ?int $TxID = null;

    #[ORM\Column(name: 'TxLgID', type: Types::SMALLINT)]
    private ?int $TxLgID = null;

    #[ORM\Column(name: 'TxTitle', length: 200)]
    private ?string $TxTitle = null;

    #[ORM\Column(name: 'TxText', type: Types::TEXT)]
    private ?string $TxText = null;

    #[ORM\Column(name: 'TxAnnotatedText', type: Types::TEXT)]
    private ?string $TxAnnotatedText = null;

    #[ORM\Column(name: 'TxAudioURI', length: 200, nullable: true)]
    private ?string $TxAudioURI = null;

    #[ORM\Column(name: 'TxSourceURI', length: 1000, nullable: true)]
    private ?string $TxSourceURI = null;

    #[ORM\Column(name: 'TxPosition', type: Types::SMALLINT)]
    private ?int $TxPosition = null;

    #[ORM\Column(name: 'TxAudioPosition')]
    private ?float $TxAudioPosition = null;

    #[ORM\Column(name: 'TxArchived')]
    private ?bool $TxArchived = null;


    public function getTxID(): ?int
    {
        return $this->TxID;
    }

    public function setTxID(int $TxID): self
    {
        if ($this->TxID != null) {
            // Prevent dumb logic errors.
            throw new \Exception("Can't change ID if already set");
        }
        $this->TxID = $TxID;

        return $this;
    }

    public function getTxLgID(): ?int
    {
        return $this->TxLgID;
    }

    public function setTxLgID(int $TxLgID): self
    {
        $this->TxLgID = $TxLgID;

        return $this;
    }

    public function getTxTitle(): ?string
    {
        return $this->TxTitle;
    }

    public function setTxTitle(string $TxTitle): self
    {
        $this->TxTitle = $TxTitle;

        return $this;
    }

    public function getTxText(): ?string
    {
        return $this->TxText;
    }

    public function setTxText(string $TxText): self
    {
        $this->TxText = $TxText;

        return $this;
    }

    public function getTxAnnotatedText(): ?string
    {
        return $this->TxAnnotatedText;
    }

    public function setTxAnnotatedText(string $TxAnnotatedText): self
    {
        $this->TxAnnotatedText = $TxAnnotatedText;

        return $this;
    }

    public function getTxAudioURI(): ?string
    {
        return $this->TxAudioURI;
    }

    public function setTxAudioURI(?string $TxAudioURI): self
    {
        $this->TxAudioURI = $TxAudioURI;

        return $this;
    }

    public function getTxSourceURI(): ?string
    {
        return $this->TxSourceURI;
    }

    public function setTxSourceURI(?string $TxSourceURI): self
    {
        $this->TxSourceURI = $TxSourceURI;

        return $this;
    }

    public function getTxPosition(): ?int
    {
        return $this->TxPosition;
    }

    public function setTxPosition(int $TxPosition): self
    {
        $this->TxPosition = $TxPosition;

        return $this;
    }

    public function getTxAudioPosition(): ?float
    {
        return $this->TxAudioPosition;
    }

    public function setTxAudioPosition(float $TxAudioPosition): self
    {
        $this->TxAudioPosition = $TxAudioPosition;

        return $this;
    }

    public function isTxArchived(): ?bool
    {
        return $this->TxArchived;
    }

    public function setTxArchived(bool $TxArchived): self
    {
        $this->TxArchived = $TxArchived;

        return $this;
    }
}
