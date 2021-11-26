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

namespace ref\register\addons\pack;

use ref\register\addons\Main;
use pocketmine\resourcepacks\ResourcePackException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function file_exists;
use function is_dir;
use function str_replace;
use function strlen;
use function substr;

class FolderResourcePack extends BaseResourcePack{
    /** @throws ResourcePackException */
    public function __construct(string $dir){
        $dir = Main::cleanDirName($dir);
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