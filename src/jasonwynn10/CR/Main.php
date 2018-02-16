<?php
declare(strict_types=1);
namespace jasonwynn10\CR;

use _64FF00\PureChat\PureChat;
use _64FF00\PurePerms\PurePerms;
use Crates\Loader;
use jasonwynn10\CR\command\CombineCommand;
use jasonwynn10\CR\command\EnvoySetCommand;
use jasonwynn10\CR\command\EnvoyTimeCommand;
use jasonwynn10\CR\command\KingdomCommand;
use jasonwynn10\CR\command\VoteCommand;
use jasonwynn10\CR\command\WarpMeCommand;
use jasonwynn10\CR\entity\Envoy;
use jasonwynn10\CR\form\CRFormUtils;
use jasonwynn10\CR\form\MoneyGrantRequestForm;
use jasonwynn10\CR\object\Area;
use jasonwynn10\CR\object\PosAABB;
use jasonwynn10\CR\task\AreaCheckTask;
use jasonwynn10\CR\task\ClaimProgressDisplayTask;
use jasonwynn10\CR\task\DelayedFormTask;
use jasonwynn10\CR\task\EnvoyDespawnTask;
use jasonwynn10\CR\task\EnvoyDropTask;
use jasonwynn10\CR\task\PowerGiveTask;
use jojoe77777\FormAPI\Form;
use onebone\economyapi\EconomyAPI;
use PiggyCustomEnchants\CustomEnchants\CustomEnchants;
use pocketmine\command\Command;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\IPlayer;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\JsonNBTParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

//use jasonwynn10\CR\command\VoteCommand;

class Main extends PluginBase {
	/** @var Main $instance */
	private static $instance;
	/** @var int $formCount */
	public static $formCount = 0;
	/** @var Config $players */
	private $players;
	/** @var Config $moneyRequestQueue */
	private $moneyRequestQueue;
	/** @var Config $voteRanks */
	private $voteRanks;
	/** @var Area[] $areas */
	private $areas = [];
	/** @var Loader $crates */
	private $crates;
	/** @var Config $envoyConfig */
	private $envoyConfig;
	/** @var int $envoyDropTime */
	private $envoyDropTime = 60;

	/**
	 * @return Main
	 */
	public static function getInstance() : self {
		return self::$instance;
	}

	public function onLoad() : void {
		self::$instance = $this;
		$this->getLogger()->debug("Plugin instance set!");

		$this->saveDefaultConfig();
		$this->players = new Config($this->getDataFolder()."players.yml",Config::YAML);
		$this->moneyRequestQueue = new Config($this->getDataFolder()."MoneyRequests.json", Config::JSON);
		$this->saveResource("VoteConfig.yml");
		$this->voteRanks = new Config($this->getDataFolder()."VoteConfig.yml", Config::YAML);
		$this->saveResource("EnvoyConfig.yml");
		$this->envoyConfig = new Config($this->getDataFolder()."EnvoyConfig.yml", Config::YAML);
		$this->getLogger()->debug("All configs saved/loaded!");

		Entity::registerEntity(Envoy::class);
		$this->getLogger()->debug("Envoy Entity Registered!");
	}

