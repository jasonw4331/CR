<?php
declare(strict_types=1);
namespace jasonwynn10\CR\task;

use jasonwynn10\CR\Main;
use pocketmine\form\Form;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class DelayedFormTask extends PluginTask {
	/** @var Form $form */
	private $form;
	/** @var string $player */
	private $player;

	/**
	 * DelayedFormTask constructor.
	 *
	 * @param Main   $owner
	 * @param Form   $form
	 * @param Player $player
	 */
	public function __construct(Main $owner, Form $form, Player $player) {
		parent::__construct($owner);
		$this->form = $form;
		$this->player = $player->getName();
	}

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) {
		$player = $this->getOwner()->getServer()->getPlayerExact($this->player);
		if($player !== null) {
			$player->sendForm($this->form, true);
			$this->getOwner()->getLogger()->debug(get_class($this)." sent to Player ".$player->getName());
		}
	}
}