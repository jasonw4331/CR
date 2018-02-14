<?php
declare(strict_types=1);

namespace jasonwynn10\CR\task;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;

class EnvoyDropTask extends PluginTask {

	/** @var int $dropTicks */
	private $dropTicks;

	/** @var bool $first */
	private $first = true;

	/**
	 * EnvoyDropTask constructor.
	 *
	 * @param Plugin $owner
	 * @param int    $dropTicks
	 */
	public function __construct(Plugin $owner, int $dropTicks) {
		parent::__construct($owner);
		$this->dropTicks = $dropTicks;
		$this->first     = true;
	}

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) {
		if($this->first) {
			$this->dropTicks = $currentTick + ($this->dropTicks * 20 * 60);
			/** @noinspection PhpUndefinedMethodInspection */
			$this->getOwner()->updateDropTime($this->dropTicks, $currentTick);
			$this->first = false;
			return;
		}
		/** @noinspection PhpUndefinedMethodInspection */
		$this->getOwner()->updateDropTime($this->dropTicks, $currentTick);
		if($currentTick >= $this->dropTicks) {
			/** @noinspection PhpUndefinedMethodInspection */
			$this->getOwner()->dropEnvoys();
		}
	}
}