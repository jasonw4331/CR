<?php
declare(strict_types=1);

namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

class MoneyGrantRequestForm extends CRModalForm {

	/** @var string $requester */
	private $requester;

	/** @var float $amount */
	private $amount;

	/**
	 * MoneyGrantRequestForm constructor.
	 *
	 * @param string $requester
	 * @param float  $amount
	 */
	public function __construct(string $requester, float $amount) {
		$this->requester = $requester;
		$this->amount    = $amount;

		parent::__construct();
	}

	protected function setup(Player $player) : void {
		$this->setTitle("Money Requested from " . $this->requester);
		$this->setContent("Grant the requested $" . $this->amount . "?");
	}

	/**
	 * @param Player $player
	 * @param mixed  $data
	 */
	public function onSubmit(Player $player, $data) : void {
		if($data === null) {
			return;
		}

		if($this->getButton1()) {
			$economy = EconomyAPI::getInstance();
			$main    = Main::getInstance();
			$return  = $economy->reduceMoney($main->getPlayerKingdom($player) . "Kingdom", $this->amount, false, "CR");
			if($return === EconomyAPI::RET_SUCCESS) {
				$economy->addMoney($this->requester, $this->amount, false, "CR");
				$main->getMoneyRequestQueue()->remove($this->requester);
				$main->getMoneyRequestQueue()->save();
			}
		}
	}
}