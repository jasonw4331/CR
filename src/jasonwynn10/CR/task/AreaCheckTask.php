<?php
declare(strict_types=1);

namespace jasonwynn10\CR\task;

use pocketmine\scheduler\PluginTask;

class AreaCheckTask extends PluginTask {

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) {
		/** @noinspection PhpUndefinedMethodInspection */
		$this->getOwner()->checkAreas();
		$this->getOwner()->getLogger()->debug("Areas have been checked!");
	}
}