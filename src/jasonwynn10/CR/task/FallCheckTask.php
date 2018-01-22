<?php
declare(strict_types=1);
namespace jasonwynn10\CR\task;

use jasonwynn10\CR\Main;
use pocketmine\scheduler\PluginTask;

class FallCheckTask extends PluginTask {
	/** @var int $eid */
	private $eid;

	/**
	 * FallCheckTask constructor.
	 *
	 * @param Main $owner
	 * @param int $eid
	 */
	public function __construct(Main $owner, int $eid) {
		parent::__construct($owner);
		$this->eid = $eid;
	}

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) {
		$entity = $this->getOwner()->getServer()->findEntity($this->eid);
		if($entity !== null) {
			if($entity->isOnGround()) {
				/** @noinspection PhpUndefinedMethodInspection */
				$this->getOwner()->afterCrystalDrop($entity);
				$this->getHandler()->remove();
			}
		}else{
			$this->getHandler()->remove();
		}
	}
}