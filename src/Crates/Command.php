<?php
/**
 * Created by PhpStorm.
 * User: AJ
 * Date: 22/09/2017
 * Time: 21:44
 */

namespace Crates;

use Crates\crate\Crate;
use jasonwynn10\CR\Main;
use pocketmine\command\Command as PMCommand;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Command extends PMCommand {
	/** @var Main */
	private $loader;

	/**
	 * Command constructor.
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin) {
		$this->loader = $plugin;
		parent::__construct("crate", "Crate base command", "/crate <keys|spawncoord|spawn|give>");
	}

	public function spawnCrate(Crate $crate, Position $position) {
		$position = Loader::getInstance()->getCrateManager()->addCrateBlock($crate, new Position($position->getFloorX(), $position->getFloorY(), $position->getFloorZ(), $position->level));
		$config = new Config($this->loader->getDataFolder() . "blocks.json");
		$all = $config->getAll();
		$all[] = [$crate->getIdentifier(), Utils::createPositionString($position)];
		$config->setAll($all);
		$config->save();
	}

	public function execute(CommandSender $sender, $commandLabel, array $args) {
		if(isset($args[0])) {
			switch($args[0]) {
				case "keys":
					if($sender instanceof Player) {
						$session = Loader::getInstance()->getSessionManager()->getSession($sender);
						if(empty($session->getCrateKeys())) {
							$sender->sendMessage(TextFormat::RED . "> " . TextFormat::YELLOW . "You haven't any crate key!");
						}
						else {
							$sender->sendMessage(Utils::translateColors("{RED}> {YELLOW}Your keys {RED}<"));
							foreach(($crateKeys = $session->getCrateKeys()) as $key => $crateIdentifier) {
								$crate = Loader::getInstance()->getCrateManager()->getCrate($key);
								if($crate != null) {
									$sender->sendMessage(TextFormat::GREEN . "- " . TextFormat::WHITE . $crateIdentifier . TextFormat::YELLOW . " " . $crate->getName() . " keys");
								}
							}
						}
					}
					else {
						$sender->sendMessage("Please, run this command in game");
					}
				break;
				case "sc":
				case "spawncoord":
					if(!isset($args[4])) {
						$sender->sendMessage("Usage: /crate spawncoord <x> <y> <z> <crate identifier> [level = default]");
						return;
					}
					for($i = 1; $i < 4; $i++) {
						if(!is_numeric($args[$i])) {
							$sender->sendMessage("{$args[$i]} is not a valid coordinate!");
							return;
						}
						$args[$i] = (int) $args[$i];
					}
					$crate = Loader::getInstance()->getCrateManager()->getCrate($args[4]);
					if($crate == null) {
						$sender->sendMessage("{$args[4]} is not a valid crate identifier");
						return;
					}
					if(isset($args[5])) {
						$level = $this->loader->getServer()->getLevelByName($args[5]);
						$level = ($level != null) ? $level : $this->loader->getServer()->getDefaultLevel();
					}
					else {
						$level = $this->loader->getServer()->getDefaultLevel();
					}
					$this->spawnCrate($crate, new Position($args[1], $args[2], $args[3], $level));
					$sender->sendMessage("Successfully spawn a {$crate->getName()} in {$level->getName()}");
				break;
				case "spawn":
					if($sender instanceof Player and $sender->isOp()) {
						if(isset($args[1])) {
							$crate = Loader::getInstance()->getCrateManager()->getCrate($args[1]);
							if($crate != null) {
								$this->spawnCrate($crate, $sender);
								$sender->sendMessage("Successfully spawned a {$crate->getName()}");
							}
							else {
								$sender->sendMessage("{$args[1]} is not a valid crate identifier");
							}
						}
						else {
							$sender->sendMessage("Usage: /crate spawn <crate identifier>");
						}
					}
				break;
				case "give":
					if($sender->isOp()) {
						if(isset($args[1], $args[2])) {
							$player = $this->loader->getServer()->getPlayerExact($args[1]);
							if($player instanceof Player) {
								$crate = Loader::getInstance()->getCrateManager()->getCrate($args[2]);
								if($crate != null) {
									$session = Loader::getInstance()->getSessionManager()->getSession($player);
									if(isset($args[3]) and is_numeric($args[3])) {
										$amount = (int) $args[3];
									}
									else {
										$amount = 1;
									}
									$session->addCrateKey($crate->getIdentifier(), $amount);
									$sender->sendMessage("Added a {$crate->getName()} key to {$player->getName()} successfully");
								}
								else {
									$sender->sendMessage("{$args[2]} is not a valid crate identifier");
								}
							}
							else {
								$sender->sendMessage("{$args[1]} is not a valid player!");
							}
						}
						else {
							$sender->sendMessage("Usage: /crate give <player> <crate identifier> [amount=1]");
						}
					}
				break;
				default:
					if($sender->isOp()) {
						$sender->sendMessage("Usage: /crate <keys|spawncoord|spawn|give> [args]");
					}
					else {
						$sender->sendMessage("Usage: /crate keys");
					}
				break;
			}
		}
		else {
			if($sender->isOp()) {
				$sender->sendMessage("Usage: /crate <keys|spawncoord|spawn|give> [args]");
			}
			else {
				$sender->sendMessage("Usage: /crate keys");
			}
		}
	}

}