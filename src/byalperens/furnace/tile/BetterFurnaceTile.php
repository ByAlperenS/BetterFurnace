<?php

namespace byalperens\furnace\tile;

use byalperens\furnace\blocks\BaseBetterFurnace;
use byalperens\furnace\blocks\BetterFurnace;
use byalperens\furnace\blocks\BlastBetterFurnace;
use byalperens\furnace\features\HeatableBlocks;
use byalperens\furnace\items\BaseToken;
use byalperens\furnace\items\FastCookToken;
use byalperens\furnace\items\HeatToken;
use byalperens\furnace\items\LowFuelToken;
use byalperens\furnace\items\MoreProductToken;
use byalperens\furnace\utils\FurnaceRecipeRegistry;
use byalperens\furnace\utils\ItemSerializer;
use pocketmine\block\Block;
use pocketmine\block\inventory\FurnaceInventory;
use pocketmine\block\tile\Container;
use pocketmine\block\tile\ContainerTrait;
use pocketmine\block\tile\Nameable;
use pocketmine\block\tile\NameableTrait;
use pocketmine\block\tile\Spawnable;
use pocketmine\crafting\FurnaceRecipe;
use pocketmine\crafting\FurnaceType;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\player\Player;
use pocketmine\world\particle\AngryVillagerParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;
use pocketmine\world\World;
use byalperens\furnace\BetterFurnace as BetterFurnaceMain;

class BetterFurnaceTile extends Spawnable implements Container, Nameable{
    use NameableTrait;
    use ContainerTrait;

    /** @var string */
    public const TAG_BURN_TIME = "BurnTime";
    public const TAG_COOK_TIME = "CookTime";
    public const TAG_MAX_TIME = "MaxTime";

    protected FurnaceInventory $inventory;

    private int $remainingFuelTime = 0;
    private int $cookTime = 0;
    private int $maxFuelTime = 0;

    public ?Block $lastBlock = null;

    /** @var string */
    private const TAG_TOKEN = "Token";

    private ?BaseToken $token = null;

    /** @var int */
    private const PER_TICK_MINUTE = 1020;
    private const PER_TICK_SECOND = 17;

    private int $tick = self::PER_TICK_SECOND;

    /**
     * @param World $world
     * @param Vector3 $pos
     */
    public function __construct(World $world, Vector3 $pos){
        parent::__construct($world, $pos);
        $this->inventory = new FurnaceInventory(Position::fromObject($this->position->subtract(0, 1, 0), $this->position->getWorld()), $this->getFurnaceType());
        $this->inventory->getListeners()->add(CallbackInventoryListener::onAnyChange(
            static function (Inventory $unused) use ($world, $pos): void{
                $world->scheduleDelayedBlockUpdate($pos, 1);
            })
        );
    }

    /**
     * @param CompoundTag $nbt
     * @return void
     */
    public function readSaveData(CompoundTag $nbt): void{
        $this->token = ItemSerializer::jsonDeserializeItem($nbt->getString(self::TAG_TOKEN, ItemSerializer::jsonSerializeItem($this->token)));

        $this->remainingFuelTime = max(0, $nbt->getShort(self::TAG_BURN_TIME, $this->remainingFuelTime));
        $this->cookTime = $nbt->getShort(self::TAG_COOK_TIME, $this->cookTime);

        if ($this->remainingFuelTime === 0){
            $this->cookTime = 0;
        }
        $this->maxFuelTime = $nbt->getShort(self::TAG_MAX_TIME, $this->maxFuelTime);

        if ($this->maxFuelTime === 0){
            $this->maxFuelTime = $this->remainingFuelTime;
        }
        $this->loadName($nbt);
        $this->loadItems($nbt);

        if ($this->remainingFuelTime > 0){
            $this->position->getWorld()->scheduleDelayedBlockUpdate($this->position, 1);
        }
    }

    /**
     * @param CompoundTag $nbt
     * @return void
     */
    protected function writeSaveData(CompoundTag $nbt): void{
        $nbt->setString(self::TAG_TOKEN, ItemSerializer::jsonSerializeItem($this->token));

        $nbt->setShort(self::TAG_BURN_TIME, $this->remainingFuelTime);
        $nbt->setShort(self::TAG_COOK_TIME, $this->cookTime);
        $nbt->setShort(self::TAG_MAX_TIME, $this->maxFuelTime);
        $this->saveName($nbt);
        $this->saveItems($nbt);
    }

    /**
     * @return string
     */
    public function getDefaultName(): string{
        return "Better Furnace";
    }

    public function close(): void{
        if (!$this->closed){
            $this->inventory->removeAllViewers();
            parent::close();
        }
    }

    /**
     * @return FurnaceInventory
     */
    public function getInventory(): FurnaceInventory{
        return $this->inventory;
    }

    /**
     * @return FurnaceInventory
     */
    public function getRealInventory(): FurnaceInventory{
        return $this->getInventory();
    }

    /**
     * @param Item $fuel
     * @return void
     */
    protected function checkFuel(Item $fuel): void{
        $this->maxFuelTime = $this->remainingFuelTime = $this->token instanceof LowFuelToken ? (int) ($fuel->getFuelTime() * (100 + (int) BetterFurnaceMain::getInstance()->getConfig()->get("low-fuel-token-percentage")) / 100) : $fuel->getFuelTime();
        $this->onStartSmelting();

        if ($this->remainingFuelTime > 0){
            $this->inventory->setFuel($fuel->getFuelResidue());
        }
    }

    protected function onStartSmelting(): void{
        $block = $this->getBlock();

        if ($block instanceof BetterFurnace){
            $block->setLit(true);
            $this->position->getWorld()->setBlock($block->getPosition(), BaseBetterFurnace::BLAST_BETTER_FURNACE()->setFacing($block->getFacing()));
        }
    }

