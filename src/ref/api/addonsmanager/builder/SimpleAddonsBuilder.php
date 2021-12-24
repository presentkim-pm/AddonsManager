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
 * @noinspection PhpDocSignatureIsNotCompleteInspection
 */

declare(strict_types=1);

namespace ref\api\addonsmanager\builder;

use GdImage;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackType;
use pocketmine\resourcepacks\json\Manifest;
use pocketmine\resourcepacks\json\ManifestHeader;
use pocketmine\resourcepacks\json\ManifestModuleEntry;
use pocketmine\resourcepacks\ResourcePackException;
use ref\api\addonsmanager\addons\Addons;

use function file_get_contents;
use function imagepng;
use function json_encode;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class SimpleAddonsBuilder{
    /** @var array<string, string> */
    private array $files = [];

    /** @param $version array{int, int, int} */
    public function __construct(
        private int $type,
        private string $name,
        private string $description = "",
        private string $uuid = "",
        private array $version = [1, 0, 0]
    ){
        if($type !== ResourcePackType::RESOURCES && $type !== ResourcePackType::BEHAVIORS){
            throw new ResourcePackException("Module type must be 'RESOURCES' or 'BEHAVIORS', '$type' given.");
        }
    }

    public function string(string $path, string $content) : self{
        $this->files[$path] = $content;
        return $this;
    }

    public function json(string $path, mixed $json) : self{
        $this->files[$path] = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function image(string $path, GdImage $image) : self{
        $tempPath = tempnam(sys_get_temp_dir(), 'pm$');
        imagepng($image, $tempPath);
        $this->files[$path] = file_get_contents($tempPath);
        unlink($tempPath);
        return $this;
    }

    public function build() : Addons{
        $this->files[Addons::MANIFEST_FILE] = Addons::manifestSerialize($this->generateManifest());
        $addons = new Addons($this->files);
        unset($this->files[Addons::MANIFEST_FILE]);
        return $addons;
    }

    private function generateManifest() : Manifest{
        $manifest = new Manifest();
        $manifest->format_version = 2;
        $manifest->header = new ManifestHeader();
        $manifest->header->name = $this->name;
        $manifest->header->description = $this->description;
        $manifest->header->uuid = $this->uuid;
        $manifest->header->version = $this->version;
        $manifest->header->min_engine_version = [1, 17, 0];
        $moduleEntry = new ManifestModuleEntry();
        $moduleEntry->type = $this->type === ResourcePackType::RESOURCES ? "resources" : "data";
        $moduleEntry->uuid = "";
        $moduleEntry->version = [1, 0, 0];
        $manifest->modules = [$moduleEntry];

        return $manifest;
    }
}