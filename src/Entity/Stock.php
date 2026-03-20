<?php

namespace App\Entity;

use App\Repository\StockRepository;
use Symfony\Component\Validator\Constraints as Assert;
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

#[ORM\Entity(repositoryClass: StockRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['stock:read']],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_STAFF')"
        ),
        new Post(
            denormalizationContext: ['groups' => ['stock:write']],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_STAFF')"
        ),
        new Get(
            normalizationContext: ['groups' => ['stock:read', 'stock:detail']],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_STAFF') or (is_granted('ROLE_USER') and object.getCreatedBy() == user)"
        ),
        new Put(
            denormalizationContext: ['groups' => ['stock:write']],
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_STAFF') and object.getCreatedBy() == user)"
        ),
        new Patch(
            denormalizationContext: ['groups' => ['stock:write']],
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_STAFF') and object.getCreatedBy() == user)"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
    order: ['id' => 'DESC']
)]
#[ApiFilter(SearchFilter::class, properties: [
    'status' => 'exact',
    'game' => 'exact'
])]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['stock:read', 'stock:detail'])]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Available quantity cannot be empty.')]
    #[Assert\Type('integer', message: 'Available quantity must be an integer.')]
    #[Assert\PositiveOrZero(message: 'Available quantity must be zero or positive.')]
    #[ORM\Column]
    #[Groups(['stock:read', 'stock:detail', 'stock:write'])]
    private ?int $availableQuantity = null;

    #[Assert\NotBlank(message: 'Total quantity is required.')]
    #[Assert\Type('integer')]
    #[Assert\Positive(message: 'Total quantity must be a positive number.')]
    #[ORM\Column]
    #[Groups(['stock:read', 'stock:detail', 'stock:write'])]
    private ?int $totalQuantity = null;

    #[ORM\Column(length: 255)]
    #[Groups(['stock:read', 'stock:detail', 'stock:write'])]
    private ?string $status = null;

    #[ORM\OneToOne(mappedBy: 'stock', cascade: ['persist', 'remove'])]
    #[Groups(['stock:read', 'stock:detail'])]
    private ?GameManagement $game = null;

    // ✅ ADD THIS: Ownership tracking
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['stock:read', 'stock:detail'])]
    private ?User $createdBy = null;

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

    // Existing methods remain...
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAvailableQuantity(): ?int
    {
        return $this->availableQuantity;
    }

    public function setAvailableQuantity(int $availableQuantity): static
    {
        $this->availableQuantity = $availableQuantity;
        return $this;
    }

    public function getTotalQuantity(): ?int
    {
        return $this->totalQuantity;
    }

    public function setTotalQuantity(int $totalQuantity): static
    {
        $this->totalQuantity = $totalQuantity;
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
        if ($game === null && $this->game !== null) {
            $this->game->setStock(null);
        }

        if ($game !== null && $game->getStock() !== $this) {
            $game->setStock($this);
        }

        $this->game = $game;
        return $this;
    }

    //METHODS
    public function updateStatusAutomatically(): void
    {
        if ($this->availableQuantity <= 0) {
            $this->status = 'Out of Stock';
        } elseif ($this->availableQuantity < 10) {
            $this->status = 'Low Stock';
        } else {
            $this->status = 'In Stock';
        }
    }
}