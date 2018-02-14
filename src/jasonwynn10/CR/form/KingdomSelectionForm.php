<?php

declare(strict_types=1);

namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use pocketmine\Player;

class KingdomSelectionForm extends CRSimpleForm {

	public function setup(Player $player) : void {
		$this->setTitle("Kingdom Selection");
		$this->setContent("Choose a kingdom to start!");
		foreach(shuffle(Main::getInstance()->getKingdomNames()) as $name) {
			$this->addButton($name, -1, "", $name);
		}
	}

	/**
	 * @param Player $player
	 * @param mixed  $data
	 */
	public function onSubmit(Player $player, $data) : void {
		if($data === null) {
			return;
		}

		if(Main::getInstance()->setPlayerKingdom($player, $data)) {
			(new TeleportLocationForm($data, false))->sendToPlayer($player);
		} else {
			(new KingdomSelectionForm())->sendToPlayer($player);
		}
	}

}