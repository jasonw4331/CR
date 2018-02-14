<?php

namespace jasonwynn10\CR\object;

use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;

class PosAABB extends AxisAlignedBB {

	/** @var Level $level */
	protected $level;

	/**
	 * PosAABB constructor.
	 *
	 * @param float $minX
	 * @param float $minY
	 * @param float $minZ
	 * @param float $maxX
	 * @param float $maxY
	 * @param float $maxZ
	 * @param Level $level
	 */
	public function __construct(float $minX, float $minY, float $minZ, float $maxX, float $maxY, float $maxZ, Level $level) {
		$this->level = $level;
		parent::__construct($minX, $minY, $minZ, $maxX, $maxY, $maxZ);
	}

	/**
	 * @return Level
	 */
	public function getLevel() : Level {
		return $this->level;
	}

	/**
	 * @param AxisAlignedBB $AABB
	 * @param Level         $level
	 *
	 * @return PosAABB
	 */
	public static function fromObject(AxisAlignedBB $AABB, Level $level) : self {
		return new self($AABB->minX, $AABB->minY, $AABB->minZ, $AABB->maxX, $AABB->maxY, $AABB->maxZ, $level);
	}

}