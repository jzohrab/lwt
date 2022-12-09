<?php

namespace App\Entity;

use App\Repository\TextTagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TextTagRepository::class)]
class TextTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $text = null;

    #[ORM\Column(length: 200)]
    private ?string $comment = null;

    #[ORM\ManyToMany(targetEntity: Text::class, inversedBy: 'textTags')]
    private Collection $texts;

    public function __construct()
    {
        $this->texts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return Collection<int, Text>
     */
    public function getTexts(): Collection
    {
        return $this->texts;
    }

    public function addText(Text $text): self
    {
        if (!$this->texts->contains($text)) {
            $this->texts->add($text);
        }

        return $this;
    }

    public function removeText(Text $text): self
    {
        $this->texts->removeElement($text);

        return $this;
    }
}
