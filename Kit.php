<?php

namespace VitalHCF\kit;

use VitalHCF\Loader;

use VitalHCF\player\Player;

use pocketmine\utils\{Config, TextFormat as TE};

use pocketmine\level\Level;

class Kit {

		/** @var Array[] */

	public $items = [], $armorItems = [];

	

	/** @var String */

	public $name = "", $permission = "", $nameFormat = "";

	

	/**

	 * @param String $name

	 * @param Array $items

	 * @param String $permission

	 * @param Int $cooldown

	 */

	public function __construct(?String $name, ?Array $items = [], ?Array $armorItems = [], ?String $permission, ?String $nameFormat = null){

		$this->name = $name;

		$this->items = $items;

		$this->armorItems = $armorItems;

		$this->permission = $permission;

		$this->nameFormat = $nameFormat;

	}

	

	/**

	 * @return String

	 */

	public function getName() : ?String {

		return $this->name;

	}

	

	/**

	 * @return Array[]

	 */

	public function getItems() : ?Array {

		return $this->items;

	}

	

	/**

	 * @return Array[]

	 */

	public function getArmorItems() : ?Array {

		return $this->armorItems;

	}

	/**

	 * @return String

	 */

	public function getPermission() : ?String {

		return $this->permission;

	}

	

	/**

	 * @return String

	 */

	public function getNameFormat() : ?String {

		return $this->nameFormat;

	}

}

?>
