<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\LicenseKeyRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LicenseKeyRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['license_key:read']],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_STAFF')"
        ),
        new Post(
            denormalizationContext: ['groups' => ['license_key:write']],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_STAFF')"
        ),
        new Get(
            normalizationContext: ['groups' => ['license_key:read', 'license_key:detail']],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_STAFF') or (is_granted('ROLE_USER') and object.getCreatedBy() == user)"
        ),
        new Put(
            denormalizationContext: ['groups' => ['license_key:write']],
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_STAFF') and object.getCreatedBy() == user)"
        ),
        new Patch(
            denormalizationContext: ['groups' => ['license_key:write']],
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_STAFF') and object.getCreatedBy() == user)"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
    order: ['id' => 'DESC']
)]
#[ApiFilter(SearchFilter::class, properties: [
    'code' => 'partial',
    'status' => 'exact',
    'game' => 'exact',
    'order' => 'exact'
])]
class LicenseKey
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['license_key:read', 'license_key:detail'])]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'License key code cannot be empty.')]
    #[Assert\Length(
        min: 5,
        max: 100,
        minMessage: 'License key must be at least {{ limit }} characters long.',
        maxMessage: 'License key cannot exceed {{ limit }} characters.'
    )]
    #[ORM\Column(length: 255)]
    #[Groups(['license_key:read', 'license_key:detail', 'license_key:write'])]
    private ?string $code = null;

    // ✅ Default to "Available"
    #[ORM\Column(length: 255)]
    #[Groups(['license_key:read', 'license_key:detail', 'license_key:write'])]
    private ?string $status = 'Available';

    #[ORM\ManyToOne(inversedBy: 'licenseKeys')]
    #[Groups(['license_key:read', 'license_key:detail', 'license_key:write'])]
    private ?GameManagement $game = null;

    #[ORM\ManyToOne(inversedBy: 'licenseKeys')]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Groups(['license_key:read', 'license_key:detail', 'license_key:write'])]
    private ?Order $order = null; // singular for clarity

    // ✅ ADD THIS: Ownership tracking
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['license_key:read', 'license_key:detail'])]
    private ?User $createdBy = null;

    // ─────────────────────────────
    // 🔹 Getters & Setters
    // ─────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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

    public function getGame(): ?GameManagement
    {
        return $this->game;
    }

    public function setGame(?GameManagement $game): static
    {
        $this->game = $game;
        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    // ✅ ADD THESE METHODS:
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    // Helper method for voter/security checks
    public function isCreatedBy(User $user): bool
    {
        return $this->createdBy && $this->createdBy->getId() === $user->getId();
    }

    // ─────────────────────────────
    // ⚙️ Auto-update license key status
    // ─────────────────────────────
    public function updateStatusAutomatically(): void
    {
        if ($this->getOrder() !== null) {
            $orderStatus = $this->getOrder()->getStatus();

            if ($orderStatus === 'Completed') {
                $this->status = 'Sold';
            } elseif ($orderStatus === 'Pending') {
                $this->status = 'Reserved';
            } elseif ($orderStatus === 'Cancelled') {
                $this->status = 'Available';
            }
        } else {
            $this->status = 'Available';
        }
    }
}