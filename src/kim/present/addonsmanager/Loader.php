<?php

/**
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection PhpDocSignatureInspection
 * @noinspection SpellCheckingInspection
 */

declare(strict_types=1);

namespace kim\present\addonsmanager;

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
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackType;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePack;

use function array_merge;
use function ceil;
use function count;
use function rtrim;
use function str_replace;
use function strpos;
use function substr;

final class Loader extends PluginBase implements Listener{
    private const PACK_CHUNK_SIZE = 128 * 1024; //128KB

    private AddonsManager $addonsManager;

    protected function onEnable() : void{
        $this->addonsManager = AddonsManager::getInstance();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /** @priority LOWEST */
    public function onDataPacketSendEvent(DataPacketSendEvent $event){
        foreach($event->getPackets() as $packet){
            if($packet instanceof ResourcePackStackPacket){
                $packet->behaviorPackStack = array_merge($packet->behaviorPackStack, $this->addonsManager->getBehaviorPackStackEntries());
            }elseif($packet instanceof ResourcePacksInfoPacket){
                $packet->behaviorPackEntries = array_merge($packet->behaviorPackEntries, $this->addonsManager->getBehaviorPackInfoEntries());
            }elseif($packet instanceof StartGamePacket){
                (function(){ //HACK : Closure bind hack to access inaccessible members
                    /**
                     * @see Experiments::experiments
                     * @noinspection PhpUndefinedFieldInspection
                     */
                    $this->experiments = array_merge($this->experiments, [
                        "scripting" => true, // Additional Modding Capabilities
                        "upcoming_creator_features" => true, // Upcoming Creator Features
                        "gametest" => true, // Enable GameTest Framework
                        "data_driven_items" => true, // Holiday Creator Features
                        "experimental_molang_features" => true, // Experimental Molang Features
                    ]);
                })->call($packet->experiments);
            }
        }
    }

    /** @priority LOWEST */
    public function onDataPacketReceiveEvent(DataPacketReceiveEvent $event){
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
                        $pack->getSha256()
                    );
                    $pk->packType = ResourcePackType::BEHAVIORS;
                    $session->sendDataPacket($pk);
                }else{
                    $remained[$key] = $uuid;
                }
            }

            $session->getLogger()->debug("Player requested download of " . (count($packet->packIds) - count($remained)) . " behavior packs");
            $packet->packIds = $remained;
        }elseif($packet instanceof ResourcePackChunkRequestPacket){
            $pack = $this->addonsManager->getBehaviorPack($packet->packId);
            if($pack instanceof ResourcePack){
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

    /** @internal */
    public static function cleanDirName(string $path) : string{
        return rtrim(str_replace("\\", "/", $path), "/") . "/";
    }
}