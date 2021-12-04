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

use Ahc\Json\Comment as CommentedJsonDecoder;
use InvalidArgumentException;
use JsonMapper;
use JsonMapper_Exception as JsonMapperException;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackType;
use pocketmine\resourcepacks\json\Manifest;
use pocketmine\resourcepacks\ResourcePack as IResourcePack;
use pocketmine\resourcepacks\ResourcePackException;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use stdClass;
use ZipArchive;

use function array_filter;
use function file_get_contents;
use function gettype;
use function hash;
use function implode;
use function json_encode;
use function md5;
use function serialize;
use function str_ends_with;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function tempnam;
use function unserialize;

class Addons implements IResourcePack{
    private const TYPE_MAP = [
        "resources" => ResourcePackType::RESOURCES,
        "data" => ResourcePackType::BEHAVIORS
    ];

    public const MANIFEST_FILE = "manifest.json";

    protected Manifest $manifest;
    protected int $type = ResourcePackType::INVALID;

    protected string $contents;
    protected string $sha256;

    /**
     * @param array<string, string> $files innerPath => fileContents
     *
     * @throws ResourcePackException
     */
    protected function __construct(array $files){
        $manifestFile = $files[self::MANIFEST_FILE] ?? null;
        if($manifestFile === null){
            throw new ResourcePackException("manifest.json not found in the addons");
        }

        try{
            $this->manifest = $this->parseManifestFile($manifestFile);
        }catch(JsonMapperException $e){
            throw new ResourcePackException("Invalid manifest.json contents: " . $e->getMessage(), 0, $e);
        }

        $tempPath = tempnam(sys_get_temp_dir(),'pm$');
        $fullContents = "";

        $archive = new ZipArchive();
        $archive->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach($files as $innerPath => $contents){
            if(str_ends_with($innerPath, ".json")){
                $contents = json_encode((new CommentedJsonDecoder())->decode($contents), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $archive->addFromString($innerPath, $contents);
            $archive->setCompressionName($innerPath, ZipArchive::CM_DEFLATE64);
            $archive->setMtimeName($innerPath, 0);

            $fullContents .= $contents;
        }

        if($this->getPackId() === "00000000-0000-0000-0000-000000000000"){
            $this->manifest->header->uuid = Uuid::fromString(md5($fullContents))->toString();
        }
        if(empty($this->manifest->modules)){
            throw new ResourcePackException("Addons must have at least one module. (addons uuid: {$this->manifest->header->uuid})");
        }
        foreach($this->manifest->modules as $key => $module){
            $type = self::TYPE_MAP[$module->type] ?? ResourcePackType::INVALID;
            if($type === ResourcePackType::INVALID){
                throw new ResourcePackException("Module type must be 'resource' and 'data', '$module->type' given. (module uuid: $module->uuid)");
            }
            if($this->type !== ResourcePackType::INVALID && $this->type !== $type){
                throw new ResourcePackException("Multiple types of modules cannot exist in one add-on. (module uuid: $module->uuid)");
            }
            $this->type = $type;
            if($module->uuid === "00000000-0000-0000-0000-000000000000"){
                $module->uuid = UUID::fromString(md5($fullContents . $key))->toString();
            }
        }

        $archive->addFromString(self::MANIFEST_FILE, json_encode(array_filter([
            "format_version" => $this->manifest->format_version,
            "header" => $this->manifest->header,
            "modules" => $this->manifest->modules,
            "metadata" => $this->manifest->metadata,
            "capabilities" => $this->manifest->capabilities,
            "dependencies" => $this->manifest->dependencies
        ], static fn(mixed $v) : bool => !empty($v)), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $archive->setCompressionName(self::MANIFEST_FILE, ZipArchive::CM_DEFLATE64);
        $archive->setMtimeName(self::MANIFEST_FILE, 0);
        $archive->close();

        $this->contents = file_get_contents($tempPath);
        $this->sha256 = hash("sha256", $this->contents, true);
        unlink($tempPath);
    }

    /**
     * @param string $manifestFile
     *
     * @return Manifest
     * @throws JsonMapperException
     */
    private function parseManifestFile(string $manifestFile) : Manifest{
        $manifestJson = (new CommentedJsonDecoder())->decode($manifestFile);
        if(!($manifestJson instanceof stdClass)){
            throw new RuntimeException("manifest.json should contain a JSON object, not " . gettype($manifestJson));
        }

        $mapper = new JsonMapper();
        $mapper->bExceptionOnUndefinedProperty = true;
        $mapper->bExceptionOnMissingData = true;

        return $mapper->map($manifestJson, new Manifest());
    }

    /**
     * Returns the type of the addons.
     *
     * @see ResourcePackType
     */
    public function getType() : int{
        return $this->type;
    }

    /** Returns the Manifest object of the addons. */
    public function getManifest() : Manifest{
        // HACK: Deserialize after serialization for deep copy
        return unserialize(serialize($this->manifest), ['allowed_classes' => true]);
    }

    /** Returns the human-readable name of the addons */
    public function getPackName() : string{
        return $this->manifest->header->name;
    }

    /** Returns the addons UUID as a human-readable string */
    public function getPackId() : string{
        return $this->manifest->header->uuid;
    }

    /** Returns the size of the addons on disk in bytes. */
    public function getPackSize() : int{
        return strlen($this->contents) + 1;
    }

    /** Returns a version number for the addons in the format major.minor.patch */
    public function getPackVersion() : string{
        return implode(".", $this->manifest->header->version);
    }

    /**
     * Returns the raw SHA256 sum of the compressed addons zip. This is used by clients to validate addons downloads.
     *
     * @return string byte-array length 32 bytes
     */
    public function getSha256() : string{
        return $this->sha256;
    }

    /**
     * Returns a chunk of the addons zip as a byte-array for sending to clients.
     *
     * @param int $start Offset to start reading the chunk from
     * @param int $length Maximum length of data to return.
     *
     * @return string byte-array
     * @throws InvalidArgumentException if the chunk does not exist
     */
    public function getPackChunk(int $start, int $length) : string{
        return substr($this->contents, $start, $length);
    }
}