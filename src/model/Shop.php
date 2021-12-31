<?php declare(strict_types=1);

namespace famima65536\EconomyCShop\model;


class Shop {
	public function __construct(
		private string $owner,
		private string $world,
		private Product $product,
		private Coordinate $signCoordinate,
		private Coordinate $mainChest,
		private ?Coordinate $subChest=null
	){
	}

	/**
	 * @return string
	 */
	public function getOwner(): string{
		return $this->owner;
	}

	/**
	 * @return string
	 */
	public function getWorld(): string{
		return $this->world;
	}

	/**
	 * @return Product
	 */
	public function getProduct(): Product{
		return $this->product;
	}

	/**
	 * @return Coordinate
	 */
	public function getSignCoordinate(): Coordinate{
		return $this->signCoordinate;
	}

	/**
	 * @return Coordinate
	 */
	public function getMainChest(): Coordinate{
		return $this->mainChest;
	}

	/**
	 * @return Coordinate|null
	 */
	public function getSubChest(): ?Coordinate{
		return $this->subChest;
	}

}