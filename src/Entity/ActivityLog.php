<?php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 50)]
    private ?string $role = null;

    #[ORM\Column(length: 50)]
    private ?string $action = null;

    #[ORM\Column(type: 'text')]
    private ?string $targetData = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        // Set timezone to Philippines (Asia/Manila)
        $this->createdAt = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getTargetData(): ?string
    {
        return $this->targetData;
    }

    public function setTargetData(string $targetData): static
    {
        $this->targetData = $targetData;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        // Return as is - no timezone conversion needed here
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        // Ensure the DateTime has Philippines timezone when setting
        if ($createdAt instanceof \DateTime) {
            $createdAt->setTimezone(new \DateTimeZone('Asia/Manila'));
        } elseif ($createdAt instanceof \DateTimeImmutable) {
            // For immutable, create a new instance with the desired timezone
            $createdAt = $createdAt->setTimezone(new \DateTimeZone('Asia/Manila'));
        }
        
        $this->createdAt = $createdAt;

        return $this;
    }
}