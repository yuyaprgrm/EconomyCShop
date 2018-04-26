<?php

namespace FAMIMA\ECShop;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Config;

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
		// $logger->info(TF::AQUA.$plugin."再配布, 二次配布は禁止です. ゲーム内のメッセージはConfigから変更できます");
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

		$config = new Config($dir."Message.yml", Config::YAML,
			[
			"Message1" => TF::GREEN."EconomyCShopの作成が完了しました",
			"Message2" => TF::RED."Chestが見つかりません!,横にChestがあるか確認してください",
			"Message3" => TF::RED."これはあなたのSHOPです",
			"Message4" => TF::RED."インベントリにアイテムが追加できません",
			"Message5" => TF::RED."Chestにアイテムがありません, 補充してもらいましょう(´・ω・｀)",
			"Message6" => TF::RED."お金が足りないため購入できませんでした",
			"Message7" => TF::RED."あなたはこのchestを開けることができません!",
			"Message8" => TF::GOLD.TF::GOLD."%item".TF::GREEN."を".TF::AQUA."%amount"."個".TF::GREEN."購入しました",
			"Message9" => TF::BLUE."%itemを購入しますか?(%price円です)",
			"Message10" => TF::RED."あなたはこのShopを破壊することができません",
			"Message11" => TF::RED."あなたはこのChestを破壊することができません",
			"Message12" => TF::RED."Shopを閉店しました"
			]);
		$this->message = $config->getAll();
		//var_dump($this->message);
	}

	public function MessageReplace(string $str, array $serrep)
	{
		foreach($serrep as $search => $replace)
		{
			$str = str_replace($search, $replace, $str);
		}
		return $str;
	}

	public function getMessage(string $message, $serrep = [])
	{
		return $this->MessageReplace( (isset($this->message[$message])) ? $this->message[$message] : TF::RED."ERROR!メッセージが存在しません", $serrep);
	}

	public function createChestShop($cpos, $spos, $owner, $item, $price)
	{
		$this->db->createChestShop($cpos->x, $cpos->y, $cpos->z, $spos->x, $spos->y, $spos->z,
		$owner, $item->getID(), $item->getDamage(), $item->getCount(), $price, $spos->getLevel()->getName());
	}

	public function updateChestShopData($spos, $owner, $item, $price)
	{
		$this->db->updateChestShopData($spos->x, $spos->y, $spos->z, $owner, $item->getID(), $item->getDamage(), $item->getCount(), $price, $spos->getLevel()->getName());
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
