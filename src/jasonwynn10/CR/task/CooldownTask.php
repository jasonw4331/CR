<?php
declare(strict_types=1);
namespace jasonwynn10\CR\task;

use jasonwynn10\CR\EventListener;
use jasonwynn10\CR\Main;
use pocketmine\scheduler\PluginTask;

class CooldownTask extends PluginTask {
	/** @var int $id */
	private $id;

	/**
	 * CooldownTask constructor.
	 *
	 * @param Main $owner
	 * @param int $id
	 */
	public function __construct(Main $owner, int $id) {
		parent::__construct($owner);
		$this->id = $id;
	}

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) {
		EventListener::setCooldown($this->id, true);
	}
}