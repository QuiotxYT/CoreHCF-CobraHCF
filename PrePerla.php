<?php

namespace VitalHCF\item\specials;

use VitalHCF\Loader;

use VitalHCF\player\Player;

use pocketmine\utils\TextFormat as TE;

use pocketmine\nbt\tag\CompoundTag;

class NinjaShear extends Custom {

		const CUSTOM_ITEM = "CustomItem";

	

	const USES_LEFT = "Uses left: ";

	

	/**

	 * NinjaShear Constructor.

	 * @param Int $usesLeft

	 */

	public function __construct(Int $usesLeft = 5){

		parent::__construct(\pocketmine\item\Item::SHEARS, str_replace(["&", "%n%"], ["§", "\n"], Loader::getDefaultConfig("Specials")["NinjaShear"]["name"]), [str_replace(["&", "%n%", "{usesLeft}"], ["§", "\n", $usesLeft], Loader::getDefaultConfig("Specials")["NinjaShear"]["lore"])]);

		$this->setNamedTagEntry(new CompoundTag(self::CUSTOM_ITEM));

		$this->getNamedTagEntry(self::CUSTOM_ITEM)->setInt(self::USES_LEFT, $usesLeft);

	}

	

	/**

	 * @param Player $player

	 * @return void

	 */

	public function reduceUses(Player $player) : void {

		$compound = $this->getNamedTagEntry(self::CUSTOM_ITEM)->getInt(self::USES_LEFT);

		if($compound > 0){

			$compound--;

			if($compound === 0){

				//TODO:

				$player->getInventory()->setItemInHand(\pocketmine\item\Item::get(\pocketmine\item\Item::AIR));

			}else{

				//TODO:

				$this->getNamedTagEntry(self::CUSTOM_ITEM)->setInt(self::USES_LEFT, $compound);

				$this->setLore([str_replace(["&", "%n%", "{usesLeft}"], ["§", "\n", $compound], Loader::getDefaultConfig("Specials")["PrePerla"]["lore"])]);

				$player->getInventory()->setItemInHand($this);

			}

		}

	}

	

	/**

     * @return Int

     */

    public function getMaxStackSize() : Int {

        return 1;

    }

}

?>
