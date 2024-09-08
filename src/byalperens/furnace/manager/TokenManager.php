<?php

namespace byalperens\furnace\manager;

use byalperens\furnace\items\BaseToken;
use byalperens\furnace\items\FastCookToken;
use byalperens\furnace\items\HeatToken;
use byalperens\furnace\items\LowFuelToken;
use byalperens\furnace\items\MoreProductToken;
use customiesdevs\customies\item\CustomiesItemFactory;
use pocketmine\utils\SingletonTrait;

class TokenManager{
    use SingletonTrait;

    /** @var string[] */
    public const TOKEN_TYPES = [
        "fast cook" => FastCookToken::class,
        "more product" => MoreProductToken::class,
        "low fuel" => LowFuelToken::class,
        "heat" => HeatToken::class,
    ];

    /** @var string */
    public const NONE = "none";
    public const FAST_COOK = "fast cook";
    public const LOW_FUEL = "low fuel";
    public const MORE_PRODUCT = "more product";
    public const HEAT = "heat";

    /**
     * @param string $token
     * @param int $count
     * @param int $minute
     * @return BaseToken
     */
    public function getToken(string $token, int $count, int $minute): BaseToken{
        /** @var BaseToken $token */
        $token = CustomiesItemFactory::getInstance()->get($this->convertTokenIdentifier($token));
        $token->setCount($count);
        $token->setDamage($token->getMaxDurability() - ($minute * 60));
        return $token;
    }

    /**
     * @param string $tokenType
     * @return string
     */
    public function convertTokenIdentifier(string $tokenType): string{
        return "custom:" . str_replace(" ", "_", $tokenType) . "_token";
    }
}
