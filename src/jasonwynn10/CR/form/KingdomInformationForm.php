<?php

declare(strict_types=1);

namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

class KingdomInformationForm extends CRCustomForm {

	protected function setup(Player $player) : void {
		$plugin = Main::getInstance();
		$kingdom = $plugin->getPlayerKingdom($player) ?? "Join a kingdom!";
		$members = $plugin->getKingdomMembers($kingdom);

		$this->setTitle("Kingdom Information");
		$this->addLabel("Kingdom Leader:  " . $plugin->getKingdomLeader($kingdom));
		$this->addLabel("Kingdom Power:  " . $plugin->getKingdomPower($kingdom));
		$this->addLabel("Kingdom Booty:  " . $plugin->getKingdomMoney($kingdom));
		$this->addToggle("Request Money?");
		$this->addDropdown("Kingdom Members", !empty($members) ? $members : ["No one is in this kingdom! Tell a Staff member!"]);
	}

	/**
	 * @param Player $player
	 * @param mixed  $data
	 */
	public function onSubmit(Player $player, $data) : void {
		if($data === null) {
			return;
		}

		$moneyToggle = $data[3];
		if(is_bool($moneyToggle) and $moneyToggle) {
			if(EconomyAPI::getInstance()->myMoney(Main::getInstance()->getPlayerKingdom($player) . "Kingdom") <= 0) {
				$player->sendMessage("Your kingdom has no money!");
				return;
			}
			(new MoneyRequestForm())->sendToPlayer($player);
		}
	}

}