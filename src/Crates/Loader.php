<?php
/**
 * Created by PhpStorm.
 * User: AJ
 * Date: 22/09/2017
 * Time: 18:30
 */

namespace Crates;


use Crates\crate\CrateManager;
use Crates\heart\Heart;
use Crates\session\SessionManager;
use jasonwynn10\CR\Main;

class Loader {
    
    /** @var Loader */
    private static $instance;
    
    /** @var Heart */
    private $heart;
    
    /** @var SessionManager */
    private $sessionManager;
    
    /** @var CrateManager */
    private $crateManager;

    public function __construct(Main $plugin) {
	    self::$instance = $this;
	    $this->heart = new Heart($this);
	    $this->sessionManager = new SessionManager();
	    $this->crateManager = new CrateManager();
	    $plugin->getServer()->getPluginManager()->registerEvents(new Listener(), $plugin);
	    $plugin->getServer()->getCommandMap()->register("cr", new Command($plugin));
	    $plugin->getLogger()->info("Crates have been loaded!");
    }

    public function onDisable() : void {
	    $this->sessionManager->closeAll();
    }
    
    /**
     * @return Loader
     */
    public static function getInstance(): Loader {
        return self::$instance;
    }
    
    /**
     * @return Heart
     */
    public function getHeart(): Heart {
        return $this->heart;
    }
    
    /**
     * @return SessionManager
     */
    public function getSessionManager(): SessionManager {
        return $this->sessionManager;
    }
    
    /**
     * @return CrateManager
     */
    public function getCrateManager(): CrateManager {
        return $this->crateManager;
    }
    
}