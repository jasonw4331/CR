<?php
declare(strict_types=1);
namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use pocketmine\form\Form;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;

class KingdomWarpForm extends MenuForm {
	/**
	 * KingdomWarpForm constructor.
	 */
	public function __construct() {
		$options = [];
		foreach(Main::getInstance()->getKingdomNames() as $kingdom) {
			$options[] = new MenuOption($kingdom);
		}
		parent::__construct("Kingdom Warp Menu", "What kingdom do you want to warp to?", $options);
	}

	/**
	 * @param Player $player
	 *
	 * @return null|Form
	 */
	public function onSubmit(Player $player) : ?Form {
		$option = $this->getSelectedOption()->getText();
		return new TeleportLocationForm($option);
	}
}