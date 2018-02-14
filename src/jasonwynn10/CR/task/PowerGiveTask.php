<?php
declare(strict_types=1);

namespace jasonwynn10\CR\task;

use pocketmine\scheduler\PluginTask;

class PowerGiveTask extends PluginTask {

	public function onRun(int $currentTick) {
		/** @noinspection PhpUndefinedMethodInspection */
		$this->getOwner()->givePowerFromAreas();
		$this->getOwner()->getLogger()->debug("Power has been given to kingdoms currently occupying areas");
	}
}