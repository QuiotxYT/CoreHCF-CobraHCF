<?php

namespace VitalHCF;

use VitalHCF\player\Player;

use VitalHCF\Loader;

use VitalHCF\provider\YamlProvider;

use VitalHCF\Task\FreezeTimeTask;

use pocketmine\Server;

use pocketmine\block\Block;

use pocketmine\math\Vector3;

use pocketmine\utils\{Config, TextFormat as TE};

use pocketmine\level\{Level, Position};

use pocketmine\network\mcpe\protocol\BlockActorDataPacket;

use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

class Factions {

    const FACTION = "Faction", SPAWN = "Spawn", PROTECTION = "Protection";

    /**

     * @return void

     */

    public static function init() : void {

    	if(count(self::getAllFactions()) === 0){    		return;

    	}

        foreach(self::getAllFactions() as $factionName){

            if(self::getStrength($factionName) < self::getMaxStrength($factionName)){

                Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new FreezeTimeTask($factionName, self::getFreezeTime($factionName)), 0);

            }

        }

    }

    /**

     * @param String $factionName

     * @param Int $time

     * @return void

     */

    public static function setFreezeTime(String $factionName, Int $time = 0) : void {

        $config = new Config(Loader::getInstance()->getDataFolder()."TimeFreeze.yml", Config::YAML);

        $config->set($factionName, $time);

        $config->save();

    }

    /**

     * @param String $factionName

     * @return Int

     */

    public static function getFreezeTime(String $factionName) : ?Int {

        $config = new Config(Loader::getInstance()->getDataFolder()."TimeFreeze.yml", Config::YAML);

        return $config->get($factionName);

    }

    /**

     * @param String $factionName

     * @return bool

     */

    public static function isFreezeTime(String $factionName) : bool {

        $config = new Config(Loader::getInstance()->getDataFolder()."TimeFreeze.yml", Config::YAML);

        if($config->exists($factionName)){

            return true;

        }else{

            return false;

        }

        return false;

    }

    /**

     * @param String $factionName

     * @return void

     */

    public static function removeFreezeTime(String $factionName) : void {

        $config = new Config(Loader::getInstance()->getDataFolder()."TimeFreeze.yml", Config::YAML);

        if(self::isFreezeTime($factionName)){

            $config->remove($factionName);

            $config->save();

        }

    }

    /**

     * @param String $playerName

     * @return bool

     */

    public static function inFaction(String $playerName) : bool {

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM player_data WHERE playerName = '$playerName';");

        $result = $data->fetchArray(SQLITE3_ASSOC);

        if(!empty($result)){

        	$data->finalize();

            return true;

        }else{

        	$data->finalize();

            return false;

        }

        $data->finalize();

        return false;

    }

    

    /**

     * @param String $playerName

     * @return String|null

     */

    public static function getFaction(String $playerName) : ?String {

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM player_data WHERE playerName = '$playerName';");

        $result = $data->fetchArray(SQLITE3_ASSOC);

        if(empty($result)){

			return null;

		}

    	return $result["factionName"];

    }

    /**

     * @param String $playerName

     * @return String|null

     */

    public static function getFactionRank(String $playerName) : ?String {

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM player_data WHERE playerName = '$playerName';");

        $result = $data->fetchArray(SQLITE3_ASSOC);

        if(empty($result)){

			return null;

		}

    	return $result["factionRank"];

    }

    /**

     * @param String $playerName

     * @return void

     */

    public static function addToFaction(String $playerName, String $factionName, String $factionRank = Player::LEADER) : void {

        $data = Loader::getProvider()->getDataBase()->prepare("INSERT OR REPLACE INTO player_data(playerName, factionRank, factionName) VALUES (:playerName, :factionRank, :factionName);");

    	$data->bindValue(":playerName", $playerName);

    	$data->bindValue(":factionRank", $factionRank);

    	$data->bindValue(":factionName", $factionName);

        $data->execute();

        foreach(self::getPlayers($factionName) as $player){

            $online = Loader::getInstance()->getServer()->getPlayer($player);

            if($online instanceof Player){

                $online->sendMessage(str_replace(["&", "{playerName}"], ["§", $playerName], Loader::getConfiguration("messages")->get("player_join_to_faction_correctly")));

            }

        }

    }

    /**

     * @param String $playerName

     * @return void

     */

    public static function removeToFaction(String $playerName) : void {

        foreach(self::getPlayers(self::getFaction($playerName)) as $player){

            $online = Loader::getInstance()->getServer()->getPlayer($player);

            if($online instanceof Player){

                $online->sendMessage(str_replace(["&", "{playerName}"], ["§", $playerName], Loader::getConfiguration("messages")->get("player_leave_to_faction_correctly")));

            }

        }

        Loader::getProvider()->getDataBase()->query("DELETE FROM player_data WHERE playerName = '$playerName';");

    }

    /**

     * @param String $factionName

     * @param Player $player

     */

    public static function createNewFaction(String $factionName, Player $player){

        if(self::isFaction($factionName)){

            $player->sendMessage(str_replace(["&", "{factionName}"], ["§", $factionName], Loader::getConfiguration("messages")->get("faction_exists")));

            return;

        }else{

            $player->sendMessage(str_replace(["&", "{factionName}"], ["§", $factionName], Loader::getConfiguration("messages")->get("faction_create")));

            self::addToFaction($player->getName(), $factionName, Player::LEADER);

            self::setStrength($factionName, 2);

            Loader::getInstance()->getServer()->broadcastMessage(str_replace(["&", "{factionName}", "{playerName}"], ["§", $factionName, $player->getNameTag()], Loader::getConfiguration("messages")->get("faction_create_correctly")));

        }

    }

    /**

     * @param String $factionName

     * @param Player $player

     */

    public static function deleteFaction(String $factionName){

        foreach(self::getPlayers($factionName) as $player){

            self::removeToFaction($player);

        }

        Loader::getProvider()->getDataBase()->exec("DELETE FROM player_data WHERE playerName = '$factionName';");

		Loader::getProvider()->getDataBase()->exec("DELETE FROM strength WHERE factionName = '$factionName';");

		Loader::getProvider()->getDataBase()->exec("DELETE FROM zoneclaims WHERE factionName = '$factionName';");

		Loader::getProvider()->getDataBase()->exec("DELETE FROM homes WHERE factionName = '$factionName';");

		Loader::getProvider()->getDataBase()->exec("DELETE FROM balance WHERE factionName = '$factionName';");

        Loader::getInstance()->getServer()->broadcastMessage(str_replace(["&", "{factionName}"], ["§", $factionName], Loader::getConfiguration("messages")->get("faction_delete_correctly")));

    }

    /**

     * @return Array[]

     */

    public static function getAllFactions() : Array {

        $factions = [];

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM player_data;");

        while($result = $data->fetchArray(SQLITE3_ASSOC)){

            $factions[] = $result["factionName"];

        }

        return $factions;

    }

    /**

     * @param String $factionName

     * @return bool 

     */

    public static function isFaction(String $factionName) : bool {

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM player_data WHERE factionName = '$factionName';");

        $result = $data->fetchArray(SQLITE3_ASSOC);

        if(!empty($result)){

        	$data->finalize();

            return true;

        }else{

        	$data->finalize();

            return false;

        }

        $data->finalize();

        return false;

    }

    

    /**

	 * @param String $factionName

	 * @return String|null.

	 */

    public static function getListPlayers(String $factionName) : ?String {

    	$players = [];

    	$data = Loader::getProvider()->getDataBase()->query("SELECT * FROM player_data WHERE factionName = '$factionName';");

    	while($result = $data->fetchArray(SQLITE3_ASSOC)){

            if(Loader::getInstance()->getServer()->getPlayer($result["playerName"]) instanceof Player){

                $players[] = TE::GREEN.Loader::getInstance()->getServer()->getPlayer($result["playerName"])->getName().TE::YELLOW."[".TE::GREEN.YamlProvider::getKills($result["playerName"]).TE::YELLOW."]";

            }else{

                $players[] = TE::GRAY.$result["playerName"].TE::YELLOW."[".TE::GREEN.YamlProvider::getKills($result["playerName"]).TE::YELLOW."]";

            }

    	}

    	return implode(", ", $players);

    }

    

    /**

	 * @param String $factionName

	 * @return Array|null

	 */

    public static function getPlayers(String $factionName) : ?Array {

    	$players = [];

    	$data = Loader::getProvider()->getDataBase()->query("SELECT * FROM player_data WHERE factionName = '$factionName';");

    	while($result = $data->fetchArray(SQLITE3_ASSOC)){        

            $players[] = $result["playerName"];

    	}

        return $players;

    }

    

    /**

     * @param String $factionName

     * @return Int|null

     */

    public static function getMaxPlayers(String $factionName) : ?Int {

    	$data = Loader::getProvider()->getDataBase()->query("SELECT COUNT(playerName) as maxplayers FROM player_data WHERE factionName = '$factionName';");

		$result = $data->fetchArray();

		if($result["maxplayers"] === 0){

			return null;

		}

		return $result["maxplayers"];

    }

    

    /**

     * @param String $factionName

     * @return String|null

     */

    public static function getLeader(String $factionName) : ?String {

    	$data = Loader::getProvider()->getDataBase()->query("SELECT * FROM player_data WHERE factionName = '$factionName' and factionRank = 'Leader';");

    	$result = $data->fetchArray(SQLITE3_ASSOC);

    	if(empty($result)){

			return null;

		}

    	return $result["playerName"];

    }

    

    /**

     * @param String $factionName

     * @return String|null

     */

    public static function getCoLeader(String $factionName) : ?String {

    	$data = Loader::getProvider()->getDataBase()->query("SELECT * FROM player_data WHERE factionName = '$factionName' and factionRank = 'Co_Leader';");

    	$result = $data->fetchArray(SQLITE3_ASSOC);

    	if(empty($result)){

			return null;

		}

    	return $result["playerName"];

    }

    /**

     * @param String $factionName

     * @return Int|null

     */

    public static function getStrength(String $factionName) : ?Int {

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM strength WHERE factionName = '$factionName';");

		$result = $data->fetchArray(SQLITE3_ASSOC);

		return $result["dtr"];

    }

    /**

	 * @param String $factionName

     * @return void

	 */

	public static function reduceStrength(String $factionName) : void {

		if(self::getStrength($factionName) === 1){

			self::setStrength($factionName, 0);

		}else{

			self::setStrength($factionName, self::getStrength($factionName) - 1);

		}

    }

    

    /**

     * @param String $factionName

     * @return Int

     */

    public static function getMaxStrength(String $factionName) : Int {

        $players = self::getMaxPlayers($factionName);

        $max = $players + 1;

        if(self::getStrength($factionName) > $max){

            self::setStrength($factionName, $max);

        }

        return $max;

    }

    /**

     * @param String $factionName

     * @param Int $strength

     */

    public static function setStrength(String $factionName, Int $strength){

        $data = Loader::getProvider()->getDataBase()->prepare("INSERT OR REPLACE INTO strength(factionName, dtr) VALUES (:factionName, :dtr);");

		$data->bindValue(":factionName", $factionName);

		$data->bindValue(":dtr", $strength);

        $data->execute();

    }

    /**

     * @param String $factionName

     * @return Int|null

     */

    public static function getBalance(String $factionName) : ?Int {

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM balance WHERE factionName = '$factionName';");

        $result = $data->fetchArray(SQLITE3_ASSOC);

        if(empty($result)){

			return 0;

		}

		return $result["money"];

    }

    /**

     * @param String $factionName

     * @param Int $balance

     * @return void

     */

    public static function addBalance(String $factionName, Int $balance) : void {

        self::setBalance($factionName, self::getBalance($factionName) + $balance);

    }

    /**

     * @param String $factionName

     * @param Int $balance

     * @return void

     */

    public static function reduceBalance(String $factionName, Int $balance) : void {

        self::setBalance($factionName, self::getBalance($factionName) - $balance);

    }

    /**

     * @param String $factionName

     * @param Int $balance

     * @return void

     */

    public static function setBalance(String $factionName, Int $balance) : void {

        $data = Loader::getProvider()->getDataBase()->prepare("INSERT OR REPLACE INTO balance(factionName, money) VALUES (:factionName, :money);");

        $data->bindValue(":factionName", $factionName);

		$data->bindValue(":money", $balance);

		$data->execute();

    }

    /**

     * @param String $factionName

     * @return String|null 

     */

    public static function getFactionHomeString(String $factionName) : ?String {

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM homes WHERE factionName = '$factionName';");

        $result = $data->fetchArray(SQLITE3_ASSOC);

        if(empty($result)){

			return "They don't have a home";

		}

        return "X: ".$result["x"]." Y: ".$result["y"]." Z: ".$result["z"];

    }

    /**

     * @param String $factionName

     * @return bool

     */

    public static function isHome(String $factionName) : bool {

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM homes WHERE factionName = '$factionName';");

        $result = $data->fetchArray(SQLITE3_ASSOC);

        if(!empty($result)){

        	$data->finalize();

            return true;

        }else{

        	$data->finalize();

            return false;

        }

        $data->finalize();

        return false;

    }

    /**

     * @param String $factionName

     * @return Position|null

     */

     # NOTE: Here we use Position and not Vector3 because Position has the function of level and Vector3 does not

    public static function getFactionHomeLocation(String $factionName) : ?Position {

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM homes WHERE factionName = '$factionName';");

        $result = $data->fetchArray(SQLITE3_ASSOC);

        $level = Loader::getInstance()->getServer()->getLevelByName($result["level"]);

        if(empty($result)){

			return null;

		}

        return new Position($result["x"], $result["y"], $result["z"], $level);

    }

    /**

     * @param String $factionName

     * @param Vector3 $position

     * @return void

     */

    public static function setFactionHome(String $factionName, Vector3 $position) : void {

        $data = Loader::getProvider()->getDataBase()->prepare("INSERT OR REPLACE INTO homes(factionName, x, y, z, level) VALUES (:factionName, :x, :y, :z, :level);");

		$data->bindValue(":factionName", $factionName);

		$data->bindValue(":x", $position->getFloorX());

		$data->bindValue(":y", $position->getFloorY());

		$data->bindValue(":z", $position->getFloorZ());

		$data->bindValue(":level", $position->getLevel()->getName());

        $data->execute();

    }

    /**

     * @param String $factionName

     * @param Level|null $level

     * @param Array $position1

     * @param Array $position2

     * @param String $protection

     */

    public static function claimRegion(String $factionName, ?String $level, Array $position1, Array $position2, String $protection = "Faction") : void {

        $xMin = min($position1[0], $position2[0]);

		$xMax = max($position1[0], $position2[0]);

		

		$zMin = min($position1[2], $position2[2]);

		$zMax = max($position1[2], $position2[2]);

		

		$yMin = min(0, 250);

        $yMax = max(0, 250);

        $data = Loader::getProvider()->getDataBase()->prepare("INSERT OR REPLACE INTO zoneclaims(factionName, protection, x1, z1, x2, z2, level) VALUES (:factionName, :protection, :x1, :z1, :x2, :z2, :level);");

        $data->bindValue(":factionName", $factionName);

        $data->bindValue(":protection", $protection);

        $data->bindValue(":x1", $xMin);

        $data->bindValue(":z1", $zMin);

        $data->bindValue(":x2", $xMax);

        $data->bindValue(":z2", $zMax);

        $data->bindValue(":level", $level);

        $data->execute();

    }

    /**

     * @param Vector3 $position

     * @return void

     */

    public static function isSpawnRegion(Vector3 $position) : bool {

        $x = $position->getFloorX();

        $z = $position->getFloorZ();

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM zoneclaims WHERE $x >= x1 AND $x <= x2 AND $z >= z1 AND $z <= z2 AND protection = 'Spawn';");

        $result = $data->fetchArray(SQLITE3_ASSOC);

        if(!empty($result) and $position->getLevel()->getName() === $result["level"]){

        	$data->finalize();

            return true;

        }else{

        	$data->finalize();

            return false;

        }

        $data->finalize();

        return false;

    }

    /**

     * @param Vector3 $position

     * @return void

     */

    public static function isProtectedRegion(Vector3 $position) : bool {

        $x = $position->getFloorX();

        $z = $position->getFloorZ();

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM zoneclaims WHERE $x >= x1 AND $x <= x2 AND $z >= z1 AND $z <= z2 AND protection = 'Protection';");

        $result = $data->fetchArray(SQLITE3_ASSOC);

        if(!empty($result) and $position->getLevel()->getName() === $result["level"]){

        	$data->finalize();

            return true;

        }else{

        	$data->finalize();

            return false;

        }

        $data->finalize();

        return false;

    }

    /**

     * @param Vector3 $position

     * @return void

     */

    public static function isFactionRegion(Vector3 $position) : bool {

        $x = $position->getFloorX();

        $z = $position->getFloorZ();

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM zoneclaims WHERE $x >= x1 AND $x <= x2 AND $z >= z1 AND $z <= z2 AND protection = 'Faction';");

        $result = $data->fetchArray(SQLITE3_ASSOC);

        if(empty($result) === false){

        	if(self::getStrength($result["factionName"]) < 1){

        		$data->finalize();

        		return false;

        	}else{

        		$data->finalize();

            	return true;

            }

        }else{

        	$data->finalize();

            return false;

        }

        $data->finalize();

        return false;

    }

    /**

     * @param Vector3 $position

     * @return String|null

     */

    public static function getRegionName(Vector3 $position) : ?String {

        $x = $position->getFloorX();

        $z = $position->getFloorZ();

        $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM zoneclaims WHERE $x >= x1 AND $x <= x2 AND $z >= z1 AND $z <= z2;");

        $result = $data->fetchArray(SQLITE3_ASSOC);

        if(empty($result)){

			return null;

		}

        if($position->getLevel()->getName() === $result["level"]){

        	return $result["factionName"];

        }else{

        	$data->finalize();

        	return null;

        }

    }

    

    /**

     * @param String $factionName

     * @return bool

     */

    public static function isRegionExists(String $factionName) : bool {

    	$data = Loader::getProvider()->getDataBase()->query("SELECT * FROM zoneclaims WHERE factionName = '$factionName';");

		$result = $data->fetchArray(SQLITE3_ASSOC);

		if(!empty($result)){

			$data->finalize();

			return true;

		}else{

			$data->finalize();

			return false;

		}

		$data->finalize();

		return false;

    }

    /**

     * @param String $factionName

     * @return void

     */

    public static function deleteRegion(String $factionName) : void {

        Loader::getProvider()->getDataBase()->query("DELETE FROM zoneclaims WHERE factionName = '$factionName';");

    }

    

    /**

	 * @param Player $player

	 * @param Block $block

	 */

	public static function observeMap(Player $player, Block $block, String $factionType = null, bool $opClaim = false){

        if($opClaim){

            $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM zoneclaims WHERE factionName = '$factionType';");

            $result = $data->fetchArray(SQLITE3_ASSOC);

            $position1 = new Vector3($result["x1"], $player->getFloorY(), $result["z1"]);

            $position2 = new Vector3($result["x2"], $player->getFloorY(), $result["z2"]);

            $position3 = new Vector3($result["x1"], $player->getFloorY(), $result["z2"]);

            $position4 = new Vector3($result["x2"], $player->getFloorY(), $result["z1"]);

            for($i = $player->getFloorY(); $i < $player->getFloorY() + 40; $i++){

                $pk = new UpdateBlockPacket();

                $pk->x = $position1->getFloorX();

                $pk->y = $i;

                $pk->z = $position1->getFloorZ();

                $pk->flags = UpdateBlockPacket::FLAG_ALL;

                $pk->blockRuntimeId = $block->getRuntimeId();

                $player->dataPacket($pk);

            }

            for($i = $player->getFloorY(); $i < $player->getFloorY() + 40; $i++){

                $pk = new UpdateBlockPacket();

                $pk->x = $position2->getFloorX();

                $pk->y = $i;

                $pk->z = $position2->getFloorZ();

                $pk->flags = UpdateBlockPacket::FLAG_ALL;

                $pk->blockRuntimeId = $block->getRuntimeId();

                $player->dataPacket($pk);

            }

            for($i = $player->getFloorY(); $i < $player->getFloorY() + 40; $i++){

                $pk = new UpdateBlockPacket();

                $pk->x = $position3->getFloorX();

                $pk->y = $i;

                $pk->z = $position3->getFloorZ();

                $pk->flags = UpdateBlockPacket::FLAG_ALL;

                $pk->blockRuntimeId = $block->getRuntimeId();

                $player->dataPacket($pk);

            }

            for($i = $player->getFloorY(); $i < $player->getFloorY() + 40; $i++){

                $pk = new UpdateBlockPacket();

                $pk->x = $position4->getFloorX();

                $pk->y = $i;

                $pk->z = $position4->getFloorZ();

                $pk->flags = UpdateBlockPacket::FLAG_ALL;

                $pk->blockRuntimeId = $block->getRuntimeId();

                $player->dataPacket($pk);

            }

        }else{

            $factionName = self::getFaction($player->getName());

            $data = Loader::getProvider()->getDataBase()->query("SELECT * FROM zoneclaims WHERE factionName = '$factionName';");

            $result = $data->fetchArray(SQLITE3_ASSOC);

            $position1 = new Vector3($result["x1"], $player->getFloorY(), $result["z1"]);

            $position2 = new Vector3($result["x2"], $player->getFloorY(), $result["z2"]);

            $position3 = new Vector3($result["x1"], $player->getFloorY(), $result["z2"]);

            $position4 = new Vector3($result["x2"], $player->getFloorY(), $result["z1"]);

            for($i = $player->getFloorY(); $i < $player->getFloorY() + 40; $i++){

                $pk = new UpdateBlockPacket();

                $pk->x = $position1->getFloorX();

                $pk->y = $i;

                $pk->z = $position1->getFloorZ();

                $pk->flags = UpdateBlockPacket::FLAG_ALL;

                $pk->blockRuntimeId = $block->getRuntimeId();

                $player->dataPacket($pk);

            }

            for($i = $player->getFloorY(); $i < $player->getFloorY() + 40; $i++){

                $pk = new UpdateBlockPacket();

                $pk->x = $position2->getFloorX();

                $pk->y = $i;

                $pk->z = $position2->getFloorZ();

                $pk->flags = UpdateBlockPacket::FLAG_ALL;

                $pk->blockRuntimeId = $block->getRuntimeId();

                $player->dataPacket($pk);

            }

            for($i = $player->getFloorY(); $i < $player->getFloorY() + 40; $i++){

                $pk = new UpdateBlockPacket();

                $pk->x = $position3->getFloorX();

                $pk->y = $i;

                $pk->z = $position3->getFloorZ();

                $pk->flags = UpdateBlockPacket::FLAG_ALL;

                $pk->blockRuntimeId = $block->getRuntimeId();

                $player->dataPacket($pk);

            }

            for($i = $player->getFloorY(); $i < $player->getFloorY() + 40; $i++){

                $pk = new UpdateBlockPacket();

                $pk->x = $position4->getFloorX();

                $pk->y = $i;

                $pk->z = $position4->getFloorZ();

                $pk->flags = UpdateBlockPacket::FLAG_ALL;

                $pk->blockRuntimeId = $block->getRuntimeId();

                $player->dataPacket($pk);

            }

            $data->finalize();

        }

	}

}

?>
