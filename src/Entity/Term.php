<?php

namespace App\Entity;

use App\Repository\TermRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TermRepository::class)]
#[ORM\Table(name: 'words')]
class Term
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'WoID', type: Types::SMALLINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: 'Language', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'WoLgID', referencedColumnName: 'LgID', nullable: false)]
    private ?Language $language = null;

    #[ORM\Column(name: 'WoText', length: 250)]
    private ?string $WoText = null;

    #[ORM\Column(name: 'WoTextLC', length: 250)]
    private ?string $WoTextLC = null;

    #[ORM\Column(name: 'WoStatus', type: Types::SMALLINT)]
    private ?int $WoStatus = 1;

    #[ORM\Column(name: 'WoTranslation', length: 500)]
    private ?string $WoTranslation = null;

    #[ORM\Column(name: 'WoRomanization', length: 100)]
    private ?string $WoRomanization = null;

    #[ORM\Column(name: 'WoSentence', length: 1000)]
    private ?string $WoSentence = null;

    #[ORM\Column(name: 'WoWordCount', type: Types::SMALLINT)]
    private ?int $WoWordCount = null;

    #[ORM\JoinTable(name: 'wordtags')]
    #[ORM\JoinColumn(name: 'WtWoID', referencedColumnName: 'WoID')]
    #[ORM\InverseJoinColumn(name: 'WtTgID', referencedColumnName: 'TgID')]
    #[ORM\ManyToMany(targetEntity: TermTag::class, cascade: ['persist'])]
    private Collection $termTags;

    #[ORM\JoinTable(name: 'wordparents')]
    #[ORM\JoinColumn(name: 'WpWoID', referencedColumnName: 'WoID')]
    #[ORM\InverseJoinColumn(name: 'WpParentWoID', referencedColumnName: 'WoID')]
    #[ORM\ManyToMany(targetEntity: Term::class, cascade: ['persist'])]
    private Collection $parents;
    /* Really, a word can have only one parent, but since we have a
       join table, I'll treat it like a many-to-many join in the
       private members, but the interface will only have setParent()
       and getParent(). */

    // An "override" of the parent text, to be set by the text form on
    // submit.  This is a code smell -- the Symfony approach of using
    // the entity as a DTO feels like it runs into problems.  Ref
    // https://blog.martinhujer.cz/symfony-forms-with-request-objects/.
    // In a nutshell, I can't determine the parent without also
    // knowing the term's Language, but I don't know that until the
    // form processes the request, which occurs after the builder and
    // its transforms have been used.
    private string $parentText = null;


    public function __construct()
    {
        $this->termTags = new ArrayCollection();
        $this->parents = new ArrayCollection();
    }

    public function getID(): ?int
    {
        return $this->id;
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

    public function setText(string $WoText): self
    {
        $parts = mb_split("\s+", $WoText);
        $testlen = function($p) { return mb_strlen($p) > 0; };
        $realparts = array_filter($parts, $testlen);
        $cleanword = implode(' ', $realparts);

        $this->WoText = $cleanword;
        $this->WoTextLC = mb_strtolower($cleanword);
        $this->setWordCount(count($realparts));

        return $this;
    }

    public function getText(): ?string
    {
        return $this->WoText;
    }

    public function getTextLC(): ?string
    {
        return $this->WoTextLC;
    }

    public function setStatus(?int $n): self
    {
        $this->WoStatus = $n;
        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->WoStatus;
    }

    public function setWordCount(?int $n): self
    {
        $this->WoWordCount = $n;
        return $this;
    }

    public function getWordCount(): ?int
    {
        return $this->WoWordCount;
    }
    
    public function setTranslation(string $WoTranslation): self
    {
        $this->WoTranslation = $WoTranslation;
        return $this;
    }

    public function getTranslation(): ?string
    {
        return $this->WoTranslation;
    }

    public function setRomanization(string $WoRomanization): self
    {
        $this->WoRomanization = $WoRomanization;
        return $this;
    }

    public function getRomanization(): ?string
    {
        return $this->WoRomanization;
    }

    public function setSentence(string $s): self
    {
        $this->WoSentence = $s;
        return $this;
    }

    public function getSentence(): ?string
    {
        return $this->WoSentence;
    }


    /**
     * @return Collection<int, TextTag>
     */
    public function getTermTags(): Collection
    {
        return $this->termTags;
    }

    public function addTermTag(TermTag $termTag): self
    {
        if (!$this->termTags->contains($termTag)) {
            $this->termTags->add($termTag);
        }
        return $this;
    }

    public function removeTermTag(TermTag $termTag): self
    {
        $this->termTags->removeElement($termTag);
        return $this;
    }

    /**
     * @return Term or null
     */
    public function getParent(): ?Term
    {
        if ($this->parents->isEmpty())
            return null;
        return $this->parents[0];
    }

    public function setParent(Term $parent): self
    {
        $this->parents = new ArrayCollection();
        $this->parents[] = $parent;
        return $this;
    }

    public function removeParent(): self
    {
        $this->parents = new ArrayCollection();
        return $this;
    }


    // Getter and setter parentText that should never be used, except
    // by the TermType and its transform.
    public function getParentText(): ?string
    {
        return $this->parentText;
    }

    public function setParentText(?string $s): self
    {
        $this->parentText = $s;
        return $this;
    }

}
