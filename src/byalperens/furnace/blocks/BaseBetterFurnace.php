<?php

namespace byalperens\furnace\blocks;

use byalperens\furnace\items\BaseToken;
use byalperens\furnace\tile\BetterFurnaceTile;
use customiesdevs\customies\block\CustomiesBlockFactory;
use customiesdevs\customies\block\permutations\Permutable;
use customiesdevs\customies\block\permutations\RotatableTrait;
use pocketmine\block\Air;
use pocketmine\block\inventory\FurnaceInventory;
use pocketmine\block\Opaque;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;

abstract class BaseBetterFurnace extends Opaque implements Permutable{
    use RotatableTrait;

    /**
     * @return BetterFurnace
     */
    public static function BETTER_FURNACE(): BetterFurnace{
        /** @var BetterFurnace $furnace */
        $furnace = CustomiesBlockFactory::getInstance()->get("custom:better_furnace");
        return $furnace;
    }

    /**
     * @return BlastBetterFurnace
     */
    public static function BLAST_BETTER_FURNACE(): BlastBetterFurnace{
        /** @var BlastBetterFurnace $furnace */
        $furnace = CustomiesBlockFactory::getInstance()->get("custom:blast_better_furnace");
        return $furnace;
    }

    /**
     * @param Item $item
     * @param int $face
     * @param Vector3 $clickVector
     * @param Player|null $player
     * @param array $returnedItems
     * @return bool
     */
    public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []): bool{
        if ($player instanceof Player){
            $furnace = $this->position->getWorld()->getTile($this->position);

            if ($furnace instanceof BetterFurnaceTile && $furnace->canOpenWith($item->getCustomName())){
                if ($player->isSneaking() && $furnace->getToken() != null){
                    $token = $furnace->getToken();
                    $furnace->setToken(null);
                    $player->getWorld()->dropItem($furnace->getPosition(), $token);
                    $player->sendMessage(C::GREEN . "You successfully get the token out of the furnace!");
                    $packet = SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $furnace->getPosition()->add(0, 1, 0), "minecraft:campfire_tall_smoke_particle", null);
                    $player->getNetworkSession()->sendDataPacket($packet);
                    $player->getWorld()->addSound($player->getPosition(), new NoteSound(NoteInstrument::PLING(), $player->getLocation()->getPitch()));
                }elseif ($item instanceof BaseToken){
                    $player->getInventory()->setItemInHand(VanillaItems::AIR());

                    if (($last = $furnace->getToken()) != null){
                        $furnace->getPosition()->getWorld()->dropItem($furnace->getPosition(), $last);
                    }
                    $furnace->setToken($item);
                    $player->sendMessage(C::GREEN . "Successfully put the token in the furnace!");
                    $packet = SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $furnace->getPosition()->add(0, 1, 0), "minecraft:campfire_tall_smoke_particle", null);
                    $player->getNetworkSession()->sendDataPacket($packet);
                    $player->getWorld()->addSound($player->getPosition(), new NoteSound(NoteInstrument::PLING(), $player->getLocation()->getPitch()));
                }else{
                    $block = $this->position->getWorld()->getBlock($this->position->subtract(0, 1, 0));
                    $furnace->lastBlock = $block instanceof Air ? null : $block;
                    $packet = new UpdateBlockPacket();
                    $packet->blockPosition = BlockPosition::fromVector3($this->position->subtract(0, 1, 0));
                    $packet->blockRuntimeId = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(VanillaBlocks::FURNACE()->getStateId());
                    $player->getNetworkSession()->sendDataPacket($packet);
                    $player->setCurrentWindow($furnace->getInventory());
                }
            }
        }
        return true;
    }

    public function onScheduledUpdate(): void{
        $world = $this->position->getWorld();
        $furnace = $world->getTile($this->position);

        if ($furnace instanceof BetterFurnaceTile && $furnace->onUpdate()){
            if (mt_rand(1, 60) === 1){
                $world->addSound($this->position, $furnace->getFurnaceType()->getCookSound());
            }
            $world->scheduleDelayedBlockUpdate($this->position, 1);
        }
    }

    /**
     * @param Item $item
     * @return array|Item[]
     */
    public function getDrops(Item $item): array{
        $items = [self::BETTER_FURNACE()->asItem()];
        $tile = $this->position->getWorld()->getTile($this->position);

        if ($tile instanceof BetterFurnaceTile){
            if ($tile->getInventory() instanceof FurnaceInventory){
                if (!$tile->getInventory()->getResult()->equals(VanillaItems::AIR())){
                    $items[] = $tile->getInventory()->getResult();
                }
                if (!$tile->getInventory()->getFuel()->equals(VanillaItems::AIR())){
                    $items[] = $tile->getInventory()->getFuel();
                }
                if (!$tile->getInventory()->getSmelting()->equals(VanillaItems::AIR())){
                    $items[] = $tile->getInventory()->getSmelting();
                }
            }
            if ($tile->getToken() instanceof BaseToken){
                $items[] = $tile->getToken();
            }
        }
        return $items;
    }
}
