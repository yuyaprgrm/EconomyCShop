<?php

namespace FAMIMA\ECShop;

use pocketmine\event\Listener;
use pocketmine\event\block\{SignChangeEvent, BlockBreakEvent};
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\utils\TextFormat as TF;
use pocketmine\item\Item;
use pocketmine\level\Position;


use FAMIMA\ECShop\EconomyCShop;


class EventListener implements Listener
{
	private $ecshop;
	const ECS = TF::WHITE."[".TF::GREEN."ECS".TF::WHITE."]";

	public function __construct(EconomyCShop $plugin)
	{
		$this->ecshop = $plugin;
		$plugin->server->getPluginManager()->registerEvents($this, $plugin);
	}

	public function SignChange(SignChangeEvent $e)
	{
		$lines = $e->getLines();
		$p = $e->getPlayer();
		//var_dump($lines);
		if($lines[0] == "")
		{
			if(is_numeric($lines[1]) and is_numeric($lines[2])  and (preg_match('/^[0-9]+:[0-9]+$/', $lines[3]) or is_numeric($lines[3])) )
			{
				if(!is_numeric($lines[3]))
				{
					$itemd = explode(":",$lines[3]);
					$item = Item::get($itemd[0], $itemd[1], $lines[2]);
				}else{
					$item =Item::get($lines[3], 0, $lines[2]);
				}
				$b = $e->getBlock();
				if($this->ecshop->isExistsChests($pos = $b))
				{
					$this->ecshop->createChestShop($this->ecshop->getChests($pos), $pos, $p->getName(), $item, $lines[1]);
					$p->sendMessage(self::ECS.TF::GREEN."EconomyCShopの作成が完了しました");
					$e->setLine(0, $p->getName());
					$e->setLine(1, "price:".$lines[1]);
					$e->setLine(2, "amount:".$lines[2]);
					$e->setLine(3, $item->getName());
				}else{
					$p->sendMessage(self::ECS.TF::RED."Chestが見つかりません!,横にChestがあるか確認してください");
				}
			}else{
				$p->sendMessage(self::ECS.TF::RED."すべての項目を書き込んでください");
			}
		}
	}

	public function onInteract(PlayerInteractEvent $e)
	{
		$b = $e->getBlock();
		$p = $e->getPlayer();
		$n = $p->getName();
		if($this->ecshop->isShopExists($b))
		{
			$shopdata = $this->ecshop->getShopData($b);
			//var_dump($shopdata);
			//var_dump($e->getAction());
			if($n === $shopdata["owner"])
			{
				if($e->getAction() === 1)$p->sendMessage(self::ECS.TF::RED."これはあなたのSHOPです");
			}else{
				$chestpos = new Position($shopdata["cx"], $shopdata["cy"], $shopdata["cz"], $this->ecshop->server->getLevelByName($shopdata["levelname"]));
				if($this->ecshop->isExistChestInItem($chestpos, $item = Item::get($shopdata["itemid"], $shopdata["itemmeta"], $shopdata["itemamount"])))
				{
					if($this->ecshop->onBuy($shopdata["owner"], $n, $shopdata["price"]))
					{
						if(($inv = $p->getInventory())->canAddItem($item))
						{
							$this->ecshop->removeChestInItem($chestpos, $item);
							$inv->addItem($item);
							$p->sendMessage(self::ECS.TF::GOLD.$item->getName().TF::GREEN."を".TF::AQUA.$item->getCount()."個".TF::GREEN."購入しました");
						}else{
							$p->sendMessage(self::ECS.TF::RED."インベントリにアイテムが追加できません");
						}
					}else{
						$p->sendMessage(self::ECS.TF::RED."お金が足りないため購入できませんでした");
					}
				}else{
					$p->sendMessage(self::ECS.TF::RED."Chestにアイテムがありません, 補充してもらいましょう(´・ω・｀)");
				}
			}
		}else if($this->ecshop->isShopChestExists($b))
		{
			if($e->getAction() === 1)
			{
				if($this->ecshop->getShopData($b)["owner"] !== $n)
				{
					$e->setCancelled();
					$p->sendMessage(self::ECS.TF::RED."あなたはこのchestを開けることができません!");
				}
			}
		}
	}

	public function onBreak(BlockBreakEvent $e)
	{
		$p = $e->getPlayer();
		$n = $p->getName();
		$b = $e->getBlock();
		if($this->ecshop->isShopExists($b))
		{
			if(($sdata = $this->ecshop->getShopData($b))["owner"] !== $n)
			{
				$p->sendMessage(self::ECS.TF::RED."あなたはこのShopを破壊することができません");
				$e->setCancelled();
			}else{
				$p->sendMessage(self::ECS.TF::RED."Shopを閉店しました");
				$this->ecshop->removeShop(new Position($sdata["sx"], $sdata["sy"], $sdata["sz"], $this->ecshop->server->getLevelByName($sdata["levelname"])));
			}
		}
		if($this->ecshop->isShopChestExists($b))
		{
			if(($sdata = $this->ecshop->getShopData($b))["owner"] !== $n)
			{
				$p->sendMessage(self::ECS.TF::RED."あなたはこのChestを破壊することができません");
				$e->setCancelled();
			}else{
				$p->sendMessage(self::ECS.TF::RED."Shopを閉店しました");
				$this->ecshop->removeShop(new Position($sdata["sx"], $sdata["sy"], $sdata["sz"], $this->ecshop->server->getLevelByName($sdata["levelname"])));
			}
		}
	}
}
