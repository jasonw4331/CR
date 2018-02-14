<?php

declare(strict_types=1);

namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use pocketmine\Player;

class VoteRankInformationForm extends CRCustomForm {

	/** @var string $rank */
	private $rank;

	public function __construct(string $rank) {
		$this->rank = $rank;
		parent::__construct();
	}

	protected  function setup(Player $player) : void {
		$this->setTitle("Rank Information");
		$this->addLabel("Potion Effects:");
		foreach(Main::getInstance()->getVoteRankEffects($this->rank) as $effectString) {
			$effect = Main::getEffectFromString($effectString);
			$this->addLabel($effect->getName() . " " . Main::getRomanNumber($effect->getAmplifier()) . " for " . (int) ($effect->getDuration() / 20) . " seconds");
		}
		$this->addLabel("Items:");
		foreach(Main::getInstance()->getVoteRankItems($this->rank) as $itemString) {
			$item       = Main::getItemFromString($itemString);
			$this->addLabel($item->getCount() . " of " . $item->getId() . ":" . $item->getDamage());
		}
		$this->addToggle("Select this rank?");
	}

	/**
	 * @param Player $player
	 * @param mixed  $data
	 */
	public function onSubmit(Player $player, $data) : void {
		if($data === null) {
			(new VoteForm())->sendToPlayer($player);
			return;
		}

		$selectToggle = $data[count($data) - 1];
		if(is_bool($selectToggle) and $selectToggle) {
			Main::givePlayerRank($player, $this->rank);
			return;
		}
		(new VoteForm())->sendToPlayer($player);
	}

}