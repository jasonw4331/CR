<?php
declare(strict_types=1);

namespace jasonwynn10\CR\command\Combine;

use jasonwynn10\CR\Main;
use pocketmine\block\Anvil;
use pocketmine\block\Block;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\AnvilInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;

class CombineLogic implements Listener {
	/** @var string[] $sent */
	private static $sent;

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
			if($inventory instanceof AnvilInventory) {
				$anvilInventory = $inventory;
			}elseif($inventory instanceof PlayerInventory) {
				$playerInventory = $inventory;
			}
		}
		if(!isset($anvilInventory) or !in_array($playerInventory->getName(), self::$sent))
			return;
		unset(self::$sent[array_search($playerInventory->getName(), self::$sent)]);
		$contents = $anvilInventory->getContents(true);
		var_dump($contents); // TODO remove

		$pos = $anvilInventory->getHolder()->asPosition();
		$pos->getLevel()->sendBlocks([$playerInventory->getHolder()], [Block::get(Block::AIR, 0, $pos)], UpdateBlockPacket::FLAG_ALL_PRIORITY);
	}

	/**
	 * @param Player $player
	 */
	public static function sendInventory(Player $player) : void {
		/** @var Anvil $block */
		$block = Block::get(Block::ANVIL, Anvil::TYPE_VERY_DAMAGED, new Position((int) floor($player->x), (int) floor($player->y+2), (int) floor($player->z), $player->getLevel()));
		$player->getLevel()->sendBlocks([$player], [$block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
		$block->onActivate(Item::get(Item::AIR), $player);
		self::$sent[] = $player->getName();
	}
}