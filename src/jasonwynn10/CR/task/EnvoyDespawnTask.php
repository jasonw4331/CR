<?php
declare(strict_types=1);

namespace jasonwynn10\CR\task;

use jasonwynn10\CR\Main;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

class EnvoyDespawnTask extends PluginTask {

	/** @var int $eid */
	private $eid;

	/**
	 * EnvoyDespawnTask constructor.
	 *
	 * @param Main $owner
	 * @param int  $eid
	 */
	public function __construct(Main $owner, int $eid) {
		parent::__construct($owner);
		$this->eid = $eid;
	}

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) {
		$entity = Server::getInstance()->findEntity($this->eid);
		if($entity !== null) {
			$entity->flagForDespawn();
		}
	}
}