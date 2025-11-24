<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Order number cannot be empty.')]
    #[ORM\Column(length: 255)]
    private ?string $orderNumber = null;

    #[Assert\NotBlank(message: 'Quantity is required.')]
    #[Assert\Type('integer', message: 'Quantity must be an integer.')]
    #[Assert\Positive(message: 'Quantity must be greater than zero.')]
    #[ORM\Column]
    private ?int $quantity = null;

    #[Assert\NotBlank]
    #[Assert\Type('numeric', message: 'Total amount must be a valid number.')]
    #[Assert\PositiveOrZero(message: 'Total amount must be zero or a positive number.')]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalAmount = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?GameManagement $game = null;

    /**
     * @var Collection<int, LicenseKey>
     */
    #[ORM\OneToMany(targetEntity: LicenseKey::class, mappedBy: 'orders')]
    private Collection $licenseKeys;

    public function __construct()
    {
        $this->licenseKeys = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

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
            $licenseKey->setOrder($this);
        }

        return $this;
    }

    public function removeLicenseKey(LicenseKey $licenseKey): static
    {
        if ($this->licenseKeys->removeElement($licenseKey)) {
            // set the owning side to null (unless already changed)
            if ($licenseKey->getOrder() === $this) {
                $licenseKey->setOrder(null);
            }
        }

        return $this;
    }
}
