<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private ?bool $isVerified = null;

    #[ORM\Column]
    private ?bool $isBlocked = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebookId = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?CandidateProfile $candidateProfile = null;

    /**
     * @var Collection<int, CvLike>
     */
    #[ORM\OneToMany(targetEntity: CvLike::class, mappedBy: 'recruiter')]
    private Collection $likes;

    /**
     * @var Collection<int, DiscussionPost>
     */
    #[ORM\OneToMany(targetEntity: DiscussionPost::class, mappedBy: 'author')]
    private Collection $discussionPosts;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
        $this->discussionPosts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isBlocked(): ?bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): static
    {
        $this->isBlocked = $isBlocked;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getFacebookId(): ?string
    {
        return $this->facebookId;
    }

    public function setFacebookId(?string $facebookId): static
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    public function getCandidateProfile(): ?CandidateProfile
    {
        return $this->candidateProfile;
    }

    public function setCandidateProfile(CandidateProfile $candidateProfile): static
    {
        // set the owning side of the relation if necessary
        if ($candidateProfile->getUser() !== $this) {
            $candidateProfile->setUser($this);
        }

        $this->candidateProfile = $candidateProfile;

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
            $like->setRecruiter($this);
        }

        return $this;
    }

    public function removeLike(CvLike $like): static
    {
        if ($this->likes->removeElement($like)) {
            // set the owning side to null (unless already changed)
            if ($like->getRecruiter() === $this) {
                $like->setRecruiter(null);
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
            $discussionPost->setAuthor($this);
        }

        return $this;
    }

    public function removeDiscussionPost(DiscussionPost $discussionPost): static
    {
        if ($this->discussionPosts->removeElement($discussionPost)) {
            // set the owning side to null (unless already changed)
            if ($discussionPost->getAuthor() === $this) {
                $discussionPost->setAuthor(null);
            }
        }

        return $this;
    }


}
