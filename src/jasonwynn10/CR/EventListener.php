<?php
declare(strict_types=1);

namespace jasonwynn10\CR;

//use jasonwynn10\CR\form\KingdomSelectionForm;
use Crates\Loader;
use jasonwynn10\CR\form\MoneyGrantRequestForm;
use jasonwynn10\CR\form\TeleportLocationForm;
use jasonwynn10\CR\form\VoteForm;
use jasonwynn10\CR\object\PosAABB;
use jasonwynn10\CR\task\CooldownTask;
use jasonwynn10\CR\task\PoisonRemovalTask;
use onebone\economyapi\EconomyAPI;
use onebone\economyapi\event\money\AddMoneyEvent;
use pocketmine\block\Block;
use pocketmine\block\Cobblestone;
use pocketmine\entity\Effect;
use pocketmine\entity\Living;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityArmorChangeEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\IPlayer;
use pocketmine\level\Position;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Chest;
use ProjectInfinity\PocketVote\event\VoteEvent;

class EventListener implements Listener {

	/** @var Main $plugin */
	private $plugin;

	/** @var PosAABB[] $poisonAABB */
	private static $poisonAABB = [];

	/** @var int[] $sentForms */
	public static $sentForms = [];

	/** @var bool[] $cooldowns */
	private static $cooldowns = [];

	/** @var int[] $votes */
	private static $votes = [];

	/**
	 * EventListener constructor.
	 *
	 * @param Main  $plugin
	 * @param array $cooldowns
	 */
	public function __construct(Main $plugin, array $cooldowns) {
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
		$this->plugin = $plugin;
		self::$cooldowns = $cooldowns;
		$plugin->getLogger()->debug("Event Listener Registered!");
	}

	/**
	 * @param int $key
	 */
	public static function removeAABB(int $key) : void {
		unset(self::$poisonAABB[$key]);
	}

