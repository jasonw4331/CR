<?php
/**
 * Created by PhpStorm.
 * User: AJ
 * Date: 22/09/2017
 * Time: 20:34
 */

namespace Crates\task;


use Crates\crate\CrateBlock;
use Crates\crate\CrateContent;
use Crates\heart\HeartTask;
use Crates\Loader;
use lib\Selector;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\ItemFactory;
use pocketmine\level\particle\BubbleParticle;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\math\Vector3;
use pocketmine\nbt\JsonNBTParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\Server;

class PlayCrateTask extends HeartTask {

    /** @var CrateBlock */
    private $block;

    /** @var FloatingTextParticle */
    private $previousParticle;

    /** @var FloatingTextParticle */
    private $currentParticle;

    /** @var FloatingTextParticle */
    private $nextParticle;

    /** @var Player */
    private $player;

    /** @var Selector */
    private $selector;

    /** @var bool */
    private $ended = false;

    /**
     * PlayCrateTask constructor.
     * @param CrateBlock $block
     * @param Player $player
     */
    public function __construct(CrateBlock $block, Player $player) {
	    Loader::getInstance()->getSessionManager()->getSession($player)->setInCrate();

        $this->block = $block;
        $this->player = $player;

        $this->previousParticle = new FloatingTextParticle($block->add(0.5, 2.25, 0.5), "");
        $this->currentParticle = new FloatingTextParticle($block->add(0.5, 2, 0.5), "");
        $this->nextParticle = new FloatingTextParticle($block->add(0.5, 1.75, 0.5), "");
        $this->selector = new Selector($block->getCrate()->getContent());

        $block->level->addParticle($this->previousParticle, [$player]);
        $block->level->addParticle($this->currentParticle, [$player]);
        $block->level->addParticle($this->nextParticle, [$player]);

        parent::__construct(1);
    }

    private function move() {
        $this->block->level->addSound(new ClickSound($this->block->asVector3()), [$this->player]);
        $this->selector->next();
        $this->previousParticle->setTitle("— " . $this->selector->getPrevious()->getRouletteMessage());
        $this->currentParticle->setTitle("- ". $this->selector->current()->getRouletteMessage());
        $this->nextParticle->setTitle("— " . $this->selector->getNext()->getRouletteMessage());
        $this->updateParticles();
        if($this->getPeriod() >= 16) {
            $this->ended = true;
        } elseif($this->getPeriod() > 10) {
            $this->setPeriod($this->getPeriod() + 5);
        } else {
            $this->setPeriod($this->getPeriod() + 1);
        }
    }

    public function tick() {
        if($this->ended) {
            $session = Loader::getInstance()->getSessionManager()->getSession($this->player);
            if($session != null) {
                /** @var CrateContent $content */
                $content = $this->selector->current();
                $username = $this->player->getName();
                $victoryMessage = str_replace("{player}", $username, $content->getWonMessage());
                $this->previousParticle->setInvisible();
                $this->nextParticle->setInvisible();
                $this->currentParticle->setTitle($victoryMessage);
                $level = $this->block->level;
                $level->addSound(new EndermanTeleportSound($this->block), [$this->player]);
                $level->addParticle(new BubbleParticle($this->block), [$this->player]);
                $this->updateParticles();
                $this->player->sendMessage($victoryMessage);
                $server = Server::getInstance();
                $session->removeCrateKey($this->block->getCrate()->getIdentifier());
                foreach($content->getCommands() as $command) {
                    $arr = explode(" ", $command);
                    if(array_shift($arr) === "give") { // remove "give"
                        array_shift($arr); // remove username
                        $split = explode(":", array_shift($arr)); // remove id:damage
                        $count = array_shift($arr); // remove count
                        $json = (string)array_shift($arr); // remove json NBT data
                        try{
                            /** @var CompoundTag $tags */
                            $tags = JsonNBTParser::parseJSON($json);
                        }catch(\Throwable $ex) {
                            continue;
                        }
                        if($this->block->x >= 0)
                            $pos = $this->block->add(0.5);
                        else
                            $pos = $this->block->subtract(0.5);
                        if($pos->z >= 0)
                            $pos = $pos->add(0, 0, 0.5);
                        else
                            $pos = $pos->subtract(0, 0, 0.5);
	                    /** @noinspection PhpUnhandledExceptionInspection */
	                    $entity = $level->dropItem(
                        	$pos->add(0, 1),
	                        ItemFactory::get((int) $split[0], (int) ($split[1] ?? 0), (int)($count ?? 1), ($tags ?? "")),
	                        new Vector3(0, 0, 0),
	                        32767);
                        if($entity !== null)
                            $server->getScheduler()->scheduleDelayedTask(new RemoveItemEntityTask($entity), 120);
                    }
                    $server->dispatchCommand(new ConsoleCommandSender(), str_replace("{player}", $username, $command));
                }
                $server->getScheduler()->scheduleDelayedTask(new RemoveCrateTask($this->currentParticle, $this->player), 120);
            }
            $this->stop();
        } else {
            $this->move();
        }
    }

    private function updateParticles() {
        foreach($this->previousParticle->encode() as $packet) {
            $this->player->dataPacket($packet);
        }
        foreach($this->currentParticle->encode() as $packet) {
            $this->player->dataPacket($packet);
        }
        foreach($this->nextParticle->encode() as $packet) {
            $this->player->dataPacket($packet);
        }
    }

}