<?php
declare(strict_types=1);
namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use onebone\economyapi\EconomyAPI;
use pocketmine\form\CustomForm;
use pocketmine\form\element\Slider;
use pocketmine\form\Form;
use pocketmine\IPlayer;
use pocketmine\Player;

class MoneyRequestForm extends CustomForm {
	/**
	 * MoneyRequestForm constructor.
	 *
	 * @param IPlayer $player
	 */
	public function __construct(IPlayer $player) {
		$elements = [];
		$elements[] = new Slider("Requested Amount",0.00, (float) EconomyAPI::getInstance()->myMoney(Main::getInstance()->getPlayerKingdom($player)."Kingdom"),5.00, 10.00);
		parent::__construct("Request Money", $elements);
	}

	/**
	 * @param Player $player
	 *
	 * @return null|Form
	 */
	public function onSubmit(Player $player) : ?Form {
		$element = $this->getElement(0);
		Main::getInstance()->addMoneyRequestToQueue($player, $element->getValue());
		return null;
	}
}