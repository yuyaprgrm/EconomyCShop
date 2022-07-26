<?php

namespace famima65536\EconomyCShop\application;

use famima65536\EconomyCShop\application\exception\DuplicateShopException;
use famima65536\EconomyCShop\model\Coordinate;
use famima65536\EconomyCShop\model\Product;
use famima65536\EconomyCShop\model\Shop;
use famima65536\EconomyCShop\repository\IShopRepository;
use famima65536\EconomyCShop\service\ShopService;
use pocketmine\block\BaseSign;
use pocketmine\block\Chest;
use pocketmine\item\Item;
use pocketmine\player\Player;

class ShopApplicationService {
	public function __construct(
		private ShopService $shopService,
		private IShopRepository $shopRepository
	){
	}

	/**
	 * @param Player $owner
	 * @param Product $product
	 * @param BaseSign $sign
	 * @param Chest $mainChest
	 * @param Chest|null $subchest
	 * @return Shop
	 * @throws DuplicateShopException
	 */
	public function createShop(Player $owner, Product $product, BaseSign $sign, Chest $mainChest, ?Chest $subchest): Shop{
		if($this->shopService->existShopChest($mainChest->getPosition()->getWorld()->getFolderName(), Coordinate::fromPosition($mainChest->getPosition())) or ($subchest !== null and $this->shopService->existShopChest($subchest->getPosition()->getWorld()->getFolderName(), Coordinate::fromPosition($subchest->getPosition())))){
			throw new DuplicateShopException("There has already been a shop in the same position.");
		}

		$shop = new Shop(
			$owner->getName(),
			$mainChest->getPosition()->getWorld()->getFolderName(),
			$product,
			Coordinate::fromPosition($sign->getPosition()),
			Coordinate::fromPosition($mainChest->getPosition()),
			($subchest === null) ? null : Coordinate::fromPosition($subchest->getPosition())
		);

		$this->shopRepository->save($shop);
		return $shop;
	}

	public function addSubChest(Shop $shop, Chest $subChest): Shop{
		$this->shopRepository->delete($shop);
		$shop = new Shop(
			$shop->getOwner(),
			$shop->getWorld(),
			$shop->getProduct(),
			$shop->getSign(),
			$shop->getMainChest(),
			Coordinate::fromPosition($subChest->getPosition())
		);
		$this->shopRepository->save($shop);
		return $shop;
	}

	public function removeSubChest(Shop $shop): Shop{
		$this->shopRepository->delete($shop);
		$shop = new Shop(
			$shop->getOwner(),
			$shop->getWorld(),
			$shop->getProduct(),
			$shop->getSign(),
			$shop->getMainChest()
		);
		$this->shopRepository->save($shop);
		return $shop;

	}

	public function destroyShop(Shop $shop) : void{
		$this->shopRepository->delete($shop);
	}
}