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
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ref\api\addonsmanager\addons\Addons;
use ref\api\addonsmanager\addons\FolderAddons;
use ref\api\addonsmanager\addons\ZippedAddons;

use function array_diff;
use function count;
use function file_exists;
use function is_dir;
use function mkdir;
use function preg_match;
use function scandir;

final class AddonsManager{
    use SingletonTrait;

    private AddonsMap $resourceMap;
    private AddonsMap $behaviorMap;

    /** @var array<string, ResourcePack> */
    private array $behaviorPacks = [];

    /** @var ResourcePackStackEntry[] */
    private array $behaviorStackEntries = [];

    /** @var BehaviorPackInfoEntry[] */
    private array $behaviorInfoEntries = [];

    public function __construct(){
        $server = Server::getInstance();
        $logger = $server->getLogger();

        $this->resourceMap = new AddonsMap();
        $this->behaviorMap = new AddonsMap();

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
        $logger->debug("Successfully loaded " . (count($this->resourceMap->getValues()) + count($this->behaviorMap->getValues())) . " addons");
    }

    public function getResourceMap() : AddonsMap{
        return $this->resourceMap;
    }

    public function getBehaviorMap() : AddonsMap{
        return $this->behaviorMap;
    }

    /** @return $this */
    public function register(Addons $addons) : self{
        if($addons->getType() === Addons::TYPE_RESOURCE){
            $this->resourceMap->add($addons);
        }elseif($addons->getType() === Addons::TYPE_BEHAVIOR){
            $this->behaviorMap->add($addons);
        }else{
            throw new InvalidArgumentException("Invalid Addons type");
        }
        return $this;
    }

    /** Returns the resource pack or behavior pack matching the specified UUID string, or null if the ID was not recognized. */
    public function unregister(string $id) : self{
        $this->resourceMap->remove($id);
        $this->behaviorMap->remove($id);
        return $this;
    }

    /** Returns the resource pack or behavior pack matching the specified UUID string, or null if the ID was not recognized. */
    public function get(string $id) : ?Addons{
        return $this->resourceMap->get($id) ?? $this->behaviorMap->get($id);
    }
}