<?php
/**
 * Created by PhpStorm.
 * User: AJ
 * Date: 22/09/2017
 * Time: 19:50
 */

namespace Crates\session;


use jasonwynn10\CR\Main;
use pocketmine\Player;
use pocketmine\Server;

class SessionManager {
    
    /** @var Session[] */
    private $sessionPool = [];
    
    /**
     * SessionManager constructor.
     */
    public function __construct() {
        @mkdir(Main::getInstance()->getDataFolder() . "users");
        $this->openAll();
        Server::getInstance()->getPluginManager()->registerEvents(new SessionListener($this), Main::getInstance());
    }
    
    /**
     * @return Session[]
     */
    public function getSessionPool(): array {
        return $this->sessionPool;
    }
    
    /**
     * @param Player $owner
     * @return Session|null
     */
    public function getSession(Player $owner) {
        return $this->sessionPool[$owner->getName()] ?? null;
    }
    
    /**
     * @param Player $owner
     */
    public function openSession(Player $owner) {
        $this->sessionPool[$owner->getName()] = new Session($owner);
    }
    
    public function openAll() {
        foreach(Server::getInstance()->getOnlinePlayers() as $player) {
            $this->openSession($player);
        }
    }
    
    /**
     * @param Player $owner
     */
    public function closeSession(Player $owner) {
        if(isset($this->sessionPool[$owner->getName()])) {
            unset($this->sessionPool[$owner->getName()]);
        }
    }
    
    public function closeAll() {
    	Main::getInstance()->getLogger()->debug("Saving Crates");
        foreach($this->sessionPool as $session) {
            $session->despawnCrateParticles();
            $this->closeSession($session->getOwner());
        }
    }
    
}