<?php

namespace App\Entity;

use App\Repository\CvRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CvRepository::class)]
class Cv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cvs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CandidateProfile $candidate = null;

    #[ORM\ManyToOne(inversedBy: 'cvs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Position $position = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'draft';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'integer')]
    #[ORM\Version]
    private ?int $version = 1;

    /**
     * @var Collection<int, CvLike>
     */
    #[ORM\OneToMany(targetEntity: CvLike::class, mappedBy: 'cv', orphanRemoval: true)]
    private Collection $likes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->likes = new ArrayCollection();
    }
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

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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
     * @return Collection<int, CvLike>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(CvLike $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setCv($this);
        }

        return $this;
    }

    public function removeLike(CvLike $like): static
    {
        if ($this->likes->removeElement($like)) {
            // set the owning side to null (unless already changed)
            if ($like->getCv() === $this) {
                $like->setCv(null);
            }
        }

        return $this;
    }
}
