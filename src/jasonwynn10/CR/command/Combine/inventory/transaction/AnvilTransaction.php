<?php
declare(strict_types=1);
namespace jasonwynn10\CR\command\Combine\inventory\transaction;

use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\Player;

class AnvilTransaction extends InventoryTransaction {

	public function __construct(Player $source, $actions = []) {
		parent::__construct($source, $actions);
	}
}