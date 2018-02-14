<?php

declare(strict_types=1);

namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use pocketmine\Player;

class KingdomWarpForm extends CRSimpleForm {

	protected  function setup(Player $player) : void {
		$this->setTitle("Kingdom Warp Menu");
		$this->setContent("What kingdom do you want to warp to?");
		foreach(Main::getInstance()->getKingdomNames() as $kingdom) {
			$this->addButton($kingdom, -1, "", $kingdom);
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

		(new TeleportLocationForm($data))->sendToPlayer($player);
	}

}