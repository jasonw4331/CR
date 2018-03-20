<?php
declare(strict_types=1);

namespace jasonwynn10\CR\command\Combine;

use jasonwynn10\CR\command\Combine\inventory\CombineInventory;
use jasonwynn10\CR\command\Combine\inventory\transaction\action\AnvilTakeResultAction;
use jasonwynn10\CR\command\Combine\inventory\transaction\AnvilTransaction;
use jasonwynn10\CR\Main;
use pocketmine\block\Anvil;
use pocketmine\block\Block;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
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
	/** @var AnvilTransaction $combineTransaction */
	private $combineTransaction;

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
		$packet = $event->getPacket();
		if($packet::NETWORK_ID === InventoryTransactionPacket::NETWORK_ID) {
			$cancel = false;
			$player = $event->getPlayer();
			$combine = false;
			/** @var InventoryAction[] $actions */
			$actions = [];
			/** @var InventoryTransactionPacket $packet */
			foreach($packet->actions as $networkInventoryAction) {
				if($networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_TODO) {
					switch($networkInventoryAction->windowId) {
						case NetworkInventoryAction::SOURCE_TYPE_ANVIL_INPUT:
						case NetworkInventoryAction::SOURCE_TYPE_ANVIL_MATERIAL:
							$cancel = true;
							$actions[] = new SlotChangeAction($player->getWindow($networkInventoryAction->windowId), $networkInventoryAction->inventorySlot, $networkInventoryAction->oldItem, $networkInventoryAction->newItem);
							echo "INPUT\n";
						break;
						case NetworkInventoryAction::SOURCE_TYPE_ANVIL_OUTPUT:
							$cancel = true;
							echo "OUTPUT\n";
						break;
						case NetworkInventoryAction::SOURCE_TYPE_ANVIL_RESULT:
							$cancel = true;
							echo "RESULT\n";
							$packet->combinePart = true;
							$actions[] = new AnvilTakeResultAction($networkInventoryAction->oldItem, $networkInventoryAction->newItem);
						break;
					}
				}
			}
			if(isset($packet->combinePart)){
				if($this->combineTransaction === null){
					$this->combineTransaction = new AnvilTransaction($player, $actions);
				}else{
					foreach($actions as $action){
						$this->combineTransaction->addAction($action);
					}
				}

				if($this->combineTransaction->getPrimaryOutput() !== null){
					//we get the actions for this in several packets, so we can't execute it until we get the result

					$this->combineTransaction->execute();
					$this->combineTransaction = null;
				}
			}elseif($this->combineTransaction !== null){
				$player->getServer()->getLogger()->debug("Got unexpected normal inventory action with incomplete crafting transaction from " . $this->getName() . ", refusing to execute crafting");
				$this->combineTransaction = null;
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
		echo $player->addWindow(new CombineInventory($pos))." is the window ID.\n";
		self::$sent[] = $player->getName();
	}

	/**
	 * @param InventoryCloseEvent $event
	 */
	public function onClose(InventoryCloseEvent $event) {
		$inventory = $event->getInventory();
		if($inventory instanceof CombineInventory) {
			$anvil = $inventory->getHolder();
			$anvil->getLevel()->sendBlocks([$event->getPlayer()], [self::$resend["$anvil->x:$anvil->y:$anvil->z"]], UpdateBlockPacket::FLAG_ALL_PRIORITY);
		}
	}
}