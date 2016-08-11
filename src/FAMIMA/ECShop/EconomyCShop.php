<?php

namespace FAMIMA\ECShop;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

use FAMIMA\ECShop\EventListener;
use FAMIMA\ECShop\DatabaseManager;


class EconomyCShop extends PluginBase
{
	private $db;

	public $server;

	public function onEnable()
	{
		$plugin = "EconomyCShop";
		$logger = $this->getLogger();
		$logger->info(TF::GREEN.$plugin."を起動しました");
		$logger->info(TF::AQUA.$plugin."はオープンソースなプラグインです");
		$this->server = $this->getServer();
		new EventListener($this);
		$dir = $this->getDataFolder();
		@mkdir($dir, 0755);
		$this->db = new DatabaseManager($dir."ECShopPos.sqlite3");

		$logger->info(TF::BLUE."EventListenerとDatabaseManagerを起動しました");
		$logger->info(TF::BLUE."EconomyAPIを読み込んでいます...");
		if(($this->economy = $this->server->getPluginManager()->getPlugin("EconomyAPI")) === null)
		{
			$logger->alert("EconomyAPIが存在しません!, EconomyAPIを導入してください");
			$this->server->getPluginManager()->disablePlugin($this);
		}

	}

	public function createChestShop($cpos, $spos, $owner, $item, $price)
	{
		$this->db->createChestShop($cpos->x, $cpos->y, $cpos->z, $spos->x, $spos->y, $spos->z,
		$owner, $item->getID(), $item->getDamage(), $item->getCount(), $price, $spos->getLevel()->getName());
	}

	public function isShopExists($pos)
	{
		return $this->db->isShopExists($pos->x, $pos->y, $pos->z, $pos->level->getName());
	}

	public function isShopChestExists($pos)
	{
		return $this->db->isShopChestExists($pos->x, $pos->y, $pos->z, $pos->level->getName());
	}

	public function getShopData($pos)
	{
		return $this->db->getShopData($pos->x, $pos->y, $pos->z, $pos->level->getName());
	}

	public function isExistsChests($pos)
	{
		$l = $pos->level;
		$existsdata = false;
		$cpos = [$pos->add(1), $pos->add(-1), $pos->add(0, 0, 1), $pos->add(0, 0, -1)];
		foreach ($cpos as $vector) {
			if($l->getBlock($vector)->getID() === 54)
			{
				$existsdata = true;
			}
		}
		return $existsdata;
	}

	public function getChests($pos)
	{
		$l = $pos->level;
		$posdata = false;
		$cpos = [$pos->add(1), $pos->add(-1), $pos->add(0, 0, 1), $pos->add(0, 0, -1)];
		foreach ($cpos as $vector) {
			if($l->getBlock($vector)->getID() === 54)
			{
				$posdata = $vector;
			}
		}
		return $posdata;
	}

	public function isExistChestInItem($pos, $item)
	{
		return $pos->level->getTile($pos)->getInventory()->contains($item);
	}

	public function removeChestInItem($pos, $item)
	{
		$pos->level->getTile($pos)->getInventory()->removeItem($item);
	}

	public function removeShop($pos)
	{
		$this->db->deleteShop($pos->x, $pos->y, $pos->z, $pos->level->getName());
	}

	public function onBuy($owner, $target, $amount)
	{
		$tmoney = $this->economy->myMoney($target);
		if($tmoney < $amount)
		{
			return false;
		}else{
			$this->economy->reduceMoney($target, $amount);
			$this->economy->addMoney($owner, $amount);
			return true;
		}
	}
}
