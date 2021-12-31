<?php declare(strict_types=1);

namespace famima65536\EconomyCShop\service;

use famima65536\EconomyCShop\model\Coordinate;
use famima65536\EconomyCShop\repository\IShopRepository;

class ShopService {
	public function __construct(private IShopRepository $shopRepository){
	}

	public function existShopChest(string $world, Coordinate $coordinate): bool{
		return $this->shopRepository->findByChest($world, $coordinate) !== null;
	}
}