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

use pocketmine\network\mcpe\protocol\types\resourcepacks\BehaviorPackInfoEntry;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;
use ref\api\addonsmanager\addons\Addons;

use function array_keys;
use function array_values;
use function strtolower;

final class AddonsMap{
    /** @var array<string, Addons> */
    private array $addons = [];

    /** @var ResourcePackStackEntry[] */
    private array $stackEntries = [];

    /** @var BehaviorPackInfoEntry[] */
    private array $infoEntries = [];

    /** @return Addons[] */
    public function getValues() : array{
        return array_values($this->addons);
    }

    /** @return string[] */
    public function getKeys() : array{
        return array_keys($this->addons);
    }

    /** Returns the addons matching the specified UUID string, or null if the ID was not recognized. */
    public function get(string $id) : ?Addons{
        return $this->addons[strtolower($id)] ?? null;
    }

    /** @return $this */
    public function add(Addons $addons) : self{
        $uuid = strtolower($addons->getPackId());
        $this->addons[$uuid] = $addons;

        //register entry caches also
        $version = $addons->getPackVersion();
        $this->stackEntries[$uuid] = new ResourcePackStackEntry($uuid, $version, "");
        $this->infoEntries[$uuid] = new BehaviorPackInfoEntry($uuid, $version, $addons->getPackSize(), "", "", "", false);
        return $this;
    }

    /** @return $this */
    public function remove(string $uuid) : self{
        $uuid = strtolower($uuid);
        unset(
            $this->addons[$uuid],
            $this->stackEntries[$uuid],
            $this->infoEntries[$uuid]
        );
        return $this;
    }

    /**
     * @return ResourcePackStackEntry[]
     * @internal
     */
    public function getStackEntries() : array{
        return array_values($this->stackEntries);
    }

    /**
     * @return BehaviorPackInfoEntry[]
     * @internal
     */
    public function getInfoEntries() : array{
        return array_values($this->infoEntries);
    }
}