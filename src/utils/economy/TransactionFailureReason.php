<?php

namespace famima65536\EconomyCShop\utils\economy;

use pocketmine\utils\EnumTrait;

/**
 * This doc-block is generated automatically, do not modify it manually.
 * This must be regenerated whenever registry members are added, removed or changed.
 * @see build/generate-registry-annotations.php
 * @generate-registry-docblock
 *
 * @method static TransactionFailureReason ACCOUNT_NOT_FOUND()
 * @method static TransactionFailureReason BALANCE_CAP_EXCEEDED()
 * @method static TransactionFailureReason BALANCE_INSUFFICIENT()
 * @method static TransactionFailureReason CAPITAL_PLAYER_OFFLINE()
 * @method static TransactionFailureReason UNKNOWN()
 */
final class TransactionFailureReason{
    use EnumTrait;

    protected static function setup() : void{
        self::registerAll(
            new self("account_not_found"),
            new self("balance_insufficient"),
            new self("balance_cap_exceeded"),
            new self("capital_player_offline"),
            new self("unknown")
        );
    }
}