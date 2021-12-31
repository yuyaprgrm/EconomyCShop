<?php declare(strict_types=1);

namespace famima65536\EconomyCShop\model;

use pocketmine\world\World;

class Coordinate implements \JsonSerializable {
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

	public function jsonSerialize(): array{
		return [
			"x" => $this->x,
			"y" => $this->y,
			"z" => $this->z
		];
	}

	public function getHash(): int{
		return World::blockHash($this->x, $this->y, $this->z);
	}
}