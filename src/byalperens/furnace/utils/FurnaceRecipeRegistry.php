<?php

namespace byalperens\furnace\utils;

use byalperens\furnace\BetterFurnace;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\FurnaceRecipe;
use pocketmine\crafting\FurnaceType;
use pocketmine\item\StringToItemParser;
use pocketmine\Server;

class FurnaceRecipeRegistry{

    public static array $registered = [];

    public static function init(): void{
        $config = BetterFurnace::getInstance()->getConfig();
        $recipes = [];

        if (!empty($config->get("heatable-blocks"))){
            foreach ($config->get("heatable-blocks") as $key => $value){
                $input = StringToItemParser::getInstance()->parse($key);
                $output = StringToItemParser::getInstance()->parse($value);

                if (Server::getInstance()->getCraftingManager()->getFurnaceRecipeManager(FurnaceType::FURNACE())->match($input) != null){
                    continue;
                }
                $recipes[] = new FurnaceRecipe($output, new ExactRecipeIngredient($input));
                self::$registered[] = $input->getStateId();
            }
        }
        if (!empty($recipes)){
            foreach ($recipes as $recipe){
                Server::getInstance()->getCraftingManager()->getFurnaceRecipeManager(FurnaceType::FURNACE())->register($recipe);
            }
        }
    }
}
