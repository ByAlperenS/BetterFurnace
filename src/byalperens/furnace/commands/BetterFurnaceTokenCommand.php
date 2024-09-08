<?php

namespace byalperens\furnace\commands;

use byalperens\furnace\BetterFurnace;
use byalperens\furnace\manager\TokenManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

class BetterFurnaceTokenCommand extends Command{

    public function __construct(){
        parent::__construct("bftoken", "Better Furnace Token Command", C::DARK_GREEN . "Usage:" . C::GREEN . "/bftoken [player] [token] [count] [minute]");
        $this->setPermission("better.furnace.token");
        $this->setPermissionMessage(C::RED . "You don't have permission for this command!");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if (isset($args[0], $args[1], $args[2], $args[3])){
            $player = Server::getInstance()->getPlayerByPrefix($args[0]);

            if (!($player instanceof Player)){
                $sender->sendMessage(C::RED . "Player not found!");
                return;
            }
            if (!in_array($args[1], array_keys(TokenManager::TOKEN_TYPES))){
                $sender->sendMessage(C::RED . "Specified token not found!");
                return;
            }
            if (!is_numeric($args[2]) || str_contains($args[2], "+") || str_contains($args[2], "-") || str_contains($args[2], ",") || str_contains($args[2], ".") || $args[2] == 0){
                $sender->sendMessage(C::RED . "Please enter a valid value!");
                return;
            }
            if (!is_numeric($args[3]) || str_contains($args[3], "+") || str_contains($args[3], "-") || str_contains($args[3], ",") || str_contains($args[3], ".") || $args[3] == 0){
                $sender->sendMessage(C::RED . "Please enter a valid value!");
                return;
            }
            if ((int) $args[3] > (int) BetterFurnace::getInstance()->getConfig()->get("token-max-durability")){
                $sender->sendMessage(C::RED . "You're over the minute limit!");
                return;
            }
            $token = TokenManager::getInstance()->getToken($args[1], (int) $args[2], (int) $args[3]);

            if (!$player->getInventory()->canAddItem($token)){
                $player->getWorld()->dropItem($player->getPosition(), $token);
                $player->sendMessage(C::DARK_RED . "Your inventory was full and the token dropped!");
            }else{
                $player->getInventory()->addItem($token);
            }
            $sender->sendMessage(C::GREEN . "The token was successfully given!");
        }else{
            $sender->sendMessage($this->getUsage());
        }
    }
}
