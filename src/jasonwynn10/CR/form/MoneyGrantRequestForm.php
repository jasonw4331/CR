<?php
declare(strict_types=1);
namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use onebone\economyapi\EconomyAPI;
use pocketmine\form\Form;
use pocketmine\form\ModalForm;
use pocketmine\Player;

class MoneyGrantRequestForm extends ModalForm {
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
		$this->amount = $amount;
		parent::__construct("Money Requested from ".$requester, "Grant the requested $".$amount."?");
	}

	/**
	 * @param Player $player
	 *
	 * @return null|Form
	 */
	public function onSubmit(Player $player) : ?Form {
		if($this->getChoice()) {
			$economy = EconomyAPI::getInstance();
			$main = Main::getInstance();
			$return = $economy->reduceMoney($main->getPlayerKingdom($player)."Kingdom", $this->amount, false, "CR");
			if($return === EconomyAPI::RET_SUCCESS) {
				$economy->addMoney($this->requester, $this->amount, false, "CR");
				$main->getMoneyRequestQueue()->remove($this->requester);
				$main->getMoneyRequestQueue()->save();
			}
		}
		return null;
	}
}