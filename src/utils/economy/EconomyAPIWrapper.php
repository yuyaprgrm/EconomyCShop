<?php

namespace famima65536\EconomyCShop\utils\economy;

use Closure;
use onebone\economyapi\EconomyAPI;

class EconomyAPIWrapper implements EconomyWrapper {

	/**
	 * @inheritDoc
	 */
	public function transfer(string $from, string $to, int $amount, Closure $onSuccess, Closure $onFailure): void{
		$api = EconomyAPI::getInstance();
		if($api->reduceMoney($from, $amount) === EconomyAPI::RET_INVALID){
			$onFailure();
			return;
		}

		if($api->addMoney($to, $amount) === EconomyAPI::RET_INVALID){
			$api->addMoney($from, $amount);
			$onFailure();
			return;
		}

		$onSuccess();
		return;
	}
}