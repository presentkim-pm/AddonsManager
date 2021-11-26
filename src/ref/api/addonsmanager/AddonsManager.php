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

use pocketmine\network\mcpe\protocol\types\resourcepacks\BehaviorPackInfoEntry;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\resourcepacks\ResourcePackManager;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

use function array_keys;
use function array_search;
use function array_values;
use function strtolower;

final class AddonsManager{
    use SingletonTrait;

    private ResourcePackManager $resourcePackManager;

    /** @var array<string, ResourcePack> */
    private array $behaviorPacks = [];

    /** @var ResourcePackStackEntry[] */
    private array $behaviorStackEntries = [];

    /** @var BehaviorPackInfoEntry[] */
    private array $behaviorInfoEntries = [];

    public function __construct(){
        $this->resourcePackManager = Server::getInstance()->getResourcePackManager();
    }

    /** @return ResourcePack[] */
    public function getBehaviorPacks() : array{
        return array_values($this->behaviorPacks);
    }

    /** @return ResourcePack[] */
    public function getResourcePacks() : array{
        return $this->resourcePackManager->getResourceStack();
    }

    /** @return string[] */
    public function getBehaviorIds() : array{
        return array_keys($this->behaviorPacks);
    }

    /** @return string[] */
    public function getResourceIds() : array{
        return $this->resourcePackManager->getPackIdList();
    }

    /** Returns the behavior pack matching the specified UUID string, or null if the ID was not recognized. */
    public function getBehaviorPack(string $id) : ?ResourcePack{
        return $this->behaviorPacks[strtolower($id)] ?? null;
    }

    /** Returns the resource pack matching the specified UUID string, or null if the ID was not recognized. */
    public function getResourcePack(string $id) : ?ResourcePack{
        return $this->resourcePackManager->getPackById($id);
    }

    /** @return $this */
    public function registerBehaviorPack(ResourcePack $pack) : self{
        $uuid = strtolower($pack->getPackId());
        $this->behaviorPacks[$uuid] = $pack;

        //register entry caches also
        $version = $pack->getPackVersion();
        $this->behaviorStackEntries[$uuid] = new ResourcePackStackEntry($uuid, $version, "");
        $this->behaviorInfoEntries[$uuid] = new BehaviorPackInfoEntry($uuid, $version, $pack->getPackSize(), "", "", "", false);
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
        })->call($this->resourcePackManager);
        return $this;
    }

    /** @return $this */
    public function unregisterBehaviorPack(ResourcePack $pack) : self{
        $uuid = strtolower($pack->getPackId());
        if(isset($this->behaviorPacks[$uuid])){
            unset(
                $this->behaviorPacks[$uuid],
                $this->behaviorStackEntries[$uuid],
                $this->behaviorInfoEntries[$uuid]
            );
        }
        return $this;
    }

    /**
     * @return $this
     * @noinspection PhpUndefinedFieldInspection
     */
    public function unregisterResourcePack(ResourcePack $pack) : self{
        $uuid = strtolower($pack->getPackId());
        if($this->resourcePackManager->getPackById($uuid) === null){
            return $this;
        }
        (function() use ($pack, $uuid){ //HACK : Closure bind hack to access inaccessible members
            /** @see ResourcePackManager::resourcePacks */
            /** @see ResourcePackManager::uuidList */
            unset(
                $this->resourcePacks[array_search($pack, $this->resourcePacks, true)],
                $this->uuidList[$uuid]
            );
        })->call($this->resourcePackManager);
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