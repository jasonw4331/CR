<?php
declare(strict_types=1);
namespace jasonwynn10\CR\form;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;

abstract class CRSimpleForm extends SimpleForm implements CRForm {
	public function __construct() {
		$formApi = CRFormUtils::getFormAPI();
		$formApi->formCountBump();
		parent::__construct($formApi->formCount, [$this, "onSubmit"]);
	}

	public function sendToPlayer(Player $player) : void {
		$this->setup($player);
		parent::sendToPlayer($player);
	}

	protected abstract function setup(Player $player) : void;

	/**
	 * @param Player $player
	 * @param mixed $data
	 */
	public function onSubmit(Player $player, $data) : void {

	}
}