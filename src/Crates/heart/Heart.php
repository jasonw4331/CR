<?php
/**
 * Created by PhpStorm.
 * User: AJ
 * Date: 22/09/2017
 * Time: 20:46
 */

namespace Crates\heart;

use Crates\Loader;
use pocketmine\Server;

class Heart {
	/** @var HeartTask[] */
	private $taskPool = [];

	/**
	 * Heart constructor.
	 *
	 * @param Loader $loader
	 */
	public function __construct(Loader $loader) {
		Server::getInstance()->getScheduler()->scheduleRepeatingTask(new HeartBeat($this), 1);
	}

	/**
	 * @param HeartTask $task
	 */
	public function startTask(HeartTask $task) {
		$this->taskPool[] = $task;
	}

	/**
	 * @param HeartTask $task
	 */
	public function stopTask(HeartTask $task) {
		if(in_array($task, $this->taskPool)) {
			unset($this->taskPool[array_search($task, $this->taskPool)]);
		}
	}

	public function run() {
		foreach($this->taskPool as $task) {
			$task->run();
		}
	}

}