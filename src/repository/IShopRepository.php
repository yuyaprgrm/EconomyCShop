<?php

namespace famima65536\EconomyCShop\repository;

use famima65536\EconomyCShop\model\Coordinate;
use famima65536\EconomyCShop\model\Shop;

interface IShopRepository {

	/**
	 * @param Shop $shop
	 */
	public function save(Shop $shop): void;

	/**
	 * @param Shop $shop
	 */
	public function delete(Shop $shop): void;

	/**
	 * @param Coordinate $coordinate
	 * @return Shop|null
	 */
	public function findBySign(Coordinate $coordinate): ?Shop;

	/**
	 * @param Coordinate $coordinate
	 * @return Shop|null
	 */
	public function findByChest(Coordinate $coordinate): ?Shop;
}