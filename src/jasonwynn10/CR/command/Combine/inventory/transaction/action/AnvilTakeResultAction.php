<?php
declare(strict_types=1);
namespace jasonwynn10\CR\command\Combine\inventory\transaction\action;

use jasonwynn10\CR\command\Combine\inventory\transaction\AnvilTransaction;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\Player;

class AnvilTakeResultAction extends InventoryAction {

	public function onAddToTransaction(InventoryTransaction $transaction) : void{
		if($transaction instanceof AnvilTransaction) {
			$transaction->setPrimaryOutput($this->getSourceItem());
		}else{
			throw new \InvalidStateException(get_class($this) . " can only be added to AnvilTransactions");
		}
	}

	public function isValid(Player $source) : bool{
		return true;
	}

	public function execute(Player $source) : bool{
		return true;
	}

	public function onExecuteSuccess(Player $source) : void{

	}

	public function onExecuteFail(Player $source) : void{

	}
}