<?php
/**
 * Created by PhpStorm.
 * User: AJ
 * Date: 22/09/2017
 * Time: 18:31
 */

namespace Crates;

use pocketmine\event\Listener as PluginListener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;

class Listener implements PluginListener {

	/**
	 * @param PlayerJoinEvent $event
	 */
	public function onJoin(PlayerJoinEvent $event) {
		foreach(Loader::getInstance()->getCrateManager()->getBlockPool() as $block) {
			$block->spawnTo($event->getPlayer());
		}
	}

	/**
	 * @param PlayerInteractEvent $event
	 */
	public function onTouch(PlayerInteractEvent $event) {
		foreach(Loader::getInstance()->getCrateManager()->getBlockPool() as $block) {
			if($block->isTouching($event->getBlock())) {
				$block->getCrate()->execute($block, $event->getPlayer());
				$event->setCancelled();
				break;
			}
		}
	}

}