    protected function onStopSmelting(): void{
        $block = $this->getBlock();

        if ($block instanceof BlastBetterFurnace){
            $this->position->getWorld()->setBlock($block->getPosition(), BaseBetterFurnace::BETTER_FURNACE());
        }
    }

    /**
     * @return FurnaceType
     */
    public function getFurnaceType(): FurnaceType{
        return FurnaceType::FURNACE();
    }

    /**
     * @return bool
     */
    public function onUpdate(): bool{
        if ($this->closed){
            return false;
        }
        if ($this->token instanceof BaseToken){
            if ($this->tick > 0){
                $this->tick--;
            }elseif ($this->tick == 0){
                $this->token->applyDamage(1);
                $this->tick = self::PER_TICK_SECOND;
            }
            if ($this->token->isBroken()){
                if (!empty($this->getPosition()->getWorld()->getPlayers())){
                    foreach ($this->getPosition()->getWorld()->getPlayers() as $player){
                        if ($player === null){
                            continue;
                        }
                        $player->getWorld()->addParticle($this->getPosition(), new AngryVillagerParticle());
                        $player->getWorld()->addSound($this->getPosition(), new NoteSound(NoteInstrument::COW_BELL(), $player->getLocation()->getPitch()));
                    }
                }
                $this->setToken(null);
            }
        }
        $this->timings->startTiming();

        $prevCookTime = $this->cookTime;
        $prevRemainingFuelTime = $this->remainingFuelTime;
        $prevMaxFuelTime = $this->maxFuelTime;

        $ret = false;

        $fuel = $this->inventory->getFuel();
        $raw = $this->inventory->getSmelting();
        $product = $this->inventory->getResult();

        if (in_array($raw->getStateId(), FurnaceRecipeRegistry::$registered)){
            if (!($this->token instanceof HeatToken)){
                return false;
            }
        }
        $furnaceType = $this->getFurnaceType();
        $smelt = $this->position->getWorld()->getServer()->getCraftingManager()->getFurnaceRecipeManager($furnaceType)->match($raw);
        $canSmelt = ($smelt instanceof FurnaceRecipe && $raw->getCount() > 0 && (($smelt->getResult()->canStackWith($product) && $product->getCount() < $product->getMaxStackSize()) || $product->isNull()));

        if ($this->remainingFuelTime <= 0 && $canSmelt && $fuel->getFuelTime() > 0 && $fuel->getCount() > 0){
            $this->checkFuel($fuel);
        }
        if ($this->remainingFuelTime > 0){
            --$this->remainingFuelTime;

            if ($smelt instanceof FurnaceRecipe && $canSmelt){
                ++$this->cookTime;
                $calculateCookDurationTicks = $this->token instanceof FastCookToken ? (int) ($furnaceType->getCookDurationTicks() * (100 - (int) BetterFurnaceMain::getInstance()->getConfig()->get("fast-cook-token-percentage")) / 100) : $furnaceType->getCookDurationTicks();

                if ($this->cookTime >= $calculateCookDurationTicks){
                    if ($this->token instanceof HeatToken){
                        if (isset(HeatableBlocks::getAll()[$smelt->getResult()->getName()])){
                            $product = HeatableBlocks::getAll()[$smelt->getResult()->getName()]->setCount($product->getCount() + 1);
                        }else{
                            $product = $smelt->getResult()->setCount($product->getCount() + 1);
                        }
                    }elseif ($this->token instanceof MoreProductToken){
                        $product = $smelt->getResult()->setCount(($product->getCount() + 1) * (int) BetterFurnaceMain::getInstance()->getConfig()->get("more-product-token-x"));
                    }else{
                        $product = $smelt->getResult()->setCount($product->getCount() + 1);
                    }
                    $this->inventory->setResult($product);
                    $raw->pop();
                    $this->inventory->setSmelting($raw);
                    $this->cookTime -= $calculateCookDurationTicks;
                }
            }elseif ($this->remainingFuelTime <= 0){
                $this->remainingFuelTime = $this->cookTime = $this->maxFuelTime = 0;
            }else{
                $this->cookTime = 0;
            }
            $ret = true;
        }else{
            $this->onStopSmelting();
            $this->remainingFuelTime = $this->cookTime = $this->maxFuelTime = 0;
        }
        $viewers = array_map(fn(Player $p) => $p->getNetworkSession()->getInvManager(), $this->inventory->getViewers());

        foreach ($viewers as $v){
            if ($v === null){
                continue;
            }
            if ($prevCookTime !== $this->cookTime){
                $v->syncData($this->inventory, ContainerSetDataPacket::PROPERTY_FURNACE_SMELT_PROGRESS, $this->cookTime);
            }
            if ($prevRemainingFuelTime !== $this->remainingFuelTime){
                $v->syncData($this->inventory, ContainerSetDataPacket::PROPERTY_FURNACE_REMAINING_FUEL_TIME, $this->remainingFuelTime);
            }
            if ($prevMaxFuelTime !== $this->maxFuelTime){
                $v->syncData($this->inventory, ContainerSetDataPacket::PROPERTY_FURNACE_MAX_FUEL_TIME, $this->maxFuelTime);
            }
        }
        $this->timings->stopTiming();
        return $ret;
    }

    /**
     * @return BaseToken|null
     */
    public function getToken(): ?BaseToken{
        return $this->token;
    }

    /**
     * @param BaseToken|null $token
     * @return void
     */
    public function setToken(?BaseToken $token): void{
        $this->token = $token;
    }
}
