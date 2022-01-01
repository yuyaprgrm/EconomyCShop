<?php

namespace famima65536\EconomyCShop\repository;

use famima65536\EconomyCShop\model\Coordinate;
use famima65536\EconomyCShop\model\Product;
use famima65536\EconomyCShop\model\Shop;
use pocketmine\item\Item;
use SQLite3;

class SqliteShopRepository implements IShopRepository {

	public function __construct(private SQLite3 $db){
		$this->initialize();
	}

	private function initialize(): void{
		$this->db->exec("CREATE TABLE IF NOT EXISTS shops(owner TEXT NOT NULL, world TEXT NOT NULL, price INTEGER NOT NULL, item TEXT NOT NULL, sign_x INTEGER NOT NULL, sign_y INTEGER NOT NULL, sign_z INTEGER NOT NULL, mainchest_x INTEGER NOT NULL, mainchest_y INTEGER NOT NULL, mainchest_z INTEGER NOT NULL, subchest_x INTEGER, subchest_y INTEGER, subchest_z INTEGER, UNIQUE (sign_x, sign_y, sign_z), UNIQUE (mainchest_x, mainchest_y, mainchest_z), UNIQUE (subchest_x, subchest_y, subchest_z));");
	}

	/**
	 * @inheritDoc
	 */
	public function save(Shop $shop): void{
		$stmt = $this->db->prepare("REPLACE INTO shops(owner, world, price, item, sign_x, sign_y, sign_z, mainchest_x, mainchest_y, mainchest_z, subchest_x, subchest_y, subchest_z) VALUES (:owner, :world, :price, :item, :sign_x, :sign_y, :sign_z, :mainchest_x, :mainchest_y, :mainchest_z, :subchest_x, :subchest_y, :subchest_z);");
		$stmt->bindValue(":owner", $shop->getOwner(), SQLITE3_TEXT);
		$stmt->bindValue(":world", $shop->getWorld(), SQLITE3_TEXT);
		$stmt->bindValue(":price", $shop->getProduct()->getPrice(), SQLITE3_INTEGER);
		$stmt->bindValue(":item", json_encode($shop->getProduct()->getItem(), JSON_PRETTY_PRINT), SQLITE3_TEXT);
		$stmt->bindValue(":sign_x", $shop->getSign()->getX(), SQLITE3_INTEGER);
		$stmt->bindValue(":sign_y", $shop->getSign()->getY(), SQLITE3_INTEGER);
		$stmt->bindValue(":sign_z", $shop->getSign()->getZ(), SQLITE3_INTEGER);
		$stmt->bindValue(":mainchest_x", $shop->getMainChest()->getX(), SQLITE3_INTEGER);
		$stmt->bindValue(":mainchest_y", $shop->getMainChest()->getY(), SQLITE3_INTEGER);
		$stmt->bindValue(":mainchest_z", $shop->getMainChest()->getZ(), SQLITE3_INTEGER);
		$subchest = $shop->getSubChest();
		if($subchest === null){
			$stmt->bindValue(":subchest_x", null, SQLITE3_NULL);
			$stmt->bindValue(":subchest_y", null, SQLITE3_NULL);
			$stmt->bindValue(":subchest_z", null, SQLITE3_NULL);
		}else{
			$stmt->bindValue(":subchest_x", $subchest->getX(), SQLITE3_INTEGER);
			$stmt->bindValue(":subchest_y", $subchest->getY(), SQLITE3_INTEGER);
			$stmt->bindValue(":subchest_z", $subchest->getZ(), SQLITE3_INTEGER);
		}
		$stmt->execute();
	}

	/**
	 * @inheritDoc
	 */
	public function delete(Shop $shop): void{
		$stmt = $this->db->prepare("DELETE FROM shops WHERE sign_x = :sign_x AND sign_y = :sign_y AND sign_z = :sign_z");
		$stmt->bindValue(":sign_x", $shop->getSign()->getX(), SQLITE3_INTEGER);
		$stmt->bindValue(":sign_y", $shop->getSign()->getY(), SQLITE3_INTEGER);
		$stmt->bindValue(":sign_z", $shop->getSign()->getZ(), SQLITE3_INTEGER);
		$stmt->execute();
	}

	/**
	 * @inheritDoc
	 */
	public function findBySign(string $world, Coordinate $coordinate): ?Shop{
		$stmt = $this->db->prepare("SELECT owner, price, item, mainchest_x, mainchest_y, mainchest_z, subchest_x, subchest_y, subchest_z FROM shops WHERE world = :world AND sign_x = :sign_x AND sign_y = :sign_y AND sign_z = :sign_z");
		$stmt->bindValue(":world", $world, SQLITE3_TEXT);
		$stmt->bindValue(":sign_x", $coordinate->getX(), SQLITE3_INTEGER);
		$stmt->bindValue(":sign_y", $coordinate->getY(), SQLITE3_INTEGER);
		$stmt->bindValue(":sign_z", $coordinate->getZ(), SQLITE3_INTEGER);
		$result = $stmt->execute();
		$array = $result->fetchArray(SQLITE3_ASSOC);
		if($array !== false){
			$subchest = null;
			if($array["subchest_x"] !== null){
				$subchest = new Coordinate($array["subchest_x"], $array["subchest_y"], $array["subchest_z"]);
			}
			return new Shop(
				$array["owner"],
				$world,
				new Product(
					Item::jsonDeserialize(json_decode($array["item"], true)),
					$array["price"]
				),
				$coordinate,
				new Coordinate($array["mainchest_x"], $array["mainchest_y"], $array["mainchest_z"]),
				$subchest
			);
		}
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function findByChest(string $world, Coordinate $coordinate): ?Shop{

		$stmt = $this->db->prepare("SELECT owner, world, price, item, sign_x, sign_y, sign_z, mainchest_x, mainchest_y, mainchest_z, subchest_x, subchest_y, subchest_z FROM shops WHERE world = :world AND ((mainchest_x = :chest_x AND mainchest_y = :chest_y AND mainchest_z = :chest_z) OR (subchest_x = :chest_x AND subchest_y = :chest_y AND subchest_z = :chest_z))");
		$stmt->bindValue(":world", $world, SQLITE3_TEXT);
		$stmt->bindValue(":chest_x", $coordinate->getX(), SQLITE3_INTEGER);
		$stmt->bindValue(":chest_y", $coordinate->getY(), SQLITE3_INTEGER);
		$stmt->bindValue(":chest_z", $coordinate->getZ(), SQLITE3_INTEGER);
		$result = $stmt->execute();
		$array = $result->fetchArray(SQLITE3_ASSOC);
		if($array !== false){
			$subchest = null;
			if($array["subchest_x"] !== null){
				$subchest = new Coordinate($array["subchest_x"], $array["subchest_y"], $array["subchest_z"]);
			}
			return new Shop(
				$array["owner"],
				$world,
				new Product(
					Item::jsonDeserialize(json_decode($array["item"], true)),
					$array["price"]
				),
				new Coordinate($array["sign_x"], $array["sign_y"], $array["sign_z"]),
				new Coordinate($array["mainchest_x"], $array["mainchest_y"], $array["mainchest_z"]),
				$subchest
			);
		}
		return null;
	}

	public function close(): void{
		$this->db->close();
	}
}