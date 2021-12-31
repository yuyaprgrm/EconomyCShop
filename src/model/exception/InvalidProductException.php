<?php

namespace famima65536\EconomyCShop\model\exception;


use RuntimeException;

class InvalidProductException extends RuntimeException {
	public const INVALID_PRICE = 0;
	public const INVALID_AMOUNT = 1;
}