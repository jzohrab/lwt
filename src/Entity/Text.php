<?php

namespace App\Entity;

use App\Repository\TextRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\ManyToOne(targetEntity: 'Language', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'TxLgID', referencedColumnName: 'LgID', nullable: false)]
    private ?Language $language = null;

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

    #[ORM\ManyToMany(targetEntity: TextTag::class, inversedBy: 'texts')]
    #[ORM\JoinTable(name: 'texttags', joinColumns={ORM\JoinColumn(name: 'TtTxID', referencedColumnName: 'ID')}, inverseJoinColumns={ORM\JoinColumn(name: 'TtT2ID', referencedColumnName: 'Id', unique: true)}
    private Collection $textTags;

    public function __construct()
    {
        $this->textTags = new ArrayCollection();
    }


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

    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    public function setLanguage(Language $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return Collection<int, TextTag>
     */
    public function getTextTags(): Collection
    {
        return $this->textTags;
    }

    public function addTextTag(TextTag $textTag): self
    {
        if (!$this->textTags->contains($textTag)) {
            $this->textTags->add($textTag);
            $textTag->addText($this);
        }

        return $this;
    }

    public function removeTextTag(TextTag $textTag): self
    {
        if ($this->textTags->removeElement($textTag)) {
            $textTag->removeText($this);
        }

        return $this;
    }
}
