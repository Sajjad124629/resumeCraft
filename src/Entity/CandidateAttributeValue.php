<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CandidateAttributeValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CandidateProfile::class, inversedBy: 'attributeValues')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CandidateProfile $candidate = null;

    #[ORM\ManyToOne(targetEntity: Attribute::class, inversedBy: 'candidateValues')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Attribute $attribute = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private mixed $value = null;

    #[ORM\Column(type: 'integer')]
    #[ORM\Version]
    private ?int $version = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCandidate(): ?CandidateProfile
    {
        return $this->candidate;
    }

    public function setCandidate(?CandidateProfile $candidate): static
    {
        $this->candidate = $candidate;
        return $this;
    }

    public function getAttribute(): ?Attribute
    {
        return $this->attribute;
    }

    public function setAttribute(?Attribute $attribute): static
    {
        $this->attribute = $attribute;
        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;
        return $this;
    }
}
