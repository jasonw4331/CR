<?php
declare(strict_types=1);
namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use pocketmine\form\Form;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\level\Position;
use pocketmine\Player;

class TeleportLocationForm extends MenuForm {
	/** @var bool $allowClose */
	private $allowClose = true;
	/**
	 * StartingLocationForm constructor.
	 *
	 * @param string $kingdom
	 * @param bool   $allowClose
	 */
	public function __construct(string $kingdom, bool $allowClose = true) {
		$this->allowClose = $allowClose;
		$options = [];
		foreach(Main::getInstance()->getConfig()->getNested("Kingdoms.".$kingdom, []) as $name => $posArr) {
			$options[] = new MenuOption($name);
		}
		parent::__construct("Starting Location", "Choose a location to begin your adventure!", $options);
	}

	/**
	 * @param Player $player
	 *
	 * @return null|Form
	 */
	public function onClose(Player $player) : ?Form {
		return $this->allowClose ? null : new self(Main::getInstance()->getPlayerKingdom($player), false);
	}

	/**
	 * @param Player $player
	 *
	 * @return null|Form
	 */
	public function onSubmit(Player $player) : ?Form {
		$plugin = Main::getInstance();
		$option = $this->getSelectedOption()->getText();
		$kingdom = $plugin->getPlayerKingdom($player);
		$posArr = $plugin->getConfig()->getNested("Kingdoms.".$kingdom.".".$option, []);
		if(!empty($posArr)) {
			$level = $player->getServer()->getLevelByName($posArr["level"]);
			if($level === null) {
				Main::getInstance()->getLogger()->debug("Invalid Level '{$posArr["level"]}'!");
				return new self($kingdom, $this->allowClose);
			}
			$player->teleport(new Position($posArr["x"], $posArr["y"], $posArr["z"], $level));
			return null;
		}else{
			Main::getInstance()->getLogger()->debug("Empty Teleport Array!");
			return new self($kingdom, $this->allowClose);
		}
	}
}