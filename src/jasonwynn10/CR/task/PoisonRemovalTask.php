<?php
declare(strict_types=1);

namespace jasonwynn10\CR\task;

use jasonwynn10\CR\EventListener;
use jasonwynn10\CR\Main;
use pocketmine\scheduler\PluginTask;

class PoisonRemovalTask extends PluginTask {

	/** @var int $key */
	private $key;

	/**
	 * PoisonRemovalTask constructor.
	 *
	 * @param Main $owner
	 * @param int  $key
	 */
	public function __construct(Main $owner, int $key) {
		parent::__construct($owner);
		$this->key = $key;
	}

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) {
		EventListener::removeAABB($this->key);
	}
}