<?php

/**            __   _____
 *  _ __ ___ / _| |_   _|__  __ _ _ __ ___
 * | '__/ _ \ |_    | |/ _ \/ _` | '_ ` _ \
 * | | |  __/  _|   | |  __/ (_| | | | | | |
 * |_|  \___|_|     |_|\___|\__,_|_| |_| |_|
 *
 * @author  ref-team
 * @link    https://github.com/ref-plugin
 *
 *  &   ／l、
 *    （ﾟ､ ｡ ７
 *   　\、ﾞ ~ヽ   *
 *   　じしf_, )ノ
 *
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace ref\api\addonsmanager;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackType;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePack;

use function ceil;
use function count;
use function strpos;
use function substr;

final class Main extends PluginBase implements Listener{
    private const PACK_CHUNK_SIZE = 128 * 1024; //128KB
    private const OVERRIDDEN_EXPERIMENTS = [
        "scripting" => true, // Additional Modding Capabilities
        "upcoming_creator_features" => true, // Upcoming Creator Features
        "gametest" => true, // Enable GameTest Framework
        "data_driven_items" => true, // Holiday Creator Features
        "experimental_molang_features" => true, // Experimental Molang Features
    ];

    private AddonsManager $addonsManager;

    protected function onEnable() : void{
        $this->addonsManager = AddonsManager::getInstance();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /** @priority LOWEST */
    public function onDataPacketSendEvent(DataPacketSendEvent $event) : void{
        foreach($event->getPackets() as $packet){
            if($packet instanceof ResourcePackStackPacket){
                $packet->behaviorPackStack = [
                    ...$packet->behaviorPackStack,
                    ...$this->addonsManager->getBehaviorPackStackEntries()
                ];
            }elseif($packet instanceof ResourcePacksInfoPacket){
                $packet->behaviorPackEntries = [
                    ...$packet->behaviorPackEntries,
                    ...$this->addonsManager->getBehaviorPackInfoEntries()
                ];
            }elseif($packet instanceof StartGamePacket){
                $experiments = $packet->levelSettings->experiments;
                /**
                 * @noinspection PhpExpressionResultUnusedInspection
                 * HACK : Modifying properties using public constructors
                 */
                $experiments->__construct(
                    array_merge($experiments->getExperiments(), self::OVERRIDDEN_EXPERIMENTS),
                    $experiments->hasPreviouslyUsedExperiments()
                );
            }
        }
    }

    /** @priority LOWEST */
    public function onDataPacketReceiveEvent(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        if(
            $packet instanceof ResourcePackClientResponsePacket &&
            $packet->status === ResourcePackClientResponsePacket::STATUS_SEND_PACKS
        ){
            $session = $event->getOrigin();
            /** @var string[] */
            $remained = [];
            foreach($packet->packIds as $key => $uuid){
                $splitPos = strpos($uuid, "_");
                if($splitPos !== false){
                    $uuid = substr($uuid, 0, $splitPos);
                }
                $pack = $this->addonsManager->getBehaviorPack($uuid);
                if($pack instanceof ResourcePack){
                    $pk = ResourcePackDataInfoPacket::create(
                        $pack->getPackId(),
                        self::PACK_CHUNK_SIZE,
                        (int) ceil($pack->getPackSize() / self::PACK_CHUNK_SIZE),
                        $pack->getPackSize(),
                        $pack->getSha256(),
                        false,
                        ResourcePackType::RESOURCES
                    );
                    $pk->packType = ResourcePackType::BEHAVIORS;
                    $session->sendDataPacket($pk);
                }else{
                    $remained[$key] = $uuid;
                }
            }

            $session->getLogger()->debug("Player requested download of " . (count($packet->packIds) - count($remained)) . " behavior packs");
            $packet->packIds = $remained;
        }elseif(
            $packet instanceof ResourcePackChunkRequestPacket &&
            ($pack = $this->addonsManager->getBehaviorPack($packet->packId)) instanceof ResourcePack
        ){
            $event->getOrigin()->sendDataPacket(ResourcePackChunkDataPacket::create(
                $pack->getPackId(),
                $packet->chunkIndex,
                (self::PACK_CHUNK_SIZE * $packet->chunkIndex),
                $pack->getPackChunk(self::PACK_CHUNK_SIZE * $packet->chunkIndex, self::PACK_CHUNK_SIZE)
            ));
            $event->cancel();
        }
    }
}