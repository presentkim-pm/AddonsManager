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
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePackException;

use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

class PluginResourcePack extends BaseResourcePack{
    /** @throws ResourcePackException */
    public function __construct(PluginBase $plugin, string $innerDir){
        $innerDir = Main::cleanDirName($innerDir);
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