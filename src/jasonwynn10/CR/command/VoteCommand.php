<?php
declare(strict_types=1);
namespace jasonwynn10\CR\command;

use jasonwynn10\CR\form\VoteForm;
use jasonwynn10\CR\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use ProjectInfinity\PocketVote\PocketVote;

class VoteCommand extends PluginCommand {
	public function __construct(Main $plugin) {
		parent::__construct("vote", $plugin);
		$this->setUsage("/vote");
		$this->setDescription("Display a UI for ranks");
		$this->setPermission("cr.command.vote");
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param array         $args
	 *
	 * @return bool|mixed
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if($this->testPermission($sender) and $sender instanceof Player) {
			//Main::sendPlayerDelayedForm($sender, new VoteForm()); //TODO: delete
			if($sender instanceof Player and PocketVote::getPlugin()->getVoteManager()->hasVotes($sender->getName())) {
				Main::sendPlayerDelayedForm($sender, new VoteForm());
			}
			return true;
		}
		return false;
	}
}