<?php declare(strict_types=1);

namespace famima65536\EconomyCShop\utils\economy;

interface EconomyWrapper {
	/**
	 * @param string $from
	 * @param string $to
	 * @param int $amount
	 * @return bool return true iff transaction is successfully completed
	 */
	public function transfer(string $from, string $to, int $amount): bool;
}