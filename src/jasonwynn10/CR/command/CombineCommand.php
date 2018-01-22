<?php
declare(strict_types=1);
namespace jasonwynn10\CR\command;

use jasonwynn10\CR\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\Book;
use pocketmine\Player;

class CombineCommand extends PluginCommand {
	public function __construct(Main $owner) {
		parent::__construct("combine", $owner);
		$this->setUsage("/combine <Enchanted Item Slot #> <Book Slot #>");
		$this->setDescription("Combines enchantments of items ");
		$this->setPermission("cr.command.combine");
	}
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if($this->testPermission($sender) and $sender instanceof Player) {
			if(count($args) >= 2) {
				if((int) $args[0] > 10 or (int) $args[1] > 10 or (int) $args[0] < 1 or (int) $args[1] < 1) {
					$sender->sendMessage("One of your slot numbers is out the allowed range");
				}
				$item = $sender->getInventory()->getHotbarSlotItem((int) $args[0] - 1);
				$book = $sender->getInventory()->getHotbarSlotItem((int) $args[1] - 1);
				if(!$item->isNull() and !$book->isNull() and $book instanceof Book) {
					$enchantments = $item->getEnchantments();
					$item->removeEnchantments();
					foreach($enchantments as $enchantment)
						$book->addEnchantment($enchantment);
				}else{
					$sender->sendMessage("Please choose a valid inventory slot!");
				}
			}else{
				return false;
			}
		}
		return true;
	}
}