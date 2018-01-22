<?php
declare(strict_types=1);
namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use onebone\economyapi\EconomyAPI;
use pocketmine\form\CustomForm;
use pocketmine\form\element\Dropdown;
use pocketmine\form\element\Label;
use pocketmine\form\element\Toggle;
use pocketmine\form\Form;
use pocketmine\IPlayer;
use pocketmine\Player;

class KingdomInformationForm extends CustomForm {
	/**
	 * KingdomInformationForm constructor.
	 *
	 * @param IPlayer $player
	 */
	public function __construct(IPlayer $player) {
		$plugin = Main::getInstance();
		$kingdom = $plugin->getPlayerKingdom($player) ?? "Join a kingdom!";
		$elements = [];
		$elements[] = new Label("Kingdom Leader:  ".$plugin->getKingdomLeader($kingdom));
		$elements[] = new Label("Kingdom Power:  ".$plugin->getKingdomPower($kingdom));
		$elements[] = new Label("Kingdom Booty:  ".$plugin->getKingdomMoney($kingdom));
		$elements[] = new Toggle("Request Money?");
		$members = $plugin->getKingdomMembers($kingdom);
		$elements[] = new Dropdown("Kingdom Members", !empty($members) ? $members : ["No one is in this kingdom! Tell a Staff member!"]);
		parent::__construct("Kingdom Information", $elements);
	}

	/**
	 * @param Player $player
	 *
	 * @return null|Form
	 */
	public function onSubmit(Player $player) : ?Form {
		$option = $this->getElement(3);
		if($option->getValue()) {
			if(EconomyAPI::getInstance()->myMoney(Main::getInstance()->getPlayerKingdom($player)."Kingdom") <= 0) {
				$player->sendMessage("Your kingdom has no money!");
				return null;
			}
			return new MoneyRequestForm($player);
		}
		return null;
	}
}