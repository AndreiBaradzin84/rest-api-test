<?php

namespace App\Entity;

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
     * @ORM\Column(type="integer", name="total")
     * @Assert\NotBlank()
     */
    private $total = 0;

    /**
     * @ORM\Column(type="string", length=10,  name="status")
     * @Assert\NotBlank()
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=2,  name="country")
     * @Assert\NotBlank()
     */
    private $country;

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

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry( string $country )
    {
        $this->country = $country;
    }

    public function defineStatus() {
        if ($this->total < 10) {
            $this->setStatus('draft');
        } else {
            $this->setStatus('complete');
        }
    }


}
