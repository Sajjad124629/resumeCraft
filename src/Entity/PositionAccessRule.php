<?php

namespace App\Entity;

use App\Repository\PositionAccessRuleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PositionAccessRuleRepository::class)]
class PositionAccessRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'accessRules')]
    #[ORM\JoinColumn(nullable: false,onDelete: 'CASCADE')]
    private ?Position $position = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false,onDelete: 'CASCADE')]
    private ?Attribute $attribute = null;

    #[ORM\Column(length: 10)]
    private ?string $operator = null;

    #[ORM\Column]
    private array $value = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): static
    {
        $this->position = $position;

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

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): static
    {
        $this->operator = $operator;

        return $this;
    }

    public function getValue(): array
    {
        return $this->value;
    }

    public function setValue(array $value): static
    {
        $this->value = $value;

        return $this;
    }
}
