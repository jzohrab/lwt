<?php

namespace App\Entity;

use App\Repository\LanguageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LanguageRepository::class)]
#[ORM\Table(name: 'languages')]
class Language
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'LgID', type: Types::SMALLINT)]
    private ?int $LgID = null;

    #[ORM\Column(name: 'LgName', length: 40)]
    private ?string $LgName = null;

    #[ORM\Column(name: 'LgDict1URI', length: 200)]
    private ?string $LgDict1URI = null;

    #[ORM\Column(name: 'LgDict2URI', length: 200)]
    private ?string $LgDict2URI = null;

    #[ORM\Column(name: 'LgGoogleTranslateURI', length: 200)]
    private ?string $LgGoogleTranslateURI = null;

    #[ORM\Column(name: 'LgExportTemplate', length: 1000, nullable: true)]
    private ?string $LgExportTemplate = null;

    #[ORM\Column(name: 'LgTextSize', type: Types::SMALLINT)]
    private ?int $LgTextSize = null;

    #[ORM\Column(name: 'LgCharacterSubstitutions', length: 500)]
    private ?string $LgCharacterSubstitutions = null;

    #[ORM\Column(name: 'LgRegexpSplitSentences', length: 500)]
    private ?string $LgRegexpSplitSentences = null;

    #[ORM\Column(name: 'LgExceptionsSplitSentences', length: 500)]
    private ?string $LgExceptionsSplitSentences = null;

    #[ORM\Column(name: 'LgRegexpWordCharacters', length: 500)]
    private ?string $LgRegexpWordCharacters = null;

    #[ORM\Column(name: 'LgRemoveSpaces')]
    private ?bool $LgRemoveSpaces = null;

    #[ORM\Column(name: 'LgSplitEachChar')]
    private ?bool $LgSplitEachChar = null;

    #[ORM\Column(name: 'LgRightToLeft')]
    private ?bool $LgRightToLeft = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLgID(): ?int
    {
        return $this->LgID;
    }

    public function setLgID(int $LgID): self
    {
        $this->LgID = $LgID;

        return $this;
    }

    public function getLgName(): ?string
    {
        return $this->LgName;
    }

    public function setLgName(string $LgName): self
    {
        $this->LgName = $LgName;

        return $this;
    }

    public function getLgDict1URI(): ?string
    {
        return $this->LgDict1URI;
    }

    public function setLgDict1URI(string $LgDict1URI): self
    {
        $this->LgDict1URI = $LgDict1URI;

        return $this;
    }

    public function getLgDict2URI(): ?string
    {
        return $this->LgDict2URI;
    }

    public function setLgDict2URI(string $LgDict2URI): self
    {
        $this->LgDict2URI = $LgDict2URI;

        return $this;
    }

    public function getLgGoogleTranslateURI(): ?string
    {
        return $this->LgGoogleTranslateURI;
    }

    public function setLgGoogleTranslateURI(string $LgGoogleTranslateURI): self
    {
        $this->LgGoogleTranslateURI = $LgGoogleTranslateURI;

        return $this;
    }

    public function getLgExportTemplate(): ?string
    {
        return $this->LgExportTemplate;
    }

    public function setLgExportTemplate(?string $LgExportTemplate): self
    {
        $this->LgExportTemplate = $LgExportTemplate;

        return $this;
    }

    public function getLgTextSize(): ?int
    {
        return $this->LgTextSize;
    }

    public function setLgTextSize(int $LgTextSize): self
    {
        $this->LgTextSize = $LgTextSize;

        return $this;
    }

    public function getLgCharacterSubstitutions(): ?string
    {
        return $this->LgCharacterSubstitutions;
    }

    public function setLgCharacterSubstitutions(string $LgCharacterSubstitutions): self
    {
        $this->LgCharacterSubstitutions = $LgCharacterSubstitutions;

        return $this;
    }

    public function getLgRegexpSplitSentences(): ?string
    {
        return $this->LgRegexpSplitSentences;
    }

    public function setLgRegexpSplitSentences(string $LgRegexpSplitSentences): self
    {
        $this->LgRegexpSplitSentences = $LgRegexpSplitSentences;

        return $this;
    }

    public function getLgExceptionsSplitSentences(): ?string
    {
        return $this->LgExceptionsSplitSentences;
    }

    public function setLgExceptionsSplitSentences(string $LgExceptionsSplitSentences): self
    {
        $this->LgExceptionsSplitSentences = $LgExceptionsSplitSentences;

        return $this;
    }

    public function getLgRegexpWordCharacters(): ?string
    {
        return $this->LgRegexpWordCharacters;
    }

    public function setLgRegexpWordCharacters(string $LgRegexpWordCharacters): self
    {
        $this->LgRegexpWordCharacters = $LgRegexpWordCharacters;

        return $this;
    }

    public function isLgRemoveSpaces(): ?bool
    {
        return $this->LgRemoveSpaces;
    }

    public function setLgRemoveSpaces(bool $LgRemoveSpaces): self
    {
        $this->LgRemoveSpaces = $LgRemoveSpaces;

        return $this;
    }

    public function isLgSplitEachChar(): ?bool
    {
        return $this->LgSplitEachChar;
    }

    public function setLgSplitEachChar(bool $LgSplitEachChar): self
    {
        $this->LgSplitEachChar = $LgSplitEachChar;

        return $this;
    }

    public function isLgRightToLeft(): ?bool
    {
        return $this->LgRightToLeft;
    }

    public function setLgRightToLeft(bool $LgRightToLeft): self
    {
        $this->LgRightToLeft = $LgRightToLeft;

        return $this;
    }

}
