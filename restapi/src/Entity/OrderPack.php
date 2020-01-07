<?php

namespace App\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OrderPackRepository")
 * @ORM\Table(name="order_pack")
 */
class OrderPack {

    /**
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity="ApiOrder")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    private $order;

    /**
     * @ORM\Column(type="integer", name="quantity")
     * @Assert\NotBlank()
     */
    private $quantity;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId( int $id )
    {
        $this->id = $id;
    }

    public function getOrder(): ApiOrder
    {
        return $this->order;
    }

    public function setOrder(ApiOrder $order)
    {
        $this->order = $order;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product )
    {
        $this->product = $product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity( int $quantity )
    {
        $this->quantity = $quantity;
    }
}
