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

use InvalidArgumentException;
use pocketmine\network\mcpe\protocol\types\resourcepacks\BehaviorPackInfoEntry;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackType;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ref\api\addonsmanager\addons\Addons;
use ref\api\addonsmanager\addons\FolderAddons;
use ref\api\addonsmanager\addons\ZippedAddons;

use function array_diff;
use function array_merge;
use function array_values;
use function count;
use function file_exists;
use function is_dir;
use function mkdir;
use function preg_match;
use function scandir;
use function strtolower;

final class AddonsManager{
    use SingletonTrait;

    /** @var array<string, Addons> */
    private array $resourcePacks = [];
    /** @var array<string, Addons> */
    private array $behaviorPacks = [];

    /** @var ResourcePackInfoEntry[] */
    private array $resourcePackInfoEntries = [];
    /** @var BehaviorPackInfoEntry[] */
    private array $behaviorPackInfoEntries = [];

    /** @var ResourcePackStackEntry[] */
    private array $resourcePackStackEntries = [];
    /** @var ResourcePackStackEntry[] */
    private array $behaviorPackStackEntries = [];

    public function __construct(){
        $server = Server::getInstance();
        $logger = $server->getLogger();

        $logger->info("Loading addons...");
        $addonsPath = $server->getDataPath() . "addons/";
        if(!file_exists($addonsPath)){
            mkdir($addonsPath, 0777, true);
        }

        foreach(array_diff(scandir($addonsPath), [".", ".."]) as $innerPath){
            $realPath = $addonsPath . $innerPath;
            if(is_dir($realPath)){
                if(file_exists("$realPath/" . Addons::MANIFEST_FILE)){
                    $this->register(new FolderAddons($realPath));
                }
            }elseif(preg_match("/^(.+)\.(zip|mcpack)$/i", $realPath)){
                $this->register(new ZippedAddons($realPath));
            }
        }
        $logger->debug("Successfully loaded " . (count($this->resourcePacks) + count($this->behaviorPacks)) . " addons");
    }

    /** @return $this */
    public function register(Addons $addons) : self{
        $id = strtolower($addons->getUuid());
        $version = $addons->getVersion();
        if($addons->getType() === ResourcePackType::RESOURCES){
            $this->resourcePacks[$id] = $addons;
            $this->resourcePackInfoEntries[$id] = new ResourcePackInfoEntry($id, $version, $addons->getSize());
            $this->resourcePackStackEntries[$id] = new ResourcePackStackEntry($id, $version, "");
        }elseif($addons->getType() === ResourcePackType::BEHAVIORS){
            $this->behaviorPacks[$id] = $addons;
            $this->behaviorPackInfoEntries[$id] = new BehaviorPackInfoEntry($id, $version, $addons->getSize());
            $this->behaviorPackStackEntries[$id] = new ResourcePackStackEntry($id, $version, "");
        }else{
            throw new InvalidArgumentException("Invalid Addons type");
        }
        return $this;
    }

    /** Remove the resource pack or behavior pack matching the specified UUID string, or null if the ID was not recognized. */
    public function unregister(string $id) : self{
        $id = strtolower($id);
        unset(
            $this->resourcePacks[$id],
            $this->resourcePackInfoEntries[$id],
            $this->resourcePackStackEntries[$id],
            $this->behaviorPacks[$id],
            $this->behaviorPackInfoEntries[$id],
            $this->behaviorPackStackEntries[$id]
        );
        return $this;
    }

    /** Returns the resource pack or behavior pack matching the specified UUID string, or null if the ID was not recognized. */
    public function get(string $id) : ?Addons{
        $id = strtolower($id);
        return $this->resourcePacks[$id] ?? $this->behaviorPacks[$id] ?? null;
    }

    /** @return Addons[] */
    public function getAllAddons() : array{
        return array_values(array_merge($this->resourcePacks, $this->behaviorPacks));
    }

    /** @return Addons[] */
    public function getResourcePacks() : array{
        return $this->resourcePacks;
    }

    /** @return Addons[] */
    public function getBehaviorPacks() : array{
        return $this->behaviorPacks;
    }

    /**
     * @return ResourcePackInfoEntry[]
     * @internal
     */
    public function getResourcePackInfoEntries() : array{
        return array_values($this->resourcePackInfoEntries);
    }

    /**
     * @return BehaviorPackInfoEntry[]
     * @internal
     */
    public function getBehaviorPackInfoEntries() : array{
        return array_values($this->behaviorPackInfoEntries);
    }

    /**
     * @return ResourcePackStackEntry[]
     * @internal
     */
    public function getResourcePackStackEntries() : array{
        return array_values($this->resourcePackStackEntries);
    }

    /**
     * @return ResourcePackStackEntry[]
     * @internal
     */
    public function getBehaviorPackStackEntries() : array{
        return array_values($this->behaviorPackStackEntries);
    }
}