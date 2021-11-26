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

namespace ref\api\addonsmanager\addons;

use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ResourcePackException;
use Webmozart\PathUtil\Path;

use function str_starts_with;

class PluginAddons extends Addons{
    /** @throws ResourcePackException */
    public function __construct(PluginBase $plugin, string $innerDir){
        $innerDir = Path::canonicalize($innerDir) . "/";
        $filePaths = [];

        foreach($plugin->getResources() as $key => $fileInfo){
            $path = Path::canonicalize($key);
            if(str_starts_with($path, $innerDir)){
                $realPath = $fileInfo->getPathname();
                $innerPath = Path::makeRelative($path, $innerDir);

                $filePaths[$innerPath] = $realPath;
            }
        }
        parent::__construct($filePaths);
    }
}