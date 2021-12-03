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

namespace ref\api\addonsmanager\addons;

use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePackException;
use Webmozart\PathUtil\Path;

use function file_get_contents;
use function str_starts_with;

class PluginAddons extends Addons{
    /** @throws ResourcePackException */
    public function __construct(PluginBase $plugin, string $innerDir){
        $baseDir = Path::canonicalize($innerDir) . "/";
        $files = [];

        foreach($plugin->getResources() as $key => $fileInfo){
            $path = Path::canonicalize($key);
            if(str_starts_with($path, $baseDir)){
                $realPath = $fileInfo->getPathname();
                $innerPath = Path::makeRelative($path, $baseDir);

                $contents = file_get_contents($realPath);
                if($contents === false){
                    throw new ResourcePackException("Failed to open $realPath file");
                }
                $files[$innerPath] = $contents;
            }
        }
        parent::__construct($files);
    }
}