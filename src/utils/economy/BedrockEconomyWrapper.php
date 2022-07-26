<?php

namespace famima65536\EconomyCShop\utils\economy;

use Closure;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\query\ErrorCodes;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;

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
					$reason = match($error){
						ErrorCodes::ERROR_CODE_BALANCE_INSUFFICIENT => TransactionFailureReason::BALANCE_INSUFFICIENT(),
                        ErrorCodes::ERROR_CODE_BALANCE_CAP_EXCEEDED => TransactionFailureReason::BALANCE_CAP_EXCEEDED(),
						ErrorCodes::ERROR_CODE_ACCOUNT_NOT_FOUND => TransactionFailureReason::ACCOUNT_NOT_FOUND(),
						default => TransactionFailureReason::UNKNOWN()
					};
					$onFailure($reason);
				}
			}
		));
	}
}