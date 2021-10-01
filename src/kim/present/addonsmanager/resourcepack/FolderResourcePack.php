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
use pocketmine\resourcepacks\ResourcePackException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function file_exists;
use function is_dir;
use function str_replace;
use function strlen;
use function substr;

class FolderResourcePack extends ResourcePack{
    /** @throws ResourcePackException */
    public function __construct(string $dir){
        $dir = Loader::cleanDirName($dir);
        if(!file_exists($dir) || !is_dir($dir)){
            throw new ResourcePackException("$dir is invalid path or not directory");
        }

        $files = [];
        /** @var SplFileInfo $fileInfo */
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $fileInfo){
            if($fileInfo->isFile()){
                $realPath = $fileInfo->getPathname();
                $innerPath = str_replace("\\", "/", substr($realPath, strlen($dir)));
                $files[$innerPath] = $realPath;
            }
        }
        parent::__construct($files);
    }
}