	/**
	 * @param int  $enchantId
	 * @param bool $bool enable use
	 *
	 * @return bool success or failure
	 */
	public static function setCooldown(int $enchantId, bool $bool) : bool {
		if(isset(self::$cooldowns[$enchantId])) {
			self::$cooldowns[$enchantId] = $bool;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return bool[]
	 */
	public static function getCooldowns() : array {
		return self::$cooldowns;
	}

	/**
	 * @param string $player
	 *
	 * @return int
	 */
	public static function hasVotes(string $player) : int {
		return self::$votes[$player] ?? 0;
	}

	/**
	 * @priority MONITOR
	 *
	 * @param PlayerJoinEvent $event
	 */
	public function onJoin(PlayerJoinEvent $event) : void {
		$player  = $event->getPlayer();
		$kingdom = $this->plugin->getPlayerKingdom($player);
		if(empty($kingdom)) {
			$kingdom = $this->plugin->getKingdomNames()[array_rand($this->plugin->getKingdomNames())];
			$this->plugin->setPlayerKingdom($player, $kingdom);
			$locName = array_rand(array_keys(Main::getInstance()->getConfig()->getNested("Kingdoms." . $kingdom, [])));
			$posArr  = $this->plugin->getConfig()->getNested("Kingdoms." . $kingdom . "." . $locName, []);
			if(!empty($posArr)) {
				$level = $this->plugin->getServer()->getLevelByName($posArr["level"]);
				if($level === null) {
					Main::getInstance()->getLogger()->debug("Invalid Level '{$posArr["level"]}' in $locName!");
				} else {
					$player->teleport(new Position($posArr["x"], $posArr["y"], $posArr["z"], $level));
				}
			} else {
				Main::getInstance()->getLogger()->debug("Empty Teleport Array!");
			}
			//Main::sendPlayerDelayedForm($event->getPlayer(), new KingdomSelectionForm(), 60); // wait 3 seconds after join to send form
			if(strpos($player->getNameTag(), "{kingdom}") !== false) {
				$player->setNameTag(str_replace("{kingdom}", $kingdom, $player->getNameTag()));
			}
			return;
		}
		if($this->plugin->getKingdomLeader($kingdom) === $player->getName()) {
			if(!$player->hasPlayedBefore()) {
				Main::sendPlayerDelayedForm($player, new TeleportLocationForm($kingdom, false));
			}
			foreach($this->plugin->getMoneyRequestsInQueue() as $requester => $amount) {
				Main::sendPlayerDelayedForm($player, new MoneyGrantRequestForm($requester, $amount));
			}
		}
		if(strpos($player->getNameTag(), "{kingdom}") !== false) {
			$player->setNameTag(str_replace("{kingdom}", $kingdom, $player->getNameTag()));
		}
	}

	/**
	 * @priority        LOW
	 * @ignoreCancelled true
	 *
	 * @param AddMoneyEvent $event
	 */
	public function onEarnMoney(AddMoneyEvent $event) : void {
		if(!$event->isCancelled() and $event->getIssuer() !== "cr") {
			$kingdom = $this->plugin->getPlayerKingdom($this->plugin->getServer()->getOfflinePlayer($event->getUsername())->getPlayer() ?? $this->plugin->getServer()->getOfflinePlayer($event->getUsername()));
			if($kingdom !== null) {
				$event->setCancelled();
				$amount  = $event->getAmount();
				$percent = abs((int) $this->plugin->getConfig()->getNested("Taxes." . $kingdom, 2)) / 100;
				$economy = EconomyAPI::getInstance();
				$economy->addMoney($kingdom . "Kingdom", $percent * $amount, false, "cr");
				$economy->addMoney($event->getUsername(), $amount - ($percent * $amount), false, "cr");
				$this->plugin->getLogger()->debug($event->getUsername() . " has been taxed!");
			}
		}
	}

	/**
	 * @priority        HIGHEST
	 * @ignoreCancelled false
	 *
	 * @param PlayerChatEvent $event
	 */
	public function onPlayerChat(PlayerChatEvent $event) : void {
		$player  = $event->getPlayer();
		$kingdom = $this->plugin->getPlayerKingdom($player);
		if($kingdom === null) {
			return;
		}
		$format = str_replace("{kingdom}", $kingdom, $event->getFormat());
		$format = str_replace("{isLeader}", $this->plugin->getKingdomLeader($kingdom) === $player->getName() ? "Leader" : "", $format);
		$event->setFormat($format);
		if(Main::inKingdomChat($player)) {
			$members = $this->plugin->getKingdomMembers($kingdom);
			$recipients = [];
			foreach($members as $member) {
				$recipient = $this->plugin->getServer()->getPlayer($member);
				if($recipient !== null) {
					$recipients[] = $recipient;
				}
			}
			$event->setRecipients($recipients);
		}
	}

	/**
	 * @priority        HIGHEST
	 * @ignoreCancelled false
	 *
	 * @param EntityDamageEvent $event
	 */
	public function onDamage(EntityDamageEvent $event) : void {
		if($event instanceof EntityDamageByChildEntityEvent and $event->getCause() === EntityDamageEvent::CAUSE_PROJECTILE) {
			$damager = $event->getDamager();
			$damaged = $event->getEntity();
		} elseif($event instanceof EntityDamageByEntityEvent and $event->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
			$damager = $event->getDamager();
			$damaged = $event->getEntity();
		} else {
			return;
		}
		if($damager instanceof Player) {
			$hand = $damager->getInventory()->getItemInHand();
			foreach($hand->getEnchantments() as $enchantment) {
				$rand = mt_rand(1, 100);
				switch($enchantment->getId()) {
					case 250:
						if($rand > 90) {
							$damage = $event->getDamage();
							$event->setDamage($damage + ($damage * .1));
						}
						break;
					case 950:
						if($rand > 85 - ($enchantment->getLevel() * 5)) {
							$damage = $event->getDamage();
							$event->setDamage($damage + ($damage * .2));
						}
						break;
					case 951:
						if($rand > 85 - ($enchantment->getLevel() * 5) and $damaged instanceof Living) {
							$damaged->addEffect(Effect::getEffect(Effect::SLOWNESS));
						}
						break;
					case 952:
						if($rand > 0 and $damaged instanceof Living) {
							$damaged->addEffect(Effect::getEffect(Effect::NAUSEA)->setDuration((2 * 20) + ($enchantment->getLevel() * 20)));
						}
						break;
					case 953:
						if($rand > 92) {
							$event->setDamage(8);
						} // 4 hearts
						break;
					case 954:
						if(($damager->getHealth() / $damager->getMaxHealth()) <= 0.2 and self::$cooldowns[954]) {
							$damage = $event->getDamage();
							$event->setDamage($damage + ($damage * .5));
							self::$cooldowns[954] = false;
							$this->plugin->getServer()->getScheduler()->scheduleDelayedTask(
								new CooldownTask($this->plugin, 954),
								20 * 60 * 5 // 5 Minutes
							);
						}
						break;
					default:
						continue;
				}
			}
		}
		if($damaged instanceof Player) {
			$armour = $damaged->getArmorInventory()->getContents();
			foreach($armour as $armourPiece) {
				foreach($armourPiece->getEnchantments() as $enchantment) {
					$rand = mt_rand(1, 100);
					switch($enchantment->getId()) {
						case 453:
							if($rand > 98 - ($enchantment->getLevel() * 2)) {
								$damager->attack(new EntityDamageByEntityEvent($damaged, $damager, EntityDamageEvent::CAUSE_MAGIC, $event->getFinalDamage(), 0));
							}
							break;
						case 454:
							if($damaged->getHealth() - $event->getFinalDamage() <= 0) {
								$this->plugin->getServer()->getScheduler()->scheduleDelayedTask(
									new PoisonRemovalTask($this->plugin, count(self::$poisonAABB)),
									5 * 20 // 5 seconds
								);
								self::$poisonAABB[] = new PosAABB($damaged->x - 1, $damaged->getFloorY(), $damaged->z - 1, $damaged->x + 1, $damaged->y, $damaged->z + 1, $damaged->getLevel());
							}
							break;
						default:
							continue;
					}
				}
			}
		}
		if($damager instanceof Player and $damaged instanceof Player and $damaged->getHealth() - $event->getFinalDamage() <= 0 and $this->plugin->getPlayerKingdom($damager) !== $this->plugin->getPlayerKingdom($damaged)) {
			$kingdom = $this->plugin->getPlayerKingdom($damager);
			$add = (int) $this->plugin->getConfig()->getNested("Power Per Kill", 5);
			$this->plugin->getConfig()->setNested("Power." . $kingdom, $this->plugin->getKingdomPower($kingdom) + $add);
		}
	}

	/**
	 * @priority        MONITOR
	 * @ignoreCancelled true
	 *
	 * @param PlayerMoveEvent $event
	 */
	public function onMove(PlayerMoveEvent $event) : void {
		$player = $event->getPlayer();
		foreach(self::$poisonAABB as $posAABB) {
			if($player->getLevel() === $posAABB->getLevel() and $posAABB->isVectorInXZ($player)) {
				$player->addEffect(Effect::getEffect(Effect::POISON)->setAmbient()->setDuration(25)); // 1.5 seconds
			}
		}
	}

	/**
	 * @priority        MONITOR
	 * @ignoreCancelled true
	 *
	 * @param EntityArmorChangeEvent $event
	 */
	public function onEquipArmour(EntityArmorChangeEvent $event) : void {
		$entity = $event->getEntity();
		if($entity instanceof Player) {
			/** @var PlayerInventory $inventory */
			$armor  = $entity->getArmorInventory();
			$item   = $armor->getHelmet();
			foreach($item->getEnchantments() as $enchantment) {
				switch($enchantment->getId()) {
					case 450:
						if($armor->getChestplate()->hasEnchantment(450)) {
							$entity->addEffect(Effect::getEffect(Effect::FIRE_RESISTANCE)->setDuration(INT32_MAX));
							$entity->addEffect(Effect::getEffect(Effect::NIGHT_VISION)->setDuration(INT32_MAX));
						} else {
							$entity->removeEffect(Effect::FIRE_RESISTANCE);
							$entity->removeEffect(Effect::NIGHT_VISION);
						}
						break;
					case 451:
						if($armor->getChestplate()->hasEnchantment(450)) {
							$entity->addEffect(Effect::getEffect(Effect::FIRE_RESISTANCE)->setDuration(INT32_MAX)->setAmplifier(1));
							$entity->addEffect(Effect::getEffect(Effect::NIGHT_VISION)->setDuration(INT32_MAX));
						} else {
							$entity->removeEffect(Effect::FIRE_RESISTANCE);
							$entity->removeEffect(Effect::NIGHT_VISION);
						}
						break;
					case 452:
						if($armor->getChestplate()->hasEnchantment(450)) {
							$entity->addEffect(Effect::getEffect(Effect::NIGHT_VISION)->setDuration(INT32_MAX));
						} else {
							$entity->removeEffect(Effect::NIGHT_VISION);
						}
						break;
					default:
						continue;
						break;
				}
			}
			$item = $armor->getLeggings();
			foreach($item->getEnchantments() as $enchantment) {
				switch($enchantment->getId()) {
					case 450:
						if($armor->getBoots()->hasEnchantment(450)) {
							$entity->addEffect(Effect::getEffect(Effect::SPEED)->setDuration(INT32_MAX));
							$entity->addEffect(Effect::getEffect(Effect::JUMP)->setDuration(INT32_MAX));
						} else {
							$entity->removeEffect(Effect::SPEED);
							$entity->removeEffect(Effect::JUMP);
						}
						break;
					case 451:
						if($armor->getBoots()->hasEnchantment(451)) {
							$entity->addEffect(Effect::getEffect(Effect::SPEED)->setAmplifier(1)->setDuration(INT32_MAX));
							$entity->addEffect(Effect::getEffect(Effect::JUMP)->setDuration(INT32_MAX));
						} else {
							$entity->removeEffect(Effect::SPEED);
							$entity->removeEffect(Effect::JUMP);
						}
						break;
					case 452:
						if($armor->getBoots()->hasEnchantment(452)) {
							$entity->addEffect(Effect::getEffect(Effect::JUMP)->setDuration(INT32_MAX));
						} else {
							$entity->removeEffect(Effect::JUMP);
						}
						break;
					default:
						continue;
						break;
				}
			}
		}
	}

	/**
	 * @priority        MONITOR
	 * @ignoreCancelled false
	 *
	 * @param BlockBreakEvent $event
	 */
	public function onBreak(BlockBreakEvent $event) : void {
		if($event->getItem()->getEnchantment(1050) !== null and $event->getBlock() instanceof Cobblestone and mt_rand(1, 100) > 95) {
			$player  = $event->getPlayer();
			$crates  = Loader::getInstance()->getCrateManager()->getCratePool();
			$crate   = $crates[array_rand($crates)];
			$session = Loader::getInstance()->getSessionManager()->getSession($player);
			$session->addCrateKey($crate->getIdentifier());
			$player->sendMessage("You got found the {$crate->getName()} Crate key! Do \"/crate keys\" to see how many you have!");
		}
	}

	/**
	 * @priority        MONITOR
	 * @ignoreCancelled false
	 *
	 * @param PlayerInteractEvent $event
	 */
	public function onTap(PlayerInteractEvent $event) : void {
		if($event->getItem()->getNamedTagEntry("VoteReward") !== null) {
			Main::sendPlayerDelayedForm($event->getPlayer(), new VoteForm());
		}
	}

	/**
	 * @priority        MONITOR
	 * @ignoreCancelled false
	 *
	 * @param VoteEvent $event
	 */
	public function onVote(VoteEvent $event) : void {
		if(!$event->isCancelled()) {
			/** @var string|Player $player */
			$player = $event->getPlayer();
			if(is_string($player)) {
				$player = $this->plugin->getServer()->getPlayer($player);
			} elseif($player instanceof IPlayer) {
				$player = $player->getPlayer();
			}
			if($player instanceof Player) {
				self::$votes[$player->getName()] += 1;
			} else {
				self::$votes[$player] += 1;
			}
		}
	}

	/**
	 * @priority        MONITOR
	 * @ignoreCancelled true
	 *
	 * @param InventoryCloseEvent $event
	 */
	public function onInventoryClose(InventoryCloseEvent $event) : void {
		$inventory = $event->getInventory();
		if($inventory instanceof ContainerInventory) {
			$holder = $inventory->getHolder();
			if($holder instanceof Chest and $holder->namedtag->hasTag("Envoy", StringTag::class)) {
				$holder->getLevel()->setBlock($holder, Block::get(Block::AIR), false, false);
				$holder->close();
			}
		}
	}
}