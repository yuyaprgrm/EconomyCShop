<?php declare(strict_types=1);

namespace famima65536\EconomyCShop\utils\economy;

use Closure;

interface EconomyWrapper {
	/**
	 * @param string $from
	 * @param string $to
	 * @param int $amount
	 * @param Closure $onSuccess called when successful
	 * @param Closure $onFailure called when failed
	 * @phpstan-param Closure(TransactionFailureReason) : void $onFailure
	 */
	public function transfer(string $from, string $to, int $amount, Closure $onSuccess, Closure $onFailure): void;
}