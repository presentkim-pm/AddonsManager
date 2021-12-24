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

/**
 * Model for JsonMapper to represent resource pack manifest.json contents.
 */
final class Manifest extends ManifestEntry{
    public int $format_version = 2;

    public ManifestHeader $header;

    /** @var ManifestModuleEntry[] */
    public array $modules = [];

    public ?ManifestMetadata $metadata = null;

    /** @var string[] */
    public ?array $capabilities = null;

    /** @var ManifestDependencyEntry[] */
    public ?array $dependencies = null;

    /**
     * @param int                            $format_version
     * @param ManifestHeader                 $header
     * @param ManifestModuleEntry[]          $modules
     * @param ManifestMetadata|null          $metadata
     * @param string[]|null                  $capabilities
     * @param ManifestDependencyEntry[]|null $dependencies
     *
     * @return self
     */
    public static function create(
        int $format_version,
        ManifestHeader $header,
        array $modules = [],
        ?ManifestMetadata $metadata = null,
        ?array $capabilities = null,
        ?array $dependencies = null
    ) : self{
        $manifest = new self();
        $manifest->format_version = $format_version;
        $manifest->header = $header;
        $manifest->modules = $modules;
        $manifest->metadata = $metadata;
        $manifest->capabilities = $capabilities;
        $manifest->dependencies = $dependencies;
        return $manifest;
    }
}
