<?php

namespace App\Entity;

use App\Repository\AttributeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttributeRepository::class)]
class Attribute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?array $options = null;

    #[ORM\ManyToOne(inversedBy: 'attributes')]
    #[ORM\JoinColumn(nullable: false,onDelete:'CASCADE')]
    private ?AttributeCategory $category = null;

    /**
     * @var Collection<int, CandidateAttributeValue>
     */
    #[ORM\OneToMany(targetEntity: CandidateAttributeValue::class, mappedBy: 'attribute')]
    private Collection $candidateValues;

    public function __construct()
    {
        $this->candidateValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function getCategory(): ?AttributeCategory
    {
        return $this->category;
    }

    public function setCategory(?AttributeCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, CandidateAttributeValue>
     */
    public function getCandidateValues(): Collection
    {
        return $this->candidateValues;
    }

    public function addCandidateValue(CandidateAttributeValue $candidateValue): static
    {
        if (!$this->candidateValues->contains($candidateValue)) {
            $this->candidateValues->add($candidateValue);
            $candidateValue->setAttribute($this);
        }

        return $this;
    }

    public function removeCandidateValue(CandidateAttributeValue $candidateValue): static
    {
        if ($this->candidateValues->removeElement($candidateValue)) {
            // set the owning side to null (unless already changed)
            if ($candidateValue->getAttribute() === $this) {
                $candidateValue->setAttribute(null);
            }
        }

        return $this;
    }
}
