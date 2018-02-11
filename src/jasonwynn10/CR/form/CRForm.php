<?php
declare(strict_types=1);
namespace jasonwynn10\CR\form;

use pocketmine\Player;

interface CRForm {
	/**
	 * Called when a player submits the form
	 *
	 * @param Player $player
	 * @param mixed $data
	 */
	public function onSubmit(Player $player, $data) : void;
}