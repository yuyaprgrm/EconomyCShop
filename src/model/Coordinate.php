<?php declare(strict_types=1);

namespace famima65536\EconomyCShop\model;

use pocketmine\world\Position;
use pocketmine\world\World;

class Coordinate implements \JsonSerializable {
	public function __construct(
		private int $x,
		private int $y,
		private int $z
	){
	}

	public static function fromPosition(Position $position): self{
		return new Coordinate($position->getFloorX(), $position->getFloorY(), $position->getFloorZ());
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

	/**
	 * @phpstan-return array{ x: int, y: int, z: int }
	 */
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

	public function equals(Coordinate $other): bool{
		return (
			$this->x === $other->x and
			$this->y === $other->y and
			$this->z === $other->z
		);
	}
}