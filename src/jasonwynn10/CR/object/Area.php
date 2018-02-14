<?php
declare(strict_types=1);

namespace jasonwynn10\CR\object;

use pocketmine\level\Level;
use pocketmine\Player;

class Area extends PosAABB {

	/** @var string $claimer */
	private $claimer = "";

	/** @var int $claimProgress */
	private $claimProgress = 0;

	/** @var string $claimKingdom */
	private $claimKingdom = "";

	/**
	 * Area constructor.
	 *
	 * @param float  $minX
	 * @param float  $minZ
	 * @param float  $maxX
	 * @param float  $maxZ
	 * @param Level  $level
	 * @param string $claimer
	 */
	public function __construct(float $minX, float $minZ, float $maxX, float $maxZ, Level $level, string $claimer = "") {
		parent::__construct(min($minX, $maxX), 0, min($minZ, $maxZ), max($maxX, $minX), $level->getWorldHeight(), max($minZ, $maxZ), $level);
		$this->claimer = $claimer;
		if(!empty($claimer)) {
			$this->claimKingdom  = $claimer;
			$this->claimProgress = 100;
		}
	}

	/**
	 * @return Level
	 */
	public function getLevel() : Level {
		return $this->level;
	}

	/**
	 * @return string
	 */
	public function getClaimer() : string {
		return $this->claimer;
	}

	/**
	 * @param bool $checkCreative
	 *
	 * @return Player[]
	 */
	public function checkForPlayers(bool $checkCreative = true) : array {
		$ret = [];
		foreach($this->level->getPlayers() as $player) {
			if($this->isVectorInXZ($player) and ($player->isSurvival() or !$checkCreative)) {
				$ret[] = $player;
			}
		}
		return $ret;
	}

	/**
	 * @param string $kingdom
	 * @param int    $amt
	 */
	public function addClaimProgress(string $kingdom, int $amt = 10) : void {
		$this->claimProgress += abs($amt);
		$this->claimKingdom  = $kingdom;
		if($this->claimProgress >= 100) {
			$this->claimer       = $this->claimKingdom;
			$this->claimProgress = 100;
		}
	}

	/**
	 * @param int $amt
	 */
	public function lowerClaimProgress(int $amt = 10) : void {
		$this->claimProgress -= abs($amt);
		if($this->claimProgress <= 0) {
			$this->resetClaimProgress();
		}
	}

	public function resetClaimProgress() : void {
		$this->claimProgress = 0;
		$this->claimKingdom  = "";
	}

	/**
	 * @return int
	 */
	public function getClaimProgress() : int {
		return $this->claimProgress;
	}

	/**
	 * @return string
	 */
	public function getClaimKingdom() : string {
		return $this->claimKingdom;
	}

}