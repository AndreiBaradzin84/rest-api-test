<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="products")
 */
class Product {

    /**
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, name="type")
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=50, name="color")
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    private $color;

    /**
     * @ORM\Column(type="string", length=5,  name="size")
     * @Assert\Type("string")
     * @Assert\NotBlank()
     */
    private $size;

    /**
     * @ORM\Column(type="integer", name="price")
     * @Assert\Type("integer")
     * @Assert\NotBlank()
     */
    private $price;


    public function getId(): int
    {
        return $this->id;
    }

    public function setId( int $id )
    {
        $this->id = $id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType( string $type )
    {
        $this->type = $type;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor( string $color )
    {
        $this->color = $color;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function setSize( string $size )
    {
        $this->size = $size;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice( int $price )
    {
        $this->price = $price;
    }

}
