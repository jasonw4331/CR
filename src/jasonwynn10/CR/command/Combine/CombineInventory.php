<?php
declare(strict_types=1);
namespace jasonwynn10\CR\command\Combine;

use pocketmine\inventory\CustomInventory;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;

class CombineInventory extends CustomInventory {
	/** @var Position $holder */
	protected $holder;

	/**
	 * CombineInventory constructor.
	 *
	 * @param Position $pos
	 */
	public function __construct(Position $pos){
		parent::__construct($pos->asPosition());
	}

	public function getNetworkType() : int {
		return WindowTypes::ANVIL;
	}

	public function getName() : string {
		return "Combine";
	}

	public function getDefaultSize() : int {
		return 2; //1 input, 1 material
	}

	public function getHolder() {
		return $this->holder;
	}

	public function onClose(Player $who) : void {
		parent::onClose($who);

		$this->dropContents($this->holder->getLevel(), $this->holder->add(0.5, 0.5, 0.5));
	}
}