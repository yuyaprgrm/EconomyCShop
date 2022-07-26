<?php

namespace famima65536\EconomyCShop\repository;

use famima65536\EconomyCShop\model\Coordinate;
use famima65536\EconomyCShop\model\Product;
use famima65536\EconomyCShop\model\Shop;
use famima65536\EconomyCShop\repository\utils\InternalShopMapper;
use pocketmine\item\Item;
use pocketmine\utils\Config;

class JsonShopRepository implements IShopRepository {

	private InternalShopMapper $chestToShopMapper;
	private InternalShopMapper $signToShopMapper;

	public function __construct(private Config $jsonConfig){
		$this->chestToShopMapper = new InternalShopMapper();
		$this->signToShopMapper = new InternalShopMapper();

		$serializedShops = $this->jsonConfig->getAll();
		foreach($serializedShops as $serializedShop){
			/** @phpstan-var array{
			 *   owner: string, 
			 *   world: string, 
			 *   product: array{
			 *   item: array{id: int, damage?: int, count?: int, nbt?: string, nbt_hex?: string, nbt_b64?: string},
			 *     price: int
			 *   },
			 *   sign: array{x: int, y: int, z: int},
			 *   main-chest: array{x: int, y: int, z: int},
			 *   sub-chest?: array{x: int, y: int, z: int}
			 * } $serializedShop
			 **/
			$shop = $this->deserialize($serializedShop);
			$this->map($shop);
		}
	}

	/** 
	 * @phpstan-return array{
	 *   owner: string, 
	 *   world: string, 
	 *   product: array{
	 *     item: Item,
	 *     price: int
	 *   },
	 *   sign: Coordinate,
	 *   main-chest: Coordinate,
	 *   sub-chest?: Coordinate
	 * }
	 */
	private function serialize(Shop $shop): array{
		$serializedShop = [
			"owner" => $shop->getOwner(),
			"world" => $shop->getWorld(),
			"product" => [
				"item" => $shop->getProduct()->getItem(),
				"price" => $shop->getProduct()->getPrice()
			],
			"sign" => $shop->getSign(),
			"main-chest" => $shop->getMainChest(),
		];

		if($shop->getSubChest() !== null){
			$serializedShop["sub-chest"] = $shop->getSubChest();
		}

		return $serializedShop;
	}

	/** 
	 * @phpstan-param array{
	 *   owner: string, 
	 *   world: string, 
	 *   product: array{
	 *     item: array{id: int, damage?: int, count?: int, nbt?: string, nbt_hex?: string, nbt_b64?: string},
	 *     price: int
	 *   },
	 *   sign: array{x: int, y: int, z: int},
	 *   main-chest: array{x: int, y: int, z: int},
	 *   sub-chest?: array{x: int, y: int, z: int}
	 * } $serializedShop
	 */
	private function deserialize(array $serializedShop): Shop{
		$subchest = null;
		if(isset($serializedShop["sub-chest"])){
			$subchest = new Coordinate($serializedShop["sub-chest"]["x"],$serializedShop["sub-chest"]["y"], $serializedShop["sub-chest"]["z"]);
		}
		return new Shop(
			$serializedShop["owner"],
			$serializedShop["world"],
			new Product(
				Item::jsonDeserialize($serializedShop["product"]["item"]),
				$serializedShop["product"]["price"]
			),
			new Coordinate($serializedShop["sign"]["x"],$serializedShop["sign"]["y"], $serializedShop["sign"]["z"]),
			new Coordinate($serializedShop["main-chest"]["x"],$serializedShop["main-chest"]["y"], $serializedShop["main-chest"]["z"]),
			$subchest
		);
	}

	private function map(Shop $shop) : void{
		$mainChest = $shop->getMainChest();
		$this->chestToShopMapper->set($shop->getWorld(), $mainChest->getHash(), $shop);

		if($shop->getSubChest() !== null){
			$subChest = $shop->getSubChest();
			$this->chestToShopMapper->set($shop->getWorld(), $subChest->getHash(), $shop);
		}

		$sign = $shop->getSign();
		$this->signToShopMapper->set($shop->getWorld(), $sign->getHash(), $shop);
	}

	private function unmap(Shop $shop) : void{
		$mainChest = $shop->getMainChest();
		$this->chestToShopMapper->remove($shop->getWorld(), $mainChest->getHash());

		if($shop->getSubChest() !== null){
			$subChest = $shop->getSubChest();
			$this->chestToShopMapper->remove($shop->getWorld(), $subChest->getHash());
		}

		$sign = $shop->getSign();
		$this->signToShopMapper->remove($shop->getWorld(), $sign->getHash());
	}

	public function save(Shop $shop): void{
		$this->jsonConfig->set($shop->getId(), $this->serialize($shop));
		$this->map($shop);
	}

	public function delete(Shop $shop): void{
		$this->unmap($shop);
		$this->jsonConfig->remove($shop->getId());
	}


	public function findBySign(string $world, Coordinate $coordinate): ?Shop{
		return $this->signToShopMapper->get($world, $coordinate->getHash());
	}

	public function findByChest(string $world, Coordinate $coordinate): ?Shop{
		return $this->chestToShopMapper->get($world, $coordinate->getHash());
	}

	public function close(): void{
		$this->jsonConfig->save();
	}
}