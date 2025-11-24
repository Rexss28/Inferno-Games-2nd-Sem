<?php

namespace App\Entity;

use App\Repository\GameManagementRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameManagementRepository::class)]
class GameManagement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[Assert\NotBlank(message: 'Game title cannot be empty.')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'The game title must be at least {{ limit }} characters long.',
        maxMessage: 'The game title cannot exceed {{ limit }} characters.'
    )]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Assert\NotBlank(message: 'Description cannot be empty.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Please provide a more detailed description (at least {{ limit }} characters).'
    )]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[Assert\NotBlank(message: 'Price is required.')]
    #[Assert\Type('numeric', message: 'Price must be a valid number.')]
    #[Assert\Positive(message: 'Price must be greater than 0.')]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\OneToOne(inversedBy: 'game', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]   // 👈 add this line
    private ?Stock $stock = null;

    /**
     * @var Collection<int, LicenseKey>
     */
    #[ORM\OneToMany(targetEntity: LicenseKey::class, mappedBy: 'game')]
    private Collection $licenseKeys;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'game')]
    private Collection $orders;

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

    public function setImage(string $image): static
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
            // set the owning side to null (unless already changed)
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
            // set the owning side to null (unless already changed)
            if ($order->getGame() === $this) {
                $order->setGame(null);
            }
        }

        return $this;
    }
}
