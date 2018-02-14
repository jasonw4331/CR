<?php
/**
 * Created by PhpStorm.
 * User: AJ
 * Date: 22/09/2017
 * Time: 20:34
 */

namespace Crates\task;

use Crates\Loader;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class RemoveCrateTask extends Task {

	/** @var FloatingTextParticle */
	private $particle;

	/** @var Player */
	private $player;

	/**
	 * RemoveCrateTask constructor.
	 *
	 * @param FloatingTextParticle $particle
	 * @param Player               $player
	 */
	public function __construct(FloatingTextParticle $particle, Player $player) {
		$this->particle = $particle;
		$this->player   = $player;
	}

	public function onRun($currentTick) {
		$this->particle->setInvisible();
		foreach($this->particle->encode() as $packet) {
			$this->player->dataPacket($packet);
		}
		Loader::getInstance()->getSessionManager()->getSession($this->player)->setInCrate(false);
	}

}