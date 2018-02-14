<?php

declare(strict_types=1);

namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

class MoneyRequestForm extends CRCustomForm {

	protected function setup(Player $player) : void {
		$this->setTitle("Request Money");
		$this->addSlider("Requested Amount", 0, (int) EconomyAPI::getInstance()->myMoney(Main::getInstance()->getPlayerKingdom($player) . "Kingdom"), 5, 10);
	}

	/**
	 * @param Player $player
	 * @param mixed  $data
	 */
	public function onSubmit(Player $player, $data) : void {
		if($data === null) {
			return;
		}

		Main::getInstance()->addMoneyRequestToQueue($player, $data[0]);
	}

}