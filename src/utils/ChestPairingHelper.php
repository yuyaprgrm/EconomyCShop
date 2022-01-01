<?php

namespace famima65536\EconomyCShop\utils;

use pocketmine\block\Chest;
use pocketmine\block\tile\Chest as TileChest;
use pocketmine\math\Facing;

class ChestPairingHelper {
	public static function getPairedChest(Chest $block): ?Chest{
		foreach(
			[
				Facing::rotateY($block->getFacing(), true),
				Facing::rotateY($block->getFacing(), false)
			] as $side
		){
			$c = $block->getSide($side);
			if($c instanceof Chest and $c->isSameType($block) and $c->getFacing() === $block->getFacing()){
				$pair = $block->getPosition()->getWorld()->getTile($c->getPosition());
				if($pair instanceof TileChest and !$pair->isPaired()){
					return $c;
				}
			}
		}
		return null;
	}
}