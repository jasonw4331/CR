<?php
declare(strict_types=1);

namespace jasonwynn10\CR\task;

use jasonwynn10\CR\object\Area;
use pocketmine\scheduler\PluginTask;

class ClaimProgressDisplayTask extends PluginTask {

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) {
		/** @var Area $area */
		/** @noinspection PhpUndefinedMethodInspection */
		$this->getOwner()->sendTips();
	}
}