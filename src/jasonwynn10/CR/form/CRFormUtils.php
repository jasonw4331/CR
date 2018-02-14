<?php

declare(strict_types=1);

namespace jasonwynn10\CR\form;

use jojoe77777\FormAPI\FormAPI;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

class CRFormUtils {

	/** @var FormAPI|null */
	private static $formApi = null;

	public static function init() {
		if(static::$formApi === null) {
			$plugin = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
			if($plugin instanceof Plugin and $plugin->isEnabled()) {
				static::$formApi = $plugin;
			}
		}
	}

	/**
	 * @return FormAPI|null
	 */
	public static function getFormAPI() {
		return static::$formApi;
	}

}