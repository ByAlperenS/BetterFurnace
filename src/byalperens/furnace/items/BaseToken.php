<?php

namespace byalperens\furnace\items;

use byalperens\furnace\BetterFurnace;
use pocketmine\item\Durable;

abstract class BaseToken extends Durable{

    /**
     * @return int
     */
    public function getMaxDurability(): int{
        return (int) BetterFurnace::getInstance()->getConfig()->get("token-max-durability");
    }

    /**
     * @return int
     */
    public function getMaxStackSize(): int{
        return 1;
    }

    abstract public function getTokenType(): string;
}
