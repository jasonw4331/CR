<?php
declare(strict_types=1);

namespace jasonwynn10\CR\command;

use jasonwynn10\CR\command\Combine\CombineLogic;
use jasonwynn10\CR\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

class CombineCommand extends PluginCommand {
	/**
	 * CombineCommand constructor.
	 *
	 * @param Main $owner
	 */
	public function __construct(Main $owner) {
		parent::__construct("combine", $owner);
		$this->setUsage("/combine <Enchanted Item Slot #> <Book Slot #>");
		$this->setDescription("Combines enchantments of items ");
		$this->setPermission("cr.command.combine");
		new CombineLogic($owner);
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 *
	 * @return bool|mixed
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if($this->testPermission($sender) and $sender instanceof Player) {
			CombineLogic::sendInventory($sender);
		}
		return true;
	}
}