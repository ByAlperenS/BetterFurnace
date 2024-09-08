<?php

namespace byalperens\furnace;

use byalperens\furnace\blocks\BlastBetterFurnace;
use byalperens\furnace\commands\BetterFurnaceTokenCommand;
use byalperens\furnace\items\FastCookToken;
use byalperens\furnace\items\HeatToken;
use byalperens\furnace\items\LowFuelToken;
use byalperens\furnace\items\MoreProductToken;
use byalperens\furnace\tile\BetterFurnaceTile;
use byalperens\furnace\utils\FurnaceRecipeRegistry;
use customiesdevs\customies\block\CustomiesBlockFactory;
use customiesdevs\customies\block\Material;
use customiesdevs\customies\block\Model;
use customiesdevs\customies\item\CustomiesItemFactory;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\tile\TileFactory;
use pocketmine\item\ToolTier;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as C;
use byalperens\furnace\blocks\BetterFurnace as BetterFurnaceBlock;

class BetterFurnace extends PluginBase{

    private static self $instance;

    public function onLoad(): void{
        self::$instance = $this;
    }

    public function onEnable(): void{
        $this->getLogger()->info(C::GREEN . "Plugin Enabled!");
        $this->getServer()->getCommandMap()->register("bftoken", new BetterFurnaceTokenCommand());
        FurnaceRecipeRegistry::init();
        TileFactory::getInstance()->register(BetterFurnaceTile::class, ["custom:better_furnace"]);
        $this->reloadConfig();
        $this->registerCustomBlocks();
        $this->registerCustomItems();
    }

    private function registerCustomBlocks(): void{
        CustomiesBlockFactory::getInstance()->registerBlock(
            static fn () => new BetterFurnaceBlock(new BlockIdentifier(BlockTypeIds::newId(), BetterFurnaceTile::class), "Better Furnace", new BlockTypeInfo(BlockBreakInfo::pickaxe(3.5, ToolTier::WOOD()))),
            "custom:better_furnace",
            new Model([new Material(Material::TARGET_ALL, "better_furnace", Material::RENDER_METHOD_OPAQUE)], "geometry.better_furnace", new Vector3(-8, 0, -8), new Vector3(16, 16, 16))
        );

        CustomiesBlockFactory::getInstance()->registerBlock(
            static fn () => new BlastBetterFurnace(new BlockIdentifier(BlockTypeIds::newId(), BetterFurnaceTile::class), "Blast Better Furnace", new BlockTypeInfo(BlockBreakInfo::pickaxe(3.5, ToolTier::WOOD()))),
            "custom:blast_better_furnace",
            new Model([new Material(Material::TARGET_ALL, "blast_better_furnace", Material::RENDER_METHOD_OPAQUE)], "geometry.better_furnace", new Vector3(-8, 0, -8), new Vector3(16, 16, 16))
        );
    }

    private function registerCustomItems(): void{
        CustomiesItemFactory::getInstance()->registerItem(LowFuelToken::class, "custom:low_fuel_token", "Low Fuel Token");
        CustomiesItemFactory::getInstance()->registerItem(HeatToken::class, "custom:heat_token", "Heat Token");
        CustomiesItemFactory::getInstance()->registerItem(FastCookToken::class, "custom:fast_cook_token", "Fast Cook Token");
        CustomiesItemFactory::getInstance()->registerItem(MoreProductToken::class, "custom:more_product_token", "More Product Token");
    }

    /**
     * @return self
     */
    public static function getInstance(): self{
        return self::$instance;
    }
}
