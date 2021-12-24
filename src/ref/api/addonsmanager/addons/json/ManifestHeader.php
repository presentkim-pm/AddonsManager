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

namespace ref\api\addonsmanager\addons\json;

final class ManifestHeader extends ManifestEntry{
    /**
     * @param string                            $name
     * @param string                            $uuid
     * @param int[]                             $version
     * @param string|null                       $description
     * @param int[]|null                        $min_engine_version
     *
     * @phpstan-param array{int, int, int}      $version
     * @phpstan-param array{int, int, int}|null $min_engine_version
     */
    public function __construct(
        public string $name = " ",
        public string $uuid = "",
        public array $version = [1, 0, 0],
        public ?string $description = null,
        public ?array $min_engine_version = null
    ){
    }
}
