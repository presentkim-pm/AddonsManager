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
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\addonsmanager;

use pocketmine\network\mcpe\protocol\types\resourcepacks\BehaviorPackInfoEntry;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use PrefixedLogger;

use function array_keys;
use function array_values;
use function strtolower;

final class AddonsManager{
    use SingletonTrait;

    private PrefixedLogger $logger;

    /** @var array<string, ResourcePack> */
    private array $behaviorPacks = [];

    /** @var ResourcePackStackEntry[] */
    private array $behaviorStackEntries = [];

    /** @var BehaviorPackInfoEntry[] */
    private array $behaviorInfoEntries = [];

    public function __construct(){
        $this->logger = new PrefixedLogger(Server::getInstance()->getLogger(), "AddonsManager");
    }

    /** @return ResourcePack[] */
    public function getBehaviorPacks() : array{
        return array_values($this->behaviorPacks);
    }

    /** @return ResourcePack[] */
    public function getResourcePacks() : array{
        return Server::getInstance()->getResourcePackManager()->getResourceStack();
    }

    /** @return string[] */
    public function getBehaviorIds() : array{
        return array_keys($this->behaviorPacks);
    }

    /** @return string[] */
    public function getResourceIds() : array{
        return Server::getInstance()->getResourcePackManager()->getPackIdList();
    }

    /** Returns the behavior pack matching the specified UUID string, or null if the ID was not recognized. */
    public function getBehaviorPack(string $id) : ?ResourcePack{
        return $this->behaviorPacks[strtolower($id)] ?? null;
    }

    /** Returns the resource pack matching the specified UUID string, or null if the ID was not recognized. */
    public function getResourcePack(string $id) : ?ResourcePack{
        return Server::getInstance()->getResourcePackManager()->getPackById($id);
    }

    /** @return $this */
    public function registerBehaviorPack(ResourcePack $pack) : self{
        $uuid = strtolower($pack->getPackId());
        $this->behaviorPacks[$uuid] = $pack;

        //register entry caches also
        $version = $pack->getPackVersion();
        $size = $pack->getPackSize();
        $this->behaviorStackEntries[$uuid] = new ResourcePackStackEntry($uuid, $version, "");
        $this->behaviorInfoEntries[$uuid] = new BehaviorPackInfoEntry($uuid, $version, $size, "", "", "", false);

        $this->logger->debug("Behavior pack registered : {$pack->getPackName()}_v$version [$uuid](size: $size bytes)");
        return $this;
    }

    /**
     * @return $this
     * @noinspection PhpUndefinedFieldInspection
     */
    public function registerResourcePack(ResourcePack $pack) : self{
        $uuid = strtolower($pack->getPackId());
        (function() use ($pack, $uuid){ //HACK : Closure bind hack to access inaccessible members
            /** @see ResourcePackManager::resourcePacks */
            /** @see ResourcePackManager::uuidList */
            $this->resourcePacks[] = $pack;
            $this->uuidList[$uuid] = $pack;
        })->call(Server::getInstance()->getResourcePackManager());

        $this->logger->debug("Resource pack registered : {$pack->getPackName()}_v{$pack->getPackVersion()} [$uuid] (size: {$pack->getPackSize()} bytes)");
        return $this;
    }

    /**
     * @return ResourcePackStackEntry[]
     * @internal
     */
    public function getBehaviorPackStackEntries() : array{
        return array_values($this->behaviorStackEntries);
    }

    /**
     * @return BehaviorPackInfoEntry[]
     * @internal
     */
    public function getBehaviorPackInfoEntries() : array{
        return array_values($this->behaviorInfoEntries);
    }
}