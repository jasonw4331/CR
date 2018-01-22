<?php
declare(strict_types=1);
namespace jasonwynn10\CR\form;

use jasonwynn10\CR\Main;
use pocketmine\form\CustomForm;
use pocketmine\form\element\Label;
use pocketmine\form\element\Toggle;
use pocketmine\form\Form;
use pocketmine\Player;

class VoteRankInformationForm extends CustomForm {
	/** @var string $rank */
	private $rank;
	public function __construct(string $rank) {
		$this->rank = $rank;
		$elements = [];
		$elements[] = new Label("Potion Effects:");
		foreach(Main::getInstance()->getVoteRankEffects($rank) as $effectString) {
			$effect = Main::getEffectFromString($effectString);
			$elements[] = new Label($effect->getName()." ".Main::getRomanNumber($effect->getAmplifier())." for ".(int)($effect->getDuration()/20)." seconds");
		}
		$elements[] = new Label("Items:");
		foreach(Main::getInstance()->getVoteRankItems($rank) as $itemString) {
			$item = Main::getItemFromString($itemString);
			$elements[] = new Label($item->getCount()." of ".$item->getId().":".$item->getDamage());
		}
		$elements[] = new Toggle("Select this Rank?");
		parent::__construct("Rank Information", $elements);
	}
	public function onClose(Player $player) : ?Form {
		return new VoteForm();
	}
	public function onSubmit(Player $player) : ?Form {
		if($this->getElement(count($this->getAllElements())-1)->getValue()) {
			Main::givePlayerRank($player, $this->rank);
			return null;
		}
		return new VoteForm();
	}
}