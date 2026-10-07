<?php

namespace App\Entity;

use App\Repository\SupportTicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportTicketRepository::class)]
#[ORM\Table(name: 'support_ticket')]
#[ORM\HasLifecycleCallbacks]
class SupportTicket
{
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SOLVED = 'solved';

    public const PRIORITY_HIGH = 'High';
    public const PRIORITY_AVERAGE = 'Average';
    public const PRIORITY_LOW = 'Low';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $ticketId = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $summary = null;

    #[ORM\Column(length: 20)]
    private ?string $priority = self::PRIORITY_AVERAGE;

    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_OPEN;

    #[ORM\Column(length: 255)]
    private ?string $reportedBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reporterEmail = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $positionTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pageLink = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $cloudPath = null;

    #[ORM\Column(length: 50, options: ['default' => 'dropbox'])]
    private ?string $provider = 'dropbox';

    #[ORM\Column(options: ['default' => false])]
    private bool $isSimulated = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $solvedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->status = self::STATUS_OPEN;
        $this->provider = 'dropbox';
        $this->isSimulated = false;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTicketId(): ?string
    {
        return $this->ticketId;
    }

    public function setTicketId(string $ticketId): static
    {
        $this->ticketId = $ticketId;
        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): static
    {
        $this->summary = $summary;
        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        if ($status === self::STATUS_SOLVED && $this->solvedAt === null) {
            $this->solvedAt = new \DateTimeImmutable();
        } elseif ($status !== self::STATUS_SOLVED) {
            $this->solvedAt = null;
        }
        return $this;
    }

    public function getReportedBy(): ?string
    {
        return $this->reportedBy;
    }

    public function setReportedBy(string $reportedBy): static
    {
        $this->reportedBy = $reportedBy;
        return $this;
    }

    public function getReporterEmail(): ?string
    {
        return $this->reporterEmail;
    }

    public function setReporterEmail(?string $reporterEmail): static
    {
        $this->reporterEmail = $reporterEmail;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getPositionTitle(): ?string
    {
        return $this->positionTitle;
    }

    public function setPositionTitle(?string $positionTitle): static
    {
        $this->positionTitle = $positionTitle;
        return $this;
    }

    public function getPageLink(): ?string
    {
        return $this->pageLink;
    }

    public function setPageLink(?string $pageLink): static
    {
        $this->pageLink = $pageLink;
        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getCloudPath(): ?string
    {
        return $this->cloudPath;
    }

    public function setCloudPath(?string $cloudPath): static
    {
        $this->cloudPath = $cloudPath;
        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function isSimulated(): bool
    {
        return $this->isSimulated;
    }

    public function setIsSimulated(bool $isSimulated): static
    {
        $this->isSimulated = $isSimulated;
        return $this;
    }

    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function setAdminNotes(?string $adminNotes): static
    {
        $this->adminNotes = $adminNotes;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getSolvedAt(): ?\DateTimeImmutable
    {
        return $this->solvedAt;
    }

    public function setSolvedAt(?\DateTimeImmutable $solvedAt): static
    {
        $this->solvedAt = $solvedAt;
        return $this;
    }
}

