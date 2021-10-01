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
 * @noinspection SpellCheckingInspection
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\addonsmanager\resourcepack;

use kim\present\addonsmanager\Loader;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePackException;

use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

class PluginResourcePack extends ResourcePack{
    /** @throws ResourcePackException */
    public function __construct(PluginBase $plugin, string $innerDir){
        $innerDir = Loader::cleanDirName($innerDir);
        $filePaths = [];

        foreach($plugin->getResources() as $key => $fileInfo){
            $path = str_replace("\\", "/", $key);
            if(str_starts_with($path, $innerDir)){
                $realPath = $fileInfo->getPathname();
                $innerPath = str_replace("\\", "/", substr($path, strlen($innerDir)));

                $filePaths[$innerPath] = $realPath;
            }
        }
        parent::__construct($filePaths);
    }
}