<?php

declare(strict_types=1);

namespace jasonwynn10\CR\form;

use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;

abstract class CRCustomForm extends CustomForm implements CRForm {

	public function __construct() {
		$formApi = CRFormUtils::getFormAPI();
		$formApi->formCountBump();
		parent::__construct($formApi->formCount, [$this, "onSubmit"]);
		$formApi->forms[$this->id] = $this;
	}

	protected abstract function setup(Player $player) : void;

	public function sendToPlayer(Player $player) : void {
		$this->setup($player);
		parent::sendToPlayer($player);
	}

	/**
	 * @param Player $player
	 * @param mixed  $data
	 */
	public function onSubmit(Player $player, $data) : void {

	}

}