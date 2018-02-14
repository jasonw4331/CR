<?php

declare(strict_types=1);

namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use pocketmine\Player;

class VoteForm extends CRSimpleForm {

	protected function setup(Player $player) : void {
		$this->setTitle("Vote");
		$this->setContent("Choose a class");
		foreach(Main::getInstance()->getVoteRanks() as $rank => $data) {
			$this->addButton($rank, -1, "", $rank);
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

		(new VoteRankInformationForm($data))->sendToPlayer($player);
	}

}