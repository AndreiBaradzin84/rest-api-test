<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ApiOrderRepository")
 * @ORM\Table(name="orders")
 */
class ApiOrder {

    /**
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="timestamp")
     * @Assert\NotBlank()
     *
     */
    private $timestamp;

    /**
     * @ORM\Column(type="float", name="total")
     * @Assert\NotBlank()
     */
    private $total = 0;

    /**
     * @ORM\Column(type="string", length=10,  name="status")
     * @Assert\NotBlank()
     */
    private $status;

    /**
     * @ORM\OneToMany(targetEntity="OrderPack", mappedBy="order")
     */
    protected $items;


    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId( int $id )
    {
        $this->id = $id;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function setTimestamp( string $timestamp )
    {
        $this->timestamp = $timestamp;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus( string $status )
    {
        $this->status = $status;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal( float $total )
    {
        $this->total = $total;
    }

    public function defineStatus() {
        if ($this->total < 10) {
            $this->setStatus('draft');
        } else {
            $this->setStatus('complete');
        }
    }

    public function getItems()
    {
        return $this->items->toArray();
    }

    public function addItem(OrderPack $item)
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
        }
    }

    public function removeItem(OrderPack $item)
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
        }
    }

    public function getItemsOrderedCount()
    {
        return $this->items->count();
    }


}
