<?php
declare(strict_types=1);
namespace jasonwynn10\CR\command;

use jasonwynn10\CR\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;

class EnvoyTimeCommand extends PluginCommand {
	/**
	 * EnvoyTimeCommand constructor.
	 *
	 * @param Main $owner
	 */
	public function __construct(Main $owner) {
		parent::__construct("envoytime", $owner);
		$this->setUsage("/envoytime");
		$this->setDescription("Displays the time until the next envoy drop");
		$this->setPermission("cr.command.envoytime");
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 *
	 * @return bool|mixed
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if($this->testPermission($sender)) {
			/** @noinspection PhpUndefinedMethodInspection */
			$sender->sendMessage("The next envoy drop will be in " . $this->getPlugin()->getEnvoyDropTime() . " minutes");
			return true;
		}
		return false;
	}
}