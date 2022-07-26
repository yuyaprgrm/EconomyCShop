<?php

namespace famima65536\EconomyCShop\utils\economy;

use Closure;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\libSQL\context\ClosureContext;
use pocketmine\Server;
use SOFe\Capital\Capital;
use SOFe\Capital\CapitalException;
use SOFe\Capital\LabelSet;
use SOFe\Capital\Schema;

class CapitalWrapper implements EconomyWrapper {

	private Schema\Complete $selector;

    public function __construct(
		private Server $server,
        array $selectorConfig
    ){
		Capital::api("0.1.0", function(Capital $api) use($selectorConfig){
			$this->selector = $api->completeConfig($selectorConfig);
		});
    }

	/**
	 * @inheritDoc
	 */
	public function transfer(string $from, string $to, int $amount, Closure $onSuccess, Closure $onFailure): void{
		Capital::api("0.1.0", function(Capital $api) use($from, $to, $amount, $onSuccess, $onFailure){
			$src = $this->server->getPlayerExact($from);
			$dst = $this->server->getPlayerExact($to);
			if($src === null || $dst === null){
				$onFailure(TransactionFailureReason::CAPITAL_PLAYER_OFFLINE());
				return;
			}
			try {
				yield from $api->pay($src, $dst, $this->selector, $amount, new LabelSet(["reason" => "buy item"]));
				$onSuccess();
			} catch(CapitalException $e) {
				$error = match($e->getCode()){
					CapitalException::SOURCE_UNDERFLOW => TransactionFailureReason::BALANCE_INSUFFICIENT(),
					CapitalException::DESTINATION_OVERFLOW => TransactionFailureReason::BALANCE_CAP_EXCEEDED(),
					CapitalException::NO_SUCH_ACCOUNT => TransactionFailureReason::ACCOUNT_NOT_FOUND(),
					default => TransactionFailureReason::UNKNOWN()
				};
				$onFailure($error);
			}
		});
	}
}