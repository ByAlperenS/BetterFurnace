<?php

namespace byalperens\furnace\items;

use customiesdevs\customies\item\component\MaxStackSizeComponent;
use customiesdevs\customies\item\ItemComponents;
use customiesdevs\customies\item\ItemComponentsTrait;
use pocketmine\item\ItemIdentifier;

class HeatToken extends BaseToken implements ItemComponents{
    use ItemComponentsTrait;

    /**
     * @param ItemIdentifier $identifier
     * @param string $name
     * @param array $enchantmentTags
     */
    public function __construct(ItemIdentifier $identifier, string $name = "Unknown", array $enchantmentTags = []){
        parent::__construct($identifier, $name, $enchantmentTags);
        $this->initComponent("heat_token");
        $this->addComponent(new MaxStackSizeComponent(1));
    }

    /**
     * @return string
     */
    public function getTokenType(): string{
        return "heat";
    }
}
