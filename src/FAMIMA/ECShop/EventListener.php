<?php

namespace FAMIMA\ECShop;

use pocketmine\event\Listener;
use pocketmine\event\block\{SignChangeEvent, BlockBreakEvent};
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\TextFormat as TF;
use pocketmine\item\Item;
use pocketmine\level\Position;


use FAMIMA\ECShop\EconomyCShop;


class EventListener implements Listener
{
	private $ecshop;
	private $playerxyz;

	const ECS = TF::WHITE."[".TF::GREEN."ECS".TF::WHITE."]";

	public function __construct(EconomyCShop $plugin)
	{
		$this->ecshop = $plugin;
		$plugin->server->getPluginManager()->registerEvents($this, $plugin);
		foreach($plugin->server->getOnlinePlayers() as $p)
		{
			$this->playerxyz[$p->getName()] = "0.0.0";
		}
	}

	public function onJoin(PlayerJoinEvent $e)
	{
		$this->playerxyz[$e->getPlayer()->getName()] = "0.0.0";
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
					$e->setLine(0, $p->getName());
					$e->setLine(1, "price:".$lines[1]);
					$e->setLine(2, "amount:".$lines[2]);
					$e->setLine(3, $item->getName());

					if ($this->ecshop->isShopExists($pos))
					{
						$this->ecshop->updateChestShopData($pos, $p->getName(), $item, $lines[1]);
					}else{
						$this->ecshop->createChestShop($this->ecshop->getChests($pos), $pos, $p->getName(), $item, $lines[1]);
						$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message1"));
					}
				}else{
					$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message2"));
				}
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
				if($e->getAction() === 1)$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message3"));
			}else{
				$chestpos = new Position($shopdata["cx"], $shopdata["cy"], $shopdata["cz"], $this->ecshop->server->getLevelByName($shopdata["levelname"]));
				if($this->ecshop->isExistChestInItem($chestpos, $item = Item::get($shopdata["itemid"], $shopdata["itemmeta"], $shopdata["itemamount"])))
				{
					if(($inv = $p->getInventory())->canAddItem($item))
					{
						if($this->playerxyz[$n] === $b->x.".".$b->y.".".$b->z)
						{
							if($this->ecshop->onBuy($shopdata["owner"], $n, $shopdata["price"]))
							{
								$this->ecshop->removeChestInItem($chestpos, $item);
							$inv->addItem($item);
							$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message8", ["%item" => $item->getName(), "%amount" => $item->getCount()]));
							}else{
								$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message6"));
							}
						}else{
							$this->playerxyz[$n] = $b->x.".".$b->y.".".$b->z;
							$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message9", ["%item" => $item->getName(), "%price" => $shopdata["price"]]));
						}
					}else{
						$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message4"));
					}
				}else{
					$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message5"));
				}
			}
		}else if($this->ecshop->isShopChestExists($b))
		{
			if($e->getAction() === 1)
			{
				if($this->ecshop->getShopData($b)["owner"] !== $n)
				{
					$e->setCancelled();
					$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message7"));
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
				$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message10"));
				$e->setCancelled();
			}else{
				$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message12"));
				$this->ecshop->removeShop(new Position($sdata["sx"], $sdata["sy"], $sdata["sz"], $this->ecshop->server->getLevelByName($sdata["levelname"])));
			}
		}
		if($this->ecshop->isShopChestExists($b))
		{
			if(($sdata = $this->ecshop->getShopData($b))["owner"] !== $n)
			{
				$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message11"));
				$e->setCancelled();
			}else{
				$p->sendMessage(self::ECS.$this->ecshop->getMessage("Message12"));
				$this->ecshop->removeShop(new Position($sdata["sx"], $sdata["sy"], $sdata["sz"], $this->ecshop->server->getLevelByName($sdata["levelname"])));
			}
		}
	}
}
