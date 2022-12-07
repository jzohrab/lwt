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
    private string $TxTitle = '';

    #[ORM\Column(name: 'TxText', type: Types::TEXT)]
    private string $TxText = '';

    #[ORM\Column(name: 'TxAnnotatedText', type: Types::TEXT)]
    private string $TxAnnotatedText = '';

    #[ORM\Column(name: 'TxAudioURI', length: 200, nullable: true)]
    private ?string $TxAudioURI = null;

    #[ORM\Column(name: 'TxSourceURI', length: 1000, nullable: true)]
    private ?string $TxSourceURI = null;

    #[ORM\Column(name: 'TxPosition', type: Types::SMALLINT)]
    private int $TxPosition = 0;

    #[ORM\Column(name: 'TxAudioPosition')]
    private float $TxAudioPosition = 0;

    #[ORM\Column(name: 'TxArchived')]
    private bool $TxArchived = false;


    public function getID(): ?int
    {
        return $this->TxID;
    }

    public function setID(int $TxID): self
    {
        if ($this->TxID != null) {
            // Prevent dumb logic errors.
            throw new \Exception("Can't change ID if already set");
        }
        $this->TxID = $TxID;

        return $this;
    }

    public function getLgID(): ?int
    {
        return $this->TxLgID;
    }

    public function setLgID(int $TxLgID): self
    {
        $this->TxLgID = $TxLgID;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->TxTitle;
    }

    public function setTitle(string $TxTitle): self
    {
        $this->TxTitle = $TxTitle;

        return $this;
    }

    public function getText(): string
    {
        return $this->TxText;
    }

    public function setText(string $TxText): self
    {
        $this->TxText = $TxText;

        return $this;
    }

    public function getAnnotatedText(): string
    {
        return $this->TxAnnotatedText;
    }

    public function setAnnotatedText(string $TxAnnotatedText): self
    {
        $this->TxAnnotatedText = $TxAnnotatedText;

        return $this;
    }

    public function getAudioURI(): ?string
    {
        return $this->TxAudioURI;
    }

    public function setAudioURI(?string $TxAudioURI): self
    {
        $this->TxAudioURI = $TxAudioURI;

        return $this;
    }

    public function getSourceURI(): ?string
    {
        return $this->TxSourceURI;
    }

    public function setSourceURI(?string $TxSourceURI): self
    {
        $this->TxSourceURI = $TxSourceURI;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->TxPosition;
    }

    public function setPosition(int $TxPosition): self
    {
        $this->TxPosition = $TxPosition;

        return $this;
    }

    public function getAudioPosition(): float
    {
        return $this->TxAudioPosition;
    }

    public function setAudioPosition(float $TxAudioPosition): self
    {
        $this->TxAudioPosition = $TxAudioPosition;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->TxArchived;
    }

    public function setArchived(bool $TxArchived): self
    {
        $this->TxArchived = $TxArchived;

        return $this;
    }
}
