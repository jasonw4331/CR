<?php
declare(strict_types=1);
namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use pocketmine\form\Form;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;

class VoteForm extends MenuForm {
	/**
	 * VoteForm constructor.
	 */
	public function __construct() {
		$options = [];
		foreach(Main::getInstance()->getVoteRanks() as $rank => $data) {
			$options[] =  new MenuOption($rank);
		}
		parent::__construct("Vote", "Choose a class", $options);
	}

	/**
	 * @param Player $player
	 *
	 * @return null|Form
	 */
	public function onSubmit(Player $player) : ?Form {
		return new VoteRankInformationForm($this->getSelectedOption()->getText());
	}
}