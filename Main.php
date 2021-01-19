<?php

namespace Fly;

use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\event\player\PlayerMoveEvent;

class Main extends PluginBase implements Listener {

    public $players = array();

     public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info(TextFormat::GREEN . "Plugin Fly Enable");
     }

     public function onDisable() {
        $this->getLogger()->info(TextFormat::RED . "Plugin Fly Disable");
     }
   
    public function onCommand(CommandSender $sender, Command $cmd, $label,array $args) : bool {
        if(strtolower($cmd->getName()) == "fly") {
            if($sender instanceof Player) {
                if($this->isPlayer($sender)) {
                    $this->removePlayer($sender);
                    $sender->setAllowFlight(false);
                    $sender->sendMessage(TextFormat::YELLOW . "§l§0(§c!§0)§r §7Fly Mode Has Been Disable");
				$sender->addTitle("§l§7»§aFly§6mode§7«§r\n§7•§l§cDisable§7•");
                    return true;
                }
                else{
                    $this->addPlayer($sender);
                    $sender->setAllowFlight(true);
                    $sender->sendMessage(TextFormat::YELLOW . "§l§0(§a!§0)§r §7Fly Mode Has Been Enable");
				$sender->addTitle("§l§7»§aFly§6mode§7«§r\n§7•§l§bEnable§7•");
                    return true;
                }
            }
            else{
                $sender->sendMessage(TextFormat::RED . "§l§0(§c!§0)§r §7Fly Mode Has Been Disable");
				$sender->addTitle("§l§7»§aFly§6mode§7«§r\n§7•§l§cDisable§7•");
                return true;
            }
        }
    }
    public function addPlayer(Player $player) {
        $this->players[$player->getName()] = $player->getName();
    }
    public function isPlayer(Player $player) {
        return in_array($player->getName(), $this->players);
    }
    public function removePlayer(Player $player) {
        unset($this->players[$player->getName()]);
    }
}
 
