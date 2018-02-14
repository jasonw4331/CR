<?php
/**
 * Created by PhpStorm.
 * User: AJ
 * Date: 22/09/2017
 * Time: 23:57
 */

namespace Crates\crate;

use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;

class CrateFloatingText extends FloatingTextParticle {

	/** @var Crate */
	private $crate;

	/**
	 * CrateFloatingText constructor.
	 *
	 * @param Crate   $crate
	 * @param Vector3 $pos
	 * @param string  $title
	 */
	public function __construct(Crate $crate, Vector3 $pos, $title) {
		$this->crate = $crate;
		parent::__construct($pos, "", $title);
	}

	/**
	 * @return Crate
	 */
	public function getCrate() : Crate {
		return $this->crate;
	}

}