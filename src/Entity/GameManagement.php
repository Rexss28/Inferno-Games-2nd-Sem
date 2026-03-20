<?php

namespace App\Entity;

use App\Repository\GameManagementRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GameManagementRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['game:read']],
            security: "is_granted('PUBLIC_ACCESS')"  // Public can browse games
        ),
        new Post(
            denormalizationContext: ['groups' => ['game:write']],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_STAFF')"
        ),
        new Get(
            normalizationContext: ['groups' => ['game:read', 'game:detail']],
            security: "is_granted('PUBLIC_ACCESS')"  // Public can view game details
        ),
        new Put(
            denormalizationContext: ['groups' => ['game:write']],
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_STAFF') and object.getCreatedBy() == user)"
        ),
        new Patch(
            denormalizationContext: ['groups' => ['game:write']],
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_STAFF') and object.getCreatedBy() == user)"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
    order: ['id' => 'DESC']
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'price' => 'exact'
])]
#[ApiFilter(RangeFilter::class, properties: ['price'])]
class GameManagement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['game:read', 'game:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['game:read', 'game:detail', 'game:write'])]
    private ?string $image = null;

    #[Assert\NotBlank(message: 'Game title cannot be empty.')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'The game title must be at least {{ limit }} characters long.',
        maxMessage: 'The game title cannot exceed {{ limit }} characters.'
    )]
    #[ORM\Column(length: 255)]
    #[Groups(['game:read', 'game:detail', 'game:write'])]
    private ?string $title = null;

    #[Assert\NotBlank(message: 'Description cannot be empty.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Please provide a more detailed description (at least {{ limit }} characters).'
    )]
    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['game:read', 'game:detail', 'game:write'])]
    private ?string $description = null;

    #[Assert\NotBlank(message: 'Price is required.')]
    #[Assert\Type('numeric', message: 'Price must be a valid number.')]
    #[Assert\Positive(message: 'Price must be greater than 0.')]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['game:read', 'game:detail', 'game:write'])]
    private ?string $price = null;

    #[ORM\OneToOne(inversedBy: 'game', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['game:read', 'game:detail'])]
    private ?Stock $stock = null;

    /**
     * @var Collection<int, LicenseKey>
     */
    #[ORM\OneToMany(targetEntity: LicenseKey::class, mappedBy: 'game')]
    #[Groups(['game:detail'])]
    private Collection $licenseKeys;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'game')]
    #[Groups(['game:detail'])]
    private Collection $orders;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)] // Changed to NOT NULL - game must have a creator
    #[Groups(['game:read', 'game:detail'])]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->licenseKeys = new ArrayCollection();
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * @return Collection<int, LicenseKey>
     */
    public function getLicenseKeys(): Collection
    {
        return $this->licenseKeys;
    }

    public function addLicenseKey(LicenseKey $licenseKey): static
    {
        if (!$this->licenseKeys->contains($licenseKey)) {
            $this->licenseKeys->add($licenseKey);
            $licenseKey->setGame($this);
        }

        return $this;
    }

    public function removeLicenseKey(LicenseKey $licenseKey): static
    {
        if ($this->licenseKeys->removeElement($licenseKey)) {
            if ($licenseKey->getGame() === $this) {
                $licenseKey->setGame(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setGame($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            if ($order->getGame() === $this) {
                $order->setGame(null);
            }
        }

        return $this;
    }

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
}