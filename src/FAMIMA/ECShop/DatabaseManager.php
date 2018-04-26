<?php

namespace FAMIMA\ECShop;

class DatabaseManager
{

	public $db;

	public function __construct($path)
	{
		$this->db = new \SQLite3($path);
		$this->db->exec(
		"CREATE TABLE IF NOT EXISTS shop(
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			owner TEXT NOT NULL,
			cx INTEGER NOT NULL,
			cy INTEGER NOT NULL,
			cz INTEGER NOT NULL,
			sx INTEGER NOT NULL,
			sy INTEGER NOT NULL,
			sz INTEGER NOT NULL,
			itemid INTEGER NOT NULL,
			itemmeta INTEGER NOT NULL,
			itemamount INTEGER NOT NULL,
			price INTEGER NOT NULL,
			levelname TEXT NOT NULL
		)");
	}

	public function createChestShop($cx, $cy, $cz, $sx, $sy, $sz, $owner, $itemid, $itemmeta, $itemamount, $price, $worldname)
	{
		$this->db->exec("INSERT INTO
			shop(owner, cx, cy, cz, sx, sy, sz, itemid, itemmeta, itemamount, price, levelname)
			VALUES(\"$owner\", $cx, $cy, $cz, $sx, $sy, $sz, $itemid, $itemmeta, $itemamount, $price, \"$worldname\")");
	}

	public function updateChestShopData($sx, $sy, $sz, $owner, $itemid, $itemmeta, $itemamount, $price, $worldname)
	{
		$this->db->exec("UPDATE shop SET owner = \"$owner\", itemid = $itemid, itemmeta = $itemmeta, itemamount = $itemamount, price = $price WHERE sx = $sx and sy = $sy and sz = $sz and levelname = \"$worldname\"");
	}

	public function isShopExists($x, $y, $z, $levelname)
	{
		$sql = $this->db->prepare("SELECT * from shop WHERE sx = :x and sy = :y and sz = :z and levelname = :levelname");
		$sql->bindValue(':x', $x, SQLITE3_INTEGER);
		$sql->bindValue(':y', $y, SQLITE3_INTEGER);
		$sql->bindValue(':z', $z, SQLITE3_INTEGER);
		$sql->bindValue(':levelname', $levelname, SQLITE3_TEXT);
		$result = $sql->execute();

		return $result->fetchArray() !== false;
	}

	public function isShopChestExists($x, $y, $z, $levelname)
	{
		$sql = $this->db->prepare("SELECT * from shop WHERE cx = :x and cy = :y and cz = :z and levelname = :levelname");
		$sql->bindValue(':x', $x, SQLITE3_INTEGER);
		$sql->bindValue(':y', $y, SQLITE3_INTEGER);
		$sql->bindValue(':z', $z, SQLITE3_INTEGER);
		$sql->bindValue(':levelname', $levelname, SQLITE3_TEXT);
		$result = $sql->execute();

		return $result->fetchArray() !== false;
	}

	public function getShopData($x, $y, $z, $levelname)
	{
		$sql = $this->db->prepare("SELECT * from shop WHERE ( (sx = :x and sy = :y and sz = :z) or (cx = :x and cy = :y and cz = :z) ) and levelname = :levelname");
		$sql->bindValue(':x', $x, SQLITE3_INTEGER);
		$sql->bindValue(':y', $y, SQLITE3_INTEGER);
		$sql->bindValue(':z', $z, SQLITE3_INTEGER);
		$sql->bindValue(':levelname', $levelname, SQLITE3_TEXT);
		$result = $sql->execute();

		$shop = $result->fetchArray();
		return $shop !== false ? $shop : null;

	}

	public function deleteShop($x, $y, $z, $worldname)
	{
		$this->db->exec("DELETE FROM shop WHERE sx = $x and sy = $y and sz = $z and levelname = \"$worldname\"");
	}
}
