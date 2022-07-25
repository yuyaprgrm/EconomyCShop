<?php

namespace famima65536\EconomyCShop\utils\economy;

use Closure;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\libSQL\context\ClosureContext;

class BedrockEconomyWrapper implements EconomyWrapper {

	/**
	 * @inheritDoc
	 */
	public function transfer(string $from, string $to, int $amount, Closure $onSuccess, Closure $onFailure): void{
		BedrockEconomyAPI::legacy()->transferFromPlayerBalance($from, $to, $amount, ClosureContext::create(
			static function (bool $success, Closure $_, ?string $error) use($onSuccess, $onFailure){
				if($success){
					$onSuccess();
				}else{
					$onFailure();
				}
			}
		));
	}
}