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

use pocketmine\resourcepacks\ResourcePackException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Webmozart\PathUtil\Path;

use function file_exists;
use function file_get_contents;
use function is_dir;

class FolderAddons extends Addons{
    /** @throws ResourcePackException */
    public function __construct(string $dir){
        $baseDir = Path::canonicalize($dir) . "/";
        if(!file_exists($baseDir) || !is_dir($baseDir)){
            throw new ResourcePackException("$baseDir is invalid path or not directory");
        }

        $files = [];
        /** @var SplFileInfo $fileInfo */
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir)) as $fileInfo){
            if($fileInfo->isFile()){
                $realPath = $fileInfo->getPathname();
                $innerPath = Path::makeRelative($realPath, $baseDir);

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