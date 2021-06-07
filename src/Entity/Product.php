<?php

namespace App\Entity;

use App\Entity\Image;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 * @ORM\Table(name="product",uniqueConstraints={ @ORM\UniqueConstraint(name="search_idx", columns={"style_number"})})
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $styleNumber;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToOne(targetEntity=Price::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $price;

    /**
     * @ORM\Column(type="simple_array")
     */
    private $images = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $synchronized;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStyleNumber(): ?string
    {
        return $this->styleNumber;
    }

    public function setStyleNumber(string $styleNumber): self
    {
        $this->styleNumber = $styleNumber;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(Price $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(array $images): self
    {
        $this->images = $images;

        return $this;
    }

    public function updateWith(Product $updatedProduct) {

        $dirty = false;

        if ($this->styleNumber != $updatedProduct->getStyleNumber()) { $this->styleNumber = $updatedProduct->getStyleNumber(); $dirty = true; }
        if ($this->name != $updatedProduct->getName()) { $this->name = $updatedProduct->getName(); $dirty = true; }
        if ($this->price != $updatedProduct->getPrice()) { $dirty = $this->price->updateWith($updatedProduct->getPrice()); }
        if ($this->images != $updatedProduct->getImages()) { $this->images = $updatedProduct->getImages(); $dirty = true; }

        return $dirty;
    }

    public function getSynchronized(): ?bool
    {
        return $this->synchronized;
    }

    public function setSynchronized(bool $synchronized): self
    {
        $this->synchronized = $synchronized;

        return $this;
    }

    public function __toString()
    {
        $pieces = [$this->styleNumber, $this->name, $this->price];
        return implode(",", $pieces) . ',' . implode(',', $this->images);
    }
}
