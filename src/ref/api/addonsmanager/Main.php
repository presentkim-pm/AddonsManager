<?php

/**            __   _____
 *  _ __ ___ / _| |_   _|__  __ _ _ __ ___
 * | '__/ _ \ |_    | |/ _ \/ _` | '_ ` _ \
 * | | |  __/  _|   | |  __/ (_| | | | | | |
 * |_|  \___|_|     |_|\___|\__,_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  ref-team
 * @link    https://github.com/refteams
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
use pocketmine\plugin\PluginBase;
use ref\api\addonsmanager\addons\Addons;

use function array_merge;
use function ceil;
use function count;
use function file_exists;
use function rmdir;
use function scandir;
use function strpos;
use function substr;

final class Main extends PluginBase implements Listener{
    private const MAX_CHUNK_SIZE = 128 * 1024; //128KB
    private const OVERRIDDEN_EXPERIMENTS = [
        "scripting" => true, // Additional Modding Capabilities
        "upcoming_creator_features" => true, // Upcoming Creator Features
        "gametest" => true, // Enable GameTest Framework
        "data_driven_items" => true, // Holiday Creator Features
        "experimental_molang_features" => true, // Experimental Molang Features
    ];

    private AddonsManager $addonsManager;

    protected function onLoad() : void{
        $this->addonsManager = AddonsManager::getInstance();
    }

    protected function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        //Remove unnecessary plugin data folder
        $dataFolder = $this->getDataFolder();
        if(file_exists($dataFolder) && count(scandir($dataFolder)) === 2){
            rmdir($dataFolder);
        }
    }

    /** @priority LOWEST */
    public function onDataPacketSendEvent(DataPacketSendEvent $event) : void{
        foreach($event->getPackets() as $packet){
            if($packet instanceof ResourcePacksInfoPacket){
                foreach($this->addonsManager->getResourcePackInfoEntries() as $entry){
                    $packet->resourcePackEntries[] = $entry;
                }
                foreach($this->addonsManager->getBehaviorPackInfoEntries() as $entry){
                    $packet->behaviorPackEntries[] = $entry;
                }
            }elseif($packet instanceof ResourcePackStackPacket){
                foreach($this->addonsManager->getResourcePackStackEntries() as $entry){
                    $packet->resourcePackStack[] = $entry;
                }
                foreach($this->addonsManager->getBehaviorPackStackEntries() as $entry){
                    $packet->behaviorPackStack[] = $entry;
                }
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
                $addons = $this->addonsManager->get($uuid);
                if($addons !== null){
                    $session->sendDataPacket(ResourcePackDataInfoPacket::create(
                        $addons->getUuid(),
                        self::MAX_CHUNK_SIZE,
                        (int) ceil($addons->getSize() / self::MAX_CHUNK_SIZE),
                        $addons->getSize(),
                        $addons->getSha256(),
                        false,
                        $addons->getType()
                    ));
                }else{
                    $remained[$key] = $uuid;
                }
            }

            $session->getLogger()->debug("Player requested download of " . (count($packet->packIds) - count($remained)) . " addons");
            $packet->packIds = $remained;
        }elseif(
            $packet instanceof ResourcePackChunkRequestPacket &&
            ($addons = $this->addonsManager->get($packet->packId)) instanceof Addons
        ){
            $event->getOrigin()->sendDataPacket(ResourcePackChunkDataPacket::create(
                $addons->getUuid(),
                $packet->chunkIndex,
                (self::MAX_CHUNK_SIZE * $packet->chunkIndex),
                $addons->getChunk(self::MAX_CHUNK_SIZE * $packet->chunkIndex, self::MAX_CHUNK_SIZE)
            ));
            $event->cancel();
        }
    }
}