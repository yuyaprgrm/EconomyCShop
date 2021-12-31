<?php

namespace famima65536\EconomyCShop\model;

class Coordinate {
	public function __construct(
		private int $x,
		private int $y,
		private int $z
	){
	}

	/**
	 * @return int
	 */
	public function getX(): int{
		return $this->x;
	}

	/**
	 * @return int
	 */
	public function getY(): int{
		return $this->y;
	}

	/**
	 * @return int
	 */
	public function getZ(): int{
		return $this->z;
	}
}