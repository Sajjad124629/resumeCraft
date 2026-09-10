<?php

namespace App\Entity;

use App\Repository\PositionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PositionRepository::class)]
class Position
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $shortDescription = null;

    #[ORM\Column]
    private ?bool $isPublic = null;

    #[ORM\Column]
    private ?int $maxProject = null;

    #[ORM\Column(nullable: true)]
    private ?array $projectTags = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $level = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    /**
     * @var Collection<int, Attribute>
     */
    #[ORM\ManyToMany(targetEntity: Attribute::class)]
    private Collection $attributes;

    #[ORM\Column(type: 'integer')]
    #[ORM\Version]
    private ?int $version = 1;

    /**
     * @var Collection<int, Cv>
     */
    #[ORM\OneToMany(targetEntity: Cv::class, mappedBy: 'position')]
    private Collection $cvs;

    /**
     * @var Collection<int, DiscussionPost>
     */
    #[ORM\OneToMany(targetEntity: DiscussionPost::class, mappedBy: 'position')]
    private Collection $discussionPosts;

    /**
     * @var Collection<int, PositionAccessRule>
     */
    #[ORM\OneToMany(targetEntity: PositionAccessRule::class, mappedBy: 'position', orphanRemoval: true)]
    private Collection $accessRules;

    public function __construct()
    {
        $this->attributes = new ArrayCollection();
        $this->cvs = new ArrayCollection();
        $this->discussionPosts = new ArrayCollection();
        $this->accessRules = new ArrayCollection();
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

    public function isPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getMaxProject(): ?int
    {
        return $this->maxProject;
    }

    public function setMaxProject(int $maxProject): static
    {
        $this->maxProject = $maxProject;

        return $this;
    }

    public function getProjectTags(): ?array
    {
        return $this->projectTags;
    }

    public function setProjectTags(?array $projectTags): static
    {
        $this->projectTags = $projectTags;

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
     * @return Collection<int, Cv>
     */
    public function getCvs(): Collection
    {
        return $this->cvs;
    }

    public function addCv(Cv $cv): static
    {
        if (!$this->cvs->contains($cv)) {
            $this->cvs->add($cv);
            $cv->setPosition($this);
        }

        return $this;
    }

    public function removeCv(Cv $cv): static
    {
        if ($this->cvs->removeElement($cv)) {
            // set the owning side to null (unless already changed)
            if ($cv->getPosition() === $this) {
                $cv->setPosition(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DiscussionPost>
     */
    public function getDiscussionPosts(): Collection
    {
        return $this->discussionPosts;
    }

    public function addDiscussionPost(DiscussionPost $discussionPost): static
    {
        if (!$this->discussionPosts->contains($discussionPost)) {
            $this->discussionPosts->add($discussionPost);
            $discussionPost->setPosition($this);
        }

        return $this;
    }

    public function removeDiscussionPost(DiscussionPost $discussionPost): static
    {
        if ($this->discussionPosts->removeElement($discussionPost)) {
            // set the owning side to null (unless already changed)
            if ($discussionPost->getPosition() === $this) {
                $discussionPost->setPosition(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PositionAccessRule>
     */
    public function getAccessRules(): Collection
    {
        return $this->accessRules;
    }

    public function addAccessRule(PositionAccessRule $accessRule): static
    {
        if (!$this->accessRules->contains($accessRule)) {
            $this->accessRules->add($accessRule);
            $accessRule->setPosition($this);
        }

        return $this;
    }

    public function removeAccessRule(PositionAccessRule $accessRule): static
    {
        if ($this->accessRules->removeElement($accessRule)) {
            // set the owning side to null (unless already changed)
            if ($accessRule->getPosition() === $this) {
                $accessRule->setPosition(null);
            }
        }

        return $this;
    }
}
