<?php
declare(strict_types=1);

namespace jasonwynn10\CR\entity;

use jasonwynn10\CR\Main;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\Player;

class Envoy extends Entity {

	public const NETWORK_ID = self::ENDER_CRYSTAL;

	public $width = 0.98;

	public $height = 0.98;

	public function initEntity() {
		parent::initEntity();
		if($this->namedtag->hasTag("ShowBottom", ByteTag::class)) {
			$this->namedtag->setByte("ShowBottom", 0);
		}
		$this->setMaxHealth(1000);
		$this->setHealth(999);
		$this->setCanSaveWithChunk(false); // when the server stops, don't save the envoy
		$this->setNameTagAlwaysVisible(true);
		$this->setNameTagVisible(true);
		$this->setForceMovementUpdate(true);
	}

	public function entityBaseTick(int $tickDiff = 1) : bool {
		if($this->closed) {
			return false;
		}
		$hasUpdate = parent::entityBaseTick($tickDiff);
		if(!$this->isFlaggedForDespawn() and $this->onGround) {
			$this->namedtag->setByte("ShowBottom", 1);
			$this->setImmobile(true);
			$this->setHealth(1);
			$hasUpdate = true;
		} elseif(!$this->onGround) {
			$this->setForceMovementUpdate(true);
			$this->setMotion(new Vector3(0, -2.5, 0));
			$hasUpdate = true;
		}
		return $hasUpdate;
	}

	public function attack(EntityDamageEvent $source) {
		if($source instanceof EntityDamageByEntityEvent) {
			$player = $source->getDamager();
			if($player instanceof Player) {
				foreach($this->getDrops() as $item) {
					$item = $this->getLevel()->dropItem($this, $item);
					if($item !== null) {
						$item->setCanSaveWithChunk(false);
					}
				}
			}
		}
		return parent::attack($source);
	}

	public function getDrops() : array {
		$rand = mt_rand(1, 100);
		$main = Main::getInstance();
		switch($this->getNameTag()) {
			//case "Legendary Envoy":
			//	$drops = $main->getEnvoyDrops("Mystical");
			//break;
			case "Mystical Envoy":
				$drops = $main->getEnvoyDrops("Mystical");
				if($rand > 90) {
					$item = Item::get(Item::ENCHANTED_BOOK);
					/** @noinspection PhpUnhandledExceptionInspection */
					$item->addEnchantment($main->getRandomCE("Rare"));
					$drops[] = $item;
				}
				break;
			case "Rare Envoy":
				$drops = $main->getEnvoyDrops("Rare");
				if($rand > 90) {
					$item = Item::get(Item::ENCHANTED_BOOK);
					/** @noinspection PhpUnhandledExceptionInspection */
					$item->addEnchantment($main->getRandomCE("Mythic"));
					$drops[] = $item;
				}
				break;
			default:
			case "Common Envoy":
				$drops = $main->getEnvoyDrops("Common");
				if($rand > 90) {
					$item = Item::get(Item::ENCHANTED_BOOK);
					/** @noinspection PhpUnhandledExceptionInspection */
					$item->addEnchantment($main->getRandomCE("Common"));
					$drops[] = $item;
				}
				break;
		}
		return $drops;
	}
}