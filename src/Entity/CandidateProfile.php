<?php

namespace App\Entity;

use App\Repository\CandidateProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidateProfileRepository::class)]
class CandidateProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'candidateProfile', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false,onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'integer')]
    #[ORM\Version]
    private ?int $version = 1;

    /**
     * @var Collection<int, CandidateAttributeValue>
     */
    #[ORM\OneToMany(targetEntity: CandidateAttributeValue::class, mappedBy: 'candidate', orphanRemoval: true)]
    private Collection $candidateAttributeValues;

    /**
     * @var Collection<int, CandidateAttributeValue>
     */
    #[ORM\OneToMany(targetEntity: CandidateAttributeValue::class, mappedBy: 'candidate', orphanRemoval: true)]
    private Collection $attributeValues;

    /**
     * @var Collection<int, Cv>
     */
    #[ORM\OneToMany(targetEntity: Cv::class, mappedBy: 'candidate', orphanRemoval: true)]
    private Collection $cvs;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'candidate', orphanRemoval: true)]
    private Collection $projects;

    public function __construct()
    {
        $this->candidateAttributeValues = new ArrayCollection();
        $this->attributeValues = new ArrayCollection();
        $this->cvs = new ArrayCollection();
        $this->projects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

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
     * @return Collection<int, CandidateAttributeValue>
     */
    public function getCandidateAttributeValues(): Collection
    {
        return $this->candidateAttributeValues;
    }

    public function addCandidateAttributeValue(CandidateAttributeValue $candidateAttributeValue): static
    {
        if (!$this->candidateAttributeValues->contains($candidateAttributeValue)) {
            $this->candidateAttributeValues->add($candidateAttributeValue);
            $candidateAttributeValue->setCandidate($this);
        }

        return $this;
    }

    public function removeCandidateAttributeValue(CandidateAttributeValue $candidateAttributeValue): static
    {
        if ($this->candidateAttributeValues->removeElement($candidateAttributeValue)) {
            // set the owning side to null (unless already changed)
            if ($candidateAttributeValue->getCandidate() === $this) {
                $candidateAttributeValue->setCandidate(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CandidateAttributeValue>
     */
    public function getAttributeValues(): Collection
    {
        return $this->attributeValues;
    }

    public function addAttributeValue(CandidateAttributeValue $attributeValue): static
    {
        if (!$this->attributeValues->contains($attributeValue)) {
            $this->attributeValues->add($attributeValue);
            $attributeValue->setCandidate($this);
        }

        return $this;
    }

    public function removeAttributeValue(CandidateAttributeValue $attributeValue): static
    {
        if ($this->attributeValues->removeElement($attributeValue)) {
            // set the owning side to null (unless already changed)
            if ($attributeValue->getCandidate() === $this) {
                $attributeValue->setCandidate(null);
            }
        }

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
            $cv->setCandidate($this);
        }

        return $this;
    }

    public function removeCv(Cv $cv): static
    {
        if ($this->cvs->removeElement($cv)) {
            // set the owning side to null (unless already changed)
            if ($cv->getCandidate() === $this) {
                $cv->setCandidate(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setCandidate($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getCandidate() === $this) {
                $project->setCandidate(null);
            }
        }

        return $this;
    }
}
