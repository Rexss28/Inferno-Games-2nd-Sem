<?php

namespace App\Entity;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\LicenseKeyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LicenseKeyRepository::class)]
class LicenseKey
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'License key code cannot be empty.')]
    #[Assert\Length(
        min: 5,
        max: 100,
        minMessage: 'License key must be at least {{ limit }} characters long.',
        maxMessage: 'License key cannot exceed {{ limit }} characters.'
    )]
    #[ORM\Column(length: 255)]
    private ?string $code = null;

    // ✅ Default to "Available"
    #[ORM\Column(length: 255)]
    private ?string $status = 'Available';

    #[ORM\ManyToOne(inversedBy: 'licenseKeys')]
    private ?GameManagement $game = null;

    #[ORM\ManyToOne(inversedBy: 'licenseKeys')]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    private ?Order $order = null; // singular for clarity

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
