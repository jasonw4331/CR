<?php
declare(strict_types=1);

namespace jasonwynn10\CR\command\Combine;

use jasonwynn10\CR\command\Combine\inventory\CombineInventory;
use jasonwynn10\CR\Main;
use pocketmine\block\Anvil;
use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;

class CombineLogic implements Listener {

	/** @var string[] $sent */
	private static $sent = [];
	/** @var string[] $resend */
	private static $resend = [];

	/**
	 * CombineLogic constructor.
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 */
	public function onDataPacket(DataPacketReceiveEvent $event) {
		/** @var InventoryTransactionPacket $packet */
		$packet = $event->getPacket();
		if($packet::NETWORK_ID === InventoryTransactionPacket::NETWORK_ID) {
			$cancel = false;
			$player = $event->getPlayer();
			foreach($packet->actions as $networkInventoryAction) {
				if($networkInventoryAction === NetworkInventoryAction::SOURCE_TODO) {
					switch($networkInventoryAction->windowId) {
						case NetworkInventoryAction::SOURCE_TYPE_ANVIL_INPUT:
						case NetworkInventoryAction::SOURCE_TYPE_ANVIL_MATERIAL:
							$cancel = true;
							echo "INPUT\n";
						break;
						case NetworkInventoryAction::SOURCE_TYPE_ANVIL_OUTPUT:
							$cancel = true;
							echo "OUTPUT\n";
						break;
						case NetworkInventoryAction::SOURCE_TYPE_ANVIL_RESULT:
							$cancel = true;
							echo "RESULT\n";
						break;
					}
				}
			}
			$event->setCancelled($cancel);
		}
	}

	/**
	 * @param Player $player
	 */
	public static function sendInventory(Player $player) : void {
		$pos = new Position((int) floor($player->x), (int) floor($player->y + 2), (int) floor($player->z), $player->getLevel());
		$original = $player->getLevel()->getBlock($pos);
		self::$resend["$pos->x:$pos->y:$pos->z"] = $original;
		/** @var Anvil $block */
		$block = Block::get(Block::ANVIL, Anvil::TYPE_VERY_DAMAGED, $pos);
		$player->getLevel()->sendBlocks([$player], [$block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
		echo $player->addWindow(new CombineInventory($pos), -10)." is the window ID.\n";
		self::$sent[] = $player->getName();
	}
}