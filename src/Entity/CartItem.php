<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Product;
use App\Entity\User;

#[ORM\Entity(repositoryClass: 'App\Repository\CartItemRepository')]
#[ORM\Table(name: 'cart_items')]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['cart:read'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(groups: ['cart:read','product:read'])]
    private Product $product;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'cartItems')]
    private ?User $user = null;
    

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive(message: 'The quantity must be greater than zero.')]
    #[Groups(['cart:read'])]
    private int $quantity;

    #[ORM\Column(type: 'decimal', scale: 2)]
    #[Assert\PositiveOrZero(message: 'The price must be zero or positive.')]
    #[Groups(['cart:read'])]
    private float $price;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

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

    
}
