<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Position
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $shortDescription = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPublic = true;

    #[ORM\Column(type: 'integer')]
    private ?int $maxProjects = 3;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $projectTags = [];

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $level = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\ManyToMany(targetEntity: Attribute::class)]
    #[ORM\JoinTable(
        name: 'position_attributes',
        joinColumns: [new ORM\JoinColumn(name: 'position_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'attribute_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    )]
    private Collection $attributes;

    #[ORM\OneToMany(mappedBy: 'position', targetEntity: PositionAccessRule::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $accessRules;

    #[ORM\OneToMany(mappedBy: 'position', targetEntity: Cv::class, cascade: ['remove'])]
    private Collection $cvs;

    #[ORM\OneToMany(mappedBy: 'position', targetEntity: DiscussionPost::class, cascade: ['remove'])]
    private Collection $discussionPosts;

    #[ORM\Column(type: 'integer')]
    #[ORM\Version]
    private ?int $version = 1;

    public function __construct()
    {
        $this->attributes = new ArrayCollection();
        $this->accessRules = new ArrayCollection();
        $this->cvs = new ArrayCollection();
        $this->discussionPosts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getMaxProjects(): ?int
    {
        return $this->maxProjects;
    }

    public function setMaxProjects(?int $maxProjects): static
    {
        $this->maxProjects = $maxProjects;
        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;
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

    /**
     * @return Collection<int, Attribute>
     */
    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function addAttribute(Attribute $attribute): static
    {
        if (!$this->attributes->contains($attribute)) {
            $this->attributes->add($attribute);
        }
        return $this;
    }

    public function removeAttribute(Attribute $attribute): static
    {
        $this->attributes->removeElement($attribute);
        return $this;
    }

    /**
     * @return Collection<int, PositionAccessRule>
     */
    public function getAccessRules(): Collection
    {
        return $this->accessRules;
    }

    /**
     * @return Collection<int, Cv>
     */
    public function getCvs(): Collection
    {
        return $this->cvs;
    }

    /**
     * @return Collection<int, DiscussionPost>
     */
    public function getDiscussionPosts(): Collection
    {
        return $this->discussionPosts;
    }

    public function getProjectTags(): ?array
    {
        return $this->projectTags ?? [];
    }

    public function setProjectTags(?array $projectTags): static
    {
        $this->projectTags = $projectTags;
        return $this;
    }
}
