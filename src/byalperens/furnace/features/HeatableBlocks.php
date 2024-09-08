<?php

namespace byalperens\furnace\features;

use byalperens\furnace\BetterFurnace;
use pocketmine\item\StringToItemParser;

class HeatableBlocks{

    /**
     * @return array
     */
    public static function getAll(): array{
        $lists = BetterFurnace::getInstance()->getConfig()->get("heatable-blocks");
        $blocks = [];

        foreach ($lists as $key => $value){
            $blocks[StringToItemParser::getInstance()->parse($key)->getName()] = StringToItemParser::getInstance()->parse($value);
        }
        return $blocks;
    }
}
