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
use ZipArchive;

use function file_exists;
use function str_ends_with;

class ZippedAddons extends Addons{
    /** @throws ResourcePackException */
    public function __construct(string $zipPath){
        if(!file_exists($zipPath)){
            throw new ResourcePackException("File not found : $zipPath");
        }

        $archive = new ZipArchive();
        if(($openResult = $archive->open($zipPath)) !== true){
            throw new ResourcePackException("Encountered ZipArchive error code $openResult while trying to open $zipPath");
        }
        $files = [];
        for($i = 0; $i < $archive->numFiles; ++$i){
            $innerPath = $archive->getNameIndex($i);
            if(!str_ends_with($innerPath, '/')){
                $files[$innerPath] = $archive->getFromIndex($i);
            }
        }
        parent::__construct($files);
    }
}