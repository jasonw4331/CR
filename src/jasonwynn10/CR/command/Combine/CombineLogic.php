<?php
declare(strict_types=1);

namespace jasonwynn10\CR\command\Combine;

use jasonwynn10\CR\Main;
use pocketmine\block\Anvil;
use pocketmine\block\Block;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\PlayerInventory;
use pocketmine\Player;

class CombineLogic implements Listener {
	/** @var int[] $ids */
	private static $ids = [];

	/**
	 * CombineLogic constructor.
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * @priority MONITOR
	 * @ignoreCancelled false
	 *
	 * @param InventoryTransactionEvent $event
	 */
	public function onTransactionEvent(InventoryTransactionEvent $event) : void {
		$transaction = $event->getTransaction();
		/** @var CombineInventory $anvilInventory */
		$anvilInventory = null;
		/** @var PlayerInventory $playerInventory */
		$playerInventory = null;
		foreach($transaction->getInventories() as $inventory) {
			if($inventory->getName() === 'Combine') {
				$anvilInventory = $inventory;
			}elseif($inventory instanceof PlayerInventory) {
				$playerInventory = $inventory;
			}
		}
		if(!isset($anvilInventory))
			return;
		$contents = $anvilInventory->getContents(true);
		var_dump($contents);
	}

	/**
	 * @param Player $player
	 */
	public static function sendInventory(Player $player) : void {
		/** @var Anvil $block */
		$block = Block::get(Block::ANVIL)->setComponents(floor($player->x), floor($player->y+2), floor($player->z));
		$inventory = new CombineInventory($block);
		self::$ids[] = $player->addWindow($inventory);
	}
}