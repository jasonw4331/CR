<?php
declare(strict_types=1);
namespace jasonwynn10\CR\command;

use jasonwynn10\CR\EventListener;
use jasonwynn10\CR\form\VoteForm;
use jasonwynn10\CR\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

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
			if($sender instanceof Player and EventListener::hasVotes($sender->getName())) {
				Main::sendPlayerDelayedForm($sender, new VoteForm());
			} elseif($sender instanceof Player) {
				$sender->sendMessage("You don't have any votes!");
			}
			return true;
		}
		return false;
	}
}