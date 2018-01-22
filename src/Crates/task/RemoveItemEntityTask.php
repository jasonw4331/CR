<?php
declare(strict_types=1);
namespace Crates\task;

use pocketmine\entity\Entity;
use pocketmine\scheduler\Task;

class RemoveItemEntityTask extends Task {
	/** @var Entity $entity */
	private $entity;

	/**
	 * RemoveItemEntityTask constructor.
	 *
	 * @param Entity $entity
	 */
	public function __construct(Entity $entity) {
		$this->entity = $entity;
	}

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) {
		if(!$this->entity->isClosed() and !$this->entity->isFlaggedForDespawn())
			$this->entity->flagForDespawn();
	}
}