<?php

namespace famima65536\EconomyCShop\model;

use pocketmine\item\Item;

class Product {
	public function __construct(
		private Item $item,
		private int $price,
		private int $amount
	){
	}

	/**
	 * @return Item
	 */
	public function getItem(): Item{
		return $this->item;
	}

	/**
	 * @return int
	 */
	public function getPrice(): int{
		return $this->price;
	}

	/**
	 * @return int
	 */
	public function getAmount(): int{
		return $this->amount;
	}
}