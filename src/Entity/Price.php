<?php

namespace App\Entity;

use App\Repository\PriceRepository;
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\Entity(repositoryClass=PriceRepository::class)
 */
class Price
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $currency;

    /**
     * @ORM\Column(type="float")
     */
    private $amount;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function updateWith (Price $updatedPrice) {

        $dirty = false;

        if ($this->currency != $updatedPrice->getCurrency()) { $this->currency = $updatedPrice->getCurrency(); $dirty = true; }
        if ($this->amount != $updatedPrice->getAmount()) { $this->amount = $updatedPrice->getAmount(); $dirty = true; }

        return $dirty;

    }

    public function __toString()
    {
        $pieces = [$this->currency, $this->amount];
        return implode(",", $pieces);
    }

}
