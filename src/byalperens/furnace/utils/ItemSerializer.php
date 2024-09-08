<?php

namespace byalperens\furnace\utils;

use byalperens\furnace\items\BaseToken;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;

class ItemSerializer{

    /**
     * @param BaseToken|null $item
     * @return string
     */
    public static function jsonSerializeItem(?BaseToken $item): string{
        if ($item == null){
            return "~";
        }
        $serializer = new LittleEndianNbtSerializer();
        return base64_encode($serializer->write(new TreeRoot($item->nbtSerialize())));
    }

    /**
     * @param string $data
     * @return BaseToken|null
     */
    public static function jsonDeserializeItem(string $data): ?BaseToken{
        if ($data == "~"){
            return null;
        }
        $serializer = new LittleEndianNbtSerializer();
        /** @var BaseToken $item */
        $item = Item::nbtDeserialize($serializer->read(base64_decode($data))->mustGetCompoundTag());
        return $item;
    }
}
