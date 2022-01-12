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

use JsonSerializable;

use function array_filter;
use function array_map;
use function get_object_vars;
use function is_array;
use function is_object;

abstract class ManifestEntry implements JsonSerializable{
    /** JSON serialize with exclude empty values */
    public function jsonSerialize() : array{
        return array_filter(get_object_vars($this), static fn(mixed $value) : bool => $value !== "" && $value !== null);
    }

    /** Perform a deep copy on clone */
    public function __clone() : void{
        foreach($this->jsonSerialize() as $key => $value){
            if(is_object($value)){
                $this->$key = clone $value;
            }elseif(is_array($value)){
                $this->$key = array_map(static fn($v) => is_object($v) ? clone $v : $v, $value);
            }else{
                $this->$key = $value;
            }
        }
    }
}
