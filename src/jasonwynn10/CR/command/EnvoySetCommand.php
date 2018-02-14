<?php
declare(strict_types=1);

namespace jasonwynn10\CR\command;

use jasonwynn10\CR\entity\Envoy;
use jasonwynn10\CR\Main;
use jasonwynn10\CR\object\PosAABB;
use jasonwynn10\CR\task\EnvoyDespawnTask;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

class EnvoySetCommand extends PluginCommand {

	/**
	 * EnvoySetCommand constructor.
	 *
	 * @param Main $owner
	 */
	public function __construct(Main $owner) {
		parent::__construct("envoyset", $owner);
		$this->setUsage("/envoyset <distance from player>");
		$this->setDescription("Drop an envoy a set number of blocks from your position");
		$this->setPermission("cr.command.envoyset");
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
			$posAABB = new PosAABB($sender->x - (float) $args[0], 0, $sender->z - (float) $args[0], $sender->x + (float) $args[0], $sender->getLevel()->getWorldHeight(), $sender->z + (float) $args[0], $sender->getLevel());
			$bool    = true;
			while($bool) {
				$randX = mt_rand((int) $posAABB->minX, (int) $posAABB->maxX);
				$randZ = mt_rand((int) $posAABB->minZ, (int) $posAABB->maxZ);
				$vec   = new Vector3($randX, $sender->y, $randZ);
				if($vec->distance($sender) >= (float) $args[0] and $vec->distance($sender) < ((float) $args[0] + 1)) {
					$bool = false;
				}
			}
			/** @noinspection PhpUndefinedVariableInspection */
			$nbt = Envoy::createBaseNBT(new Vector3($randX, $sender->getLevel()->getWorldHeight(), $randZ), new Vector3(0, -0.1, 0));
			/** @var Envoy $crystal */
			$crystal = Envoy::createEntity(Envoy::ENDER_CRYSTAL, $sender->getLevel(), $nbt);
			$crystal->spawnToAll();
			$rand = mt_rand(1, 100);
			//if($rand > 90) {
			//	$name = "Legendary";
			//}else
			if($rand > 80) {
				$rand = mt_rand(1, 100);
				if($rand > 50) {
					$name = "Mystical";
				} else {
					$name = "Rare";
				}
			} else {
				$name = "Common";
			}
			$crystal->setNameTag($name . " Envoy");
			/** @noinspection PhpParamsInspection */
			Server::getInstance()->getScheduler()->scheduleDelayedTask(new EnvoyDespawnTask($this->getPlugin(), $crystal->getId()), 20 * 60 // 1 minute
			);
			/** @noinspection PhpUndefinedMethodInspection */
			$message = $this->getPlugin()->getEnvoyConfig()->get("Message", "");
			if(!empty($message)) {
				Server::getInstance()->broadcastMessage($message);
			}
			return true;
		} else {
			return false;
		}
	}
}