<?php

namespace famima65536\EconomyCShop\utils\economy;

use onebone\economyapi\EconomyAPI;

class EconomyAPIWrapper implements EconomyWrapper {

	/**
	 * @inheritDoc
	 */
	public function transfer(string $from, string $to, int $amount): bool{
		$api = EconomyAPI::getInstance();
		if($api->reduceMoney($from, $amount) === EconomyAPI::RET_INVALID){
			return false;
		}

		if($api->addMoney($to, $amount) === EconomyAPI::RET_INVALID){
			$api->addMoney($from, $amount);
			return false;
		}

		return true;
	}
}