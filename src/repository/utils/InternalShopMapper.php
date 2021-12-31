<?php

namespace famima65536\EconomyCShop\repository\utils;

use famima65536\EconomyCShop\model\Shop;

class InternalShopMapper {

	/** @var array<string, array<int, Shop>> */
	private array $shops = [];
	public function __construct(){
	}

	public function get(string $world, int $coordinateHash): ?Shop{
		if(!isset($this->shops[$world])){
			return null;
		}

		return $this->shops[$world][$coordinateHash] ?? null;
	}

	public function set(string $world, int $coordinateHash, Shop $shop): void{
		if(!isset($this->shops[$world])){
			$this->shops[$world] = [];
		}
		$this->shops[$world][$coordinateHash] = $shop;
	}

	public function remove(string $world, int $coordinateHash): void{
		if(isset($this->shops[$world]) and isset($this->shops[$world][$coordinateHash])){
			unset($this->shops[$world][$coordinateHash]);
		}
	}
}