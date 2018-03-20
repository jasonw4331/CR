<?php
declare(strict_types=1);
namespace jasonwynn10\CR\command\Combine\inventory\transaction;

use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\Player;

class AnvilTransaction extends InventoryTransaction {

	/** @var Item[] */
	protected $inputs;
	/** @var Item|null */
	protected $primaryOutput;

	/**
	 * AnvilTransaction constructor.
	 *
	 * @param Player $source
	 * @param InventoryAction[] $actions
	 */
	public function __construct(Player $source, array $actions = []){
		//TODO
		parent::__construct($source, $actions);
	}

	public function setInput(int $index, Item $item) : void{
		//TODO
	}

	public function getInputMap() : array{
		return $this->inputs;
	}

	public function getPrimaryOutput() : ?Item{
		return $this->primaryOutput;
	}

	public function setPrimaryOutput(Item $item) : void{
		if($this->primaryOutput === null){
			$this->primaryOutput = clone $item;
		}elseif(!$this->primaryOutput->equals($item)){
			throw new \RuntimeException("Primary result item has already been set and does not match the current item (expected " . $this->primaryOutput . ", got " . $item . ")");
		}
	}

	public function canExecute() : bool{
		//TODO
		return false;
	}

	protected function callExecuteEvent() : bool{
		// TODO
		return false;
	}

	protected function sendInventories() : void{
		parent::sendInventories();
		//TODO
	}

	public function execute() : bool{
		if(parent::execute()){
			//TODO
			return true;
		}
		return false;
	}
}