<?php

declare(strict_types=1);

namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use pocketmine\level\Position;
use pocketmine\Player;

class TeleportLocationForm extends CRSimpleForm {

	/** @var bool $allowClose */
	private $allowClose = true;

	/** @var string */
	private $kingdom;

	/**
	 * StartingLocationForm constructor.
	 *
	 * @param string $kingdom
	 * @param bool   $allowClose
	 */
	public function __construct(string $kingdom, bool $allowClose = true) {
		$this->allowClose = $allowClose;
		$this->kingdom = $kingdom;

		parent::__construct();
	}

	protected function setup(Player $player) : void {
		$this->setTitle("Starting Location");
		$this->setContent("Choose a location to begin your adventure!");
		foreach(Main::getInstance()->getConfig()->getNested("Kingdoms." . $this->kingdom, []) as $name => $posArr) {
			$this->addButton($name, -1, "", $name);
		}
	}

	/**
	 * @param Player $player
	 * @param mixed  $data
	 */
	public function onSubmit(Player $player, $data) : void {
		if($data === null) {
			if(!$this->allowClose) {
				(new TeleportLocationForm(Main::getInstance()->getPlayerKingdom($player), false))->sendToPlayer($player);
			}
			return;
		}

		$plugin  = Main::getInstance();
		$kingdom = $plugin->getPlayerKingdom($player);
		$posArr  = $plugin->getConfig()->getNested("Kingdoms." . $kingdom . "." . $data, []);
		if(!empty($posArr)) {
			$level = $player->getServer()->getLevelByName($posArr["level"]);
			if($level === null) {
				Main::getInstance()->getLogger()->debug("Invalid Level '{$posArr["level"]}'!");
				(new TeleportLocationForm($kingdom, $this->allowClose))->sendToPlayer($player);
				return;
			}
			$player->teleport(new Position($posArr["x"], $posArr["y"], $posArr["z"], $level));
		} else {
			Main::getInstance()->getLogger()->debug("Empty Teleport Array!");
			(new TeleportLocationForm($kingdom, $this->allowClose))->sendToPlayer($player);
			return;
		}
	}

}