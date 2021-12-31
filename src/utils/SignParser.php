<?php

namespace famima65536\EconomyCShop\utils;

use famima65536\EconomyCShop\model\exception\InvalidProductException;
use famima65536\EconomyCShop\model\Product;
use pocketmine\block\utils\SignText;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\SingletonTrait;

class SignParser {
	use SingletonTrait;
	public function canParse(SignText $signText): bool{
		$top = $signText->getLine(0);
		$sPrice = $signText->getLine(1);
		$sAmount = $signText->getLine(2);
		$sItem = $signText->getLine(3);
		if(
			$top !== "" or
			$sPrice === "" or
			$sAmount === "" or
			$sItem === ""
		)
			return false;

		if(
			!is_numeric($sPrice) or
			!is_numeric($sAmount)
		)
			return false;
		return true;
	}

	/**
	 * @param SignText $signText
	 * @return Product|null
	 * @throws InvalidProductException
	 */
	public function parse(SignText $signText): ?Product{
		if(!$this->canParse($signText))
			return null;
		$price = intval($signText->getLine(1));
		$amount = intval($signText->getLine(2));
		$sItem = $signText->getLine(3);
		$item = StringToItemParser::getInstance()->parse($sItem);
		if($item === null and preg_match('/^[0-9]+:[0-9]+$/', $sItem)){
			[$id, $meta] = array_map(fn($x):int => intval($x), explode(":", $sItem));
			$item = ItemFactory::getInstance()->get($id, $meta);
		}

		if($item === null)
			return null;

		$item->setCount($amount);

		return new Product($item, $price);
	}
}