	public function onEnable() : void {
		CRFormUtils::init();

		foreach($this->getKingdomNames() as $kingdom) {
			EconomyAPI::getInstance()->createAccount($kingdom."Kingdom", 1000.00, true);
			$this->players->set($this->getConfig()->getNested("Leaders.".$kingdom, "blank"), $kingdom);
		}
		$this->players->save();
		$this->getLogger()->debug("Kingdom economy accounts created/loaded!");

		/** @var PurePerms $purePerms */
		$purePerms = Server::getInstance()->getPluginManager()->getPlugin("PurePerms");
		/** @var PureChat $pureChat */
		$pureChat = $this->getServer()->getPluginManager()->getPlugin("PureChat");
		foreach($this->voteRanks->get("Ranks", []) as $rank => $data) {
			$purePerms->addGroup($rank);
			$group = $purePerms->getGroup($rank);
			$chat = str_replace("{rank}", $rank, $data["Chat"]);
			$pureChat->setOriginalChatFormat($group, $chat);
		}
		$this->getLogger()->debug("PurePerms and PureChat ranks created/loaded!");

		$resource = $this->getResource("CE.json");
		if($resource === null) {
			$this->getLogger()->error("No Custom Enchantment resource found!");
			$this->setEnabled(false);
			return;
		}
		else {
			$enchantments = json_decode(stream_get_contents($resource), true);
			fclose($resource);
			$cooldowns = [];
			foreach(array_keys($enchantments) as $id) {
				$cooldowns[$id] = true;
			}
			new EventListener($this, $cooldowns);
		}

		$map = $this->getServer()->getCommandMap();
		$vote = $map->getCommand("vote");
		if($vote instanceof Command) {
			$map->unregister($vote);
		}
		$map->register("cr", new CombineCommand($this));
		$map->register("cr", new VoteCommand($this));
		$map->register("cr", new KingdomCommand($this));
		$map->register("cr", new WarpMeCommand($this));
		$map->register("cr", new EnvoyTimeCommand($this));
		$map->register("cr", new EnvoySetCommand($this));
		$this->getLogger()->debug("Commands Registered!");

		foreach($this->getConfig()->getNested("Power-Areas.Areas", []) as $areaKey => $areaData) {
			$level = $this->getServer()->getLevelByName($areaData["level"]);
			if($level === null)
				continue; // skip if level isn't loaded
			$this->areas[$areaKey] = new Area($areaData["x1"], $areaData["z1"], $areaData["x2"], $areaData["z2"], $level, $areaData["claimed"]);
		}
		$this->getServer()->getScheduler()->scheduleRepeatingTask(
			new ClaimProgressDisplayTask($this),
			35 // 1.75 seconds
		);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(
			new PowerGiveTask($this),
			(20*(int)$this->getConfig()->getNested("Power-Areas.Time-Per-Power", 120)) // 2 minutes
		);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(
			new AreaCheckTask($this),
			(20*(int)$this->getConfig()->getNested("Power-Areas.Area-Check-Time", 15)) // 15 seconds
		);
		$this->getLogger()->debug("Power Areas Loaded!");

		/** @var \PiggyCustomEnchants\Main $ce */
		$ce = $this->getServer()->getPluginManager()->getPlugin("PiggyCustomEnchants");

		$resource = $this->getResource("CE.json");
		$enchantments = json_decode(stream_get_contents($resource), true);
		fclose($resource);

		foreach($enchantments as $id => $data) {
			$ce->registerEnchantment($id, $data[0], $data[1], $data[2], $data[3], $data[4], $data[5]);
		}
		$this->getLogger()->debug("Enchantments Registered!");

		$this->crates = new Loader($this);

		$dropTime = (int) $this->envoyConfig->get("Drop Time", 60);
		if($dropTime >= 1) {
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new EnvoyDropTask($this, $dropTime), 20 * 60);
		}
		$this->getLogger()->debug("Envoys Loaded!");
	}

	public function onDisable() : void {
		if(isset($this->crates)) {
			$this->crates->onDisable();
		}
		if($this->getConfig()->get("setup-mode", false)) {
			$this->getLogger()->debug("config saving was cancelled!");
			return;
		}
		foreach($this->areas as $areaKey => $area) {
			$this->getConfig()->setNested("Power-Areas.Areas.".$areaKey, [
				"x1" => (int)$area->minX,
				"x2" => (int)$area->maxX,
				"z1" => (int)$area->minZ,
				"z2" => (int)$area->maxZ,
				"level" => $area->getLevel()->getFolderName(),
				"claimed" => $area->getClaimer()
			]);
		}
		$this->getConfig()->save();
	}

	/**
	 * @return Area[]
	 */
	public function getAreas() : array {
		return $this->areas;
	}

	/**
	 * @return string[]
	 */
	public function getKingdomNames() : array {
		return array_keys($this->getConfig()->get("Kingdoms", []));
	}

	/**
	 * @param IPlayer $player
	 * @param string  $kingdom
	 *
	 * @return bool
	 */
	public function setPlayerKingdom(IPlayer $player, string $kingdom) : bool {
		$this->players->set($player->getName(), $kingdom);
		return $this->players->save();
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return string
	 */
	public function getPlayerKingdom(IPlayer $player) : string {
		$this->players->reload();
		/** @noinspection PhpStrictTypeCheckingInspection */
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->players->get($player->getName(), "");
	}

	/**
	 * @param string $kingdom
	 *
	 * @return string
	 */
	public function getKingdomLeader(string $kingdom) : string {
		return $this->getConfig()->getNested("Leaders.".$kingdom, "blank");
	}

	/**
	 * @param string $kingdom
	 *
	 * @return int
	 */
	public function getKingdomPower(string $kingdom) : int {
		return $this->getConfig()->getNested("Power.".$kingdom, 0);
	}

	/**
	 * @param string $kingdom
	 *
	 * @return float
	 */
	public function getKingdomMoney(string $kingdom) : float {
		$return = EconomyAPI::getInstance()->myMoney($kingdom."Kingdom");
		return $return !== false ? $return : 0;
	}

	/**
	 * @param string $kingdom
	 *
	 * @return string[]
	 */
	public function getKingdomMembers(string $kingdom) : array {
		return array_keys($this->players->getAll(), $kingdom);
	}

	/**
	 * @param IPlayer $player
	 * @param float   $amount
	 *
	 * @return bool
	 */
	public function addMoneyRequestToQueue(IPlayer $player, float $amount) : bool {
		$kingdom = $this->getPlayerKingdom($player);
		if($kingdom !== null) {
			$leader = $this->getServer()->getPlayerExact($this->getKingdomLeader($kingdom));
			if($leader !== null) {
				Main::sendPlayerDelayedForm($leader, new MoneyGrantRequestForm($player->getName(), $amount));
			}
		}
		$this->moneyRequestQueue->set($player->getName(), $amount); //TODO: what if player submits form multiple times before leader is online?
		return $this->moneyRequestQueue->save();
	}

	/**
	 * @return float[]
	 */
	public function getMoneyRequestsInQueue() : array {
		return $this->moneyRequestQueue->getAll();
	}

	/**
	 * @return Config
	 */
	public function getMoneyRequestQueue() : Config {
		return $this->moneyRequestQueue;
	}

	/**
	 * @return string[][][]
	 */
	public function getVoteRanks() : array {
		return $this->voteRanks->get("Ranks", []);
	}

	/**
	 * @param string $rank
	 *
	 * @return string[]
	 */
	public function getVoteRankEffects(string $rank) : array {
		return $this->voteRanks->getNested("Ranks.".$rank.".Effects",[]);
	}

	/**
	 * @param string $rank
	 *
	 * @return string[]
	 */
	public function getVoteRankItems(string $rank) : array {
		return $this->voteRanks->getNested("Ranks.".$rank.".Items", []);
	}

	/**
	 * @param Player $player
	 * @param string $rank
	 */
	public static function givePlayerRank(Player $player, string $rank) : void {
		/** @var PurePerms $purePerms */
		$purePerms = Server::getInstance()->getPluginManager()->getPlugin("PurePerms");
		$main = self::$instance;
		$purePerms->setGroup($player, $purePerms->getGroup($rank), null, time() + ($main->voteRanks->get("Rank-Timeout", 24) * 60 * 60));
		$items = $main->getVoteRankItems($rank);
		$effects = $main->getVoteRankEffects($rank);
		foreach($items as $itemString) {
			$player->getInventory()->addItem(self::getItemFromString($itemString));
		}
		foreach($effects as $effectString) {
			$player->addEffect(self::getEffectFromString($effectString));
		}
		$main->getLogger()->debug("Rank ".$rank." given to ".$player->getName());
	}

	/**
	 * @param int $integer
	 *
	 * @return string
	 */
	public static function getRomanNumber(int $integer) : string {
		$characters = [
			'M' => 1000,
			'CM' => 900,
			'D' => 500,
			'CD' => 400,
			'C' => 100,
			'XC' => 90,
			'L' => 50,
			'XL' => 40,
			'X' => 10,
			'IX' => 9,
			'V' => 5,
			'IV' => 4,
			'I' => 1
		];
		$romanString = "";
		while ($integer > 0) {
			foreach ($characters as $rom => $arb) {
				if ($integer >= $arb) {
					$integer -= $arb;
					$romanString .= $rom;
					break;
				}
			}
		}
		return $romanString;
	}

	/**
	 * @param string $itemString
	 *
	 * @return Item
	 */
	public static function getItemFromString(string $itemString) : Item {
		$arr = explode(" ", $itemString);
		$item = Item::fromString($arr[0]);
		if(!isset($arr[1])) {
			$item->setCount($item->getMaxStackSize());
		}else{
			$item->setCount((int) $arr[1]);
		}
		if(isset($arr[2])) {
			$tags = $exception = null;
			$data = implode(" ", array_slice($arr, 2));
			try{
				$tags = JsonNBTParser::parseJSON($data);
			}catch(\Throwable $ex) {
				$exception = $ex;
			}
			if(!$tags instanceof CompoundTag or $exception !== null) {
				self::$instance->getLogger()->error("Invalid NBT tag!");
			}
			$item->setNamedTag($tags);
		}
		return $item;
	}

	/**
	 * @param string $effectString
	 *
	 * @return Effect
	 */
	public static function getEffectFromString(string $effectString) : Effect {
		$arr = explode(" ", $effectString);
		$effect = Effect::getEffectByName($arr[0]);
		if($effect === null) {
			$effect = Effect::getEffect((int) $arr[0]);
		}

		if(count($arr) >= 2) {
			$duration = ((int) $arr[1]) * 20; // duration is in ticks
		}else{
			$duration = $effect->getDefaultDuration();
		}

		if(count($arr) >= 3) {
			$amplification = (int) $arr[2];
			if($amplification > 255) {
				$amplification = 255;
			}elseif($amplification < 0) {
				$amplification = 0;
			}
		}else{
			$amplification = $effect->getAmplifier();
		}

		if(count($arr) >= 4) {
			$v = strtolower($arr[3]);
			if($v === "on" or $v === "true" or $v === "t" or $v === "1") {
				$visibility = false;
			}else{
				$visibility = true;
			}
		}else{
			$visibility = $effect->isVisible();
		}

		$effect->setDuration($duration)->setAmplifier($amplification)->setVisible($visibility);
		return $effect;
	}

	public function givePowerFromAreas() : void {
		$this->getConfig()->reload(); // check if file was edited manually
		foreach($this->areas as $areaKey => $area) {
			if(!empty($area->getClaimer()) and $area->getClaimProgress() >= 100)
				$this->getConfig()->setNested("Power.".$area->getClaimer(), $this->getKingdomPower($area->getClaimer()) + $this->getConfig()->getNested("Areas.Power-Per-Time", 2));
		}
	}

	public function checkAreas() : void {
		$this->getConfig()->reload(); // check if file was edited manually
		foreach($this->areas as $areaKey => $area) {
			$players = $area->checkForPlayers();
			$same = null;
			foreach($players as $playerA) {
				foreach($players as $playerB) {
					if($this->getPlayerKingdom($playerA) !== $this->getPlayerKingdom($playerB)) {
						$same = false;
						break 2;
					}else{
						$same = true;
					}
				}
			}
			if(!empty($area->getClaimKingdom())) {
				if($same === true) {
					$kingdom = $this->getPlayerKingdom($players[0]);
					if(!empty($kingdom) and $kingdom !== $area->getClaimKingdom())
						$area->lowerClaimProgress();
					elseif(!empty($kingdom))
						$area->addClaimProgress($kingdom);
				}elseif(!isset($same))
					if($area->getClaimProgress() < 100)
						$area->lowerClaimProgress();
			}else{
				if($same === true) {
					$kingdom = $this->getPlayerKingdom($players[0]);
					if(!empty($kingdom))
						$area->addClaimProgress($kingdom);
				}else{
					$area->resetClaimProgress();
				}
			}
		}
	}

	public function sendTips() : void {
		foreach($this->getAreas() as $area) {
			foreach($area->checkForPlayers() as $player) {
				if(!empty($area->getClaimKingdom())) {
					$kingdom = $this->getPlayerKingdom($player);
					if($area->getClaimProgress() >= 100) {
						if($kingdom === $area->getClaimKingdom()) {
							$player->sendTip(TextFormat::BOLD.TextFormat::GREEN."Area successfully claimed!");
						}else{
							$player->sendTip(TextFormat::BOLD.TextFormat::DARK_RED."Area claimed by ".$area->getClaimer()."!");
						}
					}else{
						if($kingdom === $area->getClaimKingdom()) {
							$player->sendTip(TextFormat::BOLD.TextFormat::GREEN."Area claim progress: ".$area->getClaimProgress()." percent");
						}else{
							$player->sendTip(TextFormat::BOLD.TextFormat::DARK_RED.$area->getClaimKingdom()." claim progress: ".$area->getClaimProgress()." percent");
						}
					}
				}else{
					$player->sendTip(TextFormat::BOLD.TextFormat::GREEN."Area is unclaimed!");
				}
			}
		}
	}

	public function dropEnvoys() : void {
		/** @var array $zone */
		foreach($this->envoyConfig->get("War Zones", []) as $zone) {
			$level = $this->getServer()->getLevelByName($zone["level"]);
			if(!($level instanceof Level)) {
				$this->getLogger()->notice("Level '" . $zone["level"] . "' couldn't be found and envoys failed to drop as a result!");
				return;
			}
			$posAABB = new PosAABB((int) $zone["x1"], 0, (int) $zone["z1"], (int) $zone["x2"], $level->getWorldHeight(), (int) $zone["z2"], $level);
			for($i = $this->envoyConfig->get("Ender Crystals", 5); $i > 0; $i--) {
				$randX = mt_rand((int) $posAABB->minX, (int) $posAABB->maxX);
				$randZ = mt_rand((int) $posAABB->minZ, (int) $posAABB->maxZ);
				$nbt = Entity::createBaseNBT(new Vector3($randX, $level->getWorldHeight(), $randZ), new Vector3(0, -0.1, 0));
				$crystal = Entity::createEntity(Entity::ENDER_CRYSTAL, $level, $nbt);
				$crystal->spawnToAll();
				$rand = mt_rand(1, 100);
				//if($rand > 90) {
				//	$name = "Legendary";
				//}else
				if($rand > 80) {
					$rand = mt_rand(1, 100);
					if($rand > 50) {
						$name = "Mystical";
					}else{
						$name = "Rare";
					}
				}else{
					$name = "Common";
				}
				$crystal->setNameTag($name." Envoy");
				$this->getServer()->getScheduler()->scheduleDelayedTask(new EnvoyDespawnTask($this, $crystal->getId()), 20 * 60 * 5 // 5 minutes
				);
			}
		}
		$message = $this->envoyConfig->get("Message", "");
		if(!empty($message))
			$this->getServer()->broadcastMessage($message);
	}

	/**
	 * @param string $type
	 *
	 * @return Item[]
	 */
	public function getEnvoyDrops(string $type = "Common") : array {
		$items = [];
		foreach($this->envoyConfig->getNested("Envoy Items.".$type, []) as $itemString) {
			$items[] = self::getItemFromString($itemString);
		}
		return $items;
	}

	/**
	 * @param string $type
	 *
	 * @return EnchantmentInstance
	 */
	public function getRandomCE(string $type = "Common") : EnchantmentInstance {
		/** @noinspection PhpUnhandledExceptionInspection */
		$class = new \ReflectionClass(CustomEnchants::class);
		/** @var Enchantment[] $enchantments */
		$enchantments = $class->getStaticPropertyValue("enchantments", []);
		switch($type) {
			case "Mythic":
				/** @var Enchantment[] $typeEnchantments */
				$typeEnchantments = array_filter($enchantments, function(Enchantment $enchantment){
					if($enchantment->getRarity() === Enchantment::RARITY_MYTHIC)
						return true;
					return false;
				});
			break;
			case "Rare":
				/** @var Enchantment[] $typeEnchantments */
				$typeEnchantments = array_filter($enchantments, function(Enchantment $enchantment){
					if($enchantment->getRarity() === Enchantment::RARITY_RARE)
						return true;
					return false;
				});
			break;
			case "Common":
			default:
			/** @var Enchantment[] $typeEnchantments */
			$typeEnchantments = array_filter($enchantments, function(Enchantment $enchantment){
				if($enchantment->getRarity() === Enchantment::RARITY_COMMON)
					return true;
				return false;
			});
			break;
		}

		return new EnchantmentInstance($typeEnchantments[array_rand($typeEnchantments)], mt_rand(1, 10));
	}

	/**
	 * @param int $dropTick
	 * @param int $currentTick
	 */
	public function updateDropTime(int $dropTick, int $currentTick) : void {
		$this->envoyDropTime = ($dropTick - $currentTick) / (20 * 60);
	}

	/**
	 * @return int minutes until next scheduled envoy drop
	 */
	public function getEnvoyDropTime() : int {
		return $this->envoyDropTime;
	}

	/**
	 * @param null|Player $player
	 * @param Form $form
	 * @param int $delay
	 */
	public static function sendPlayerDelayedForm(?Player $player, Form $form, int $delay = 1) : void {
		if($player !== null)
			Server::getInstance()->getScheduler()->scheduleDelayedTask(new DelayedFormTask(self::$instance, $form, $player), $delay);
	}

	/**
	 * @return Config
	 */
	public function getEnvoyConfig() : Config {
		return $this->envoyConfig;
	}
}