<?php
/**
 * Created by PhpStorm.
 * User: AJ
 * Date: 22/09/2017
 * Time: 18:31
 */

namespace Crates\crate;

use Crates\Loader;
use Crates\task\PlayCrateTask;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Crate {
    
    /** @var string */
    private $name;
    
    /** @var string */
    private $identifier;
    
    /** @var CrateContent[] */
    private $content = [];
    
    /**
     * Crate constructor.
     * @param string $name
     * @param string $identifier
     * @param array $content
     */
    public function __construct(string $name, string $identifier, array $content) {
        $this->name = $name;
        $this->identifier = $identifier;
        $this->content = $content;
    }
    
    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
    
    /**
     * @return string
     */
    public function getIdentifier(): string {
        return $this->identifier;
    }
    
    /**
     * @return CrateContent[]
     */
    public function getContent(): array {
        return $this->content;
    }
    
    /**
     * @param CrateBlock $block
     * @param Player $player
     */
    public function execute(CrateBlock $block, Player $player) {
        $session = Loader::getInstance()->getSessionManager()->getSession($player);
        if(!$session->isInCrate()) {
            if($session->hasCrateKey($this->identifier)) {
                Loader::getInstance()->getHeart()->startTask(new PlayCrateTask($block, $player));
            } else {
                $player->sendMessage(TextFormat::RED . "> " . TextFormat::WHITE . " You need a {$this->name} key to open this crate");
            }
        }
    }
    
}