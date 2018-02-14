<?php
/**
 * Created by PhpStorm.
 * User: AJ
 * Date: 22/09/2017
 * Time: 19:57
 */

namespace Crates\crate;

use Crates\Utils;
use jasonwynn10\CR\Main;
use pocketmine\level\Position;
use pocketmine\utils\Config;

class CrateManager {

	/** @var Crate[] */
	private $cratePool = [];

	/** @var CrateBlock[] */
	private $blockPool = [];

	private function checkFolders() {
		@mkdir(Main::getInstance()->getDataFolder() . "crates");
		Main::getInstance()->saveResource("blocks.json");
	}

	/**
	 * CrateManager constructor.
	 */
	public function __construct() {
		$this->checkFolders();
		$this->parseCrates();
		$this->parseBlocks();
	}

	/**
	 * @return Crate[]
	 */
	public function getCratePool() : array {
		return $this->cratePool;
	}

	/**
	 * @param string $identifier
	 *
	 * @return Crate|null
	 */
	public function getCrate(string $identifier) {
		return $this->cratePool[$identifier] ?? null;
	}

	/**
	 * @return CrateBlock[]
	 */
	public function getBlockPool() : array {
		return $this->blockPool;
	}

	/**
	 * @param string $name
	 * @param string $identifier
	 * @param array  $content
	 */
	public function addCrate(string $name, string $identifier, array $content) {
		$this->cratePool[$identifier] = new Crate($name, $identifier, $content);
		Main::getInstance()->getLogger()->debug("Successfully loaded a Crate");
	}

	/**
	 * @param Crate    $crate
	 * @param Position $position
	 *
	 * @return CrateBlock
	 */
	public function addCrateBlock(Crate $crate, Position $position) : CrateBlock {
		$crateBlock        = new CrateBlock($crate, $position->x, $position->y, $position->z, $position->level);
		$this->blockPool[] = $crateBlock;
		Main::getInstance()->getLogger()->debug("Successfully loaded a CrateBlock");
		return $crateBlock;
	}

	public function parseCrates() {
		foreach(scandir($path = Main::getInstance()->getDataFolder() . "crates" . DIRECTORY_SEPARATOR) as $file) {
			$parts = explode(".", $file);
			if($file == "." or $file == "..") {
				continue;
			}
			if(is_file($path . $file) and isset($parts[1]) and $parts[1] == "json") {
				$config  = new Config($path . $file);
				$content = [];
				foreach($config->get("content", []) as $array) {
					$content[] = new CrateContent(($array["rouletteMessage"] ?? "Undefined message"),
						($array["commands"] ?? []),
						($array["message"] ?? "Undefined won message"));
				}
				$this->addCrate($config->get("name", "Undefined"), $config->get("identifier", "Undefined"), $content);
			} else {
				Main::getInstance()->getLogger()->error("Error while parsing {$file} as crate because it is not a valid JSON file");
			}
		}
	}

	public function parseBlocks() {
		$config = new Config(Main::getInstance()->getDataFolder() . "blocks.json", Config::JSON, []);
		foreach($config->getAll() as $block) {
			if(isset($block[1])) {
				$crate    = $this->cratePool[$block[0]] ?? null;
				$position = Utils::parsePosition($block[1]);
				if(!(is_null($crate)) and !is_null($position)) {
					$this->addCrateBlock($crate, $position);
				}
			} else {
				Main::getInstance()->getLogger()->debug("Error while loading a CrateBlock, no data found.");
			}
		}
	}

}