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
use pocketmine\resourcepacks\json\Manifest;
use pocketmine\resourcepacks\ResourcePack as IResourcePack;
use pocketmine\resourcepacks\ResourcePackException;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use stdClass;
use Webmozart\PathUtil\Path;
use ZipArchive;

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
use function unserialize;

class Addons implements IResourcePack{
    public const MANIFEST_FILE = "manifest.json";

    protected Manifest $manifest;

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
            throw new ResourcePackException("manifest.json not found in the pack");
        }

        try{
            $manifestJson = (new CommentedJsonDecoder())->decode($manifestFile);
            if(!($manifestJson instanceof stdClass)){
                throw new RuntimeException("manifest.json should contain a JSON object, not " . gettype($manifestJson));
            }

            $mapper = new JsonMapper();
            $mapper->bExceptionOnUndefinedProperty = true;
            $mapper->bExceptionOnMissingData = true;

            $this->manifest = $mapper->map($manifestJson, new Manifest());
        }catch(RuntimeException $e){
            throw new ResourcePackException("Failed to parse manifest.json: " . $e->getMessage(), $e->getCode(), $e);
        }catch(JsonMapperException $e){
            throw new ResourcePackException("Invalid manifest.json contents: " . $e->getMessage(), 0, $e);
        }

        $tmp = Path::canonicalize(Server::getInstance()->getDataPath()) . "/\$TEMP_" . md5($manifestFile) . ".zip";
        $fullContents = "";

        $archive = new ZipArchive();
        $archive->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach($files as $innerPath => $contents){
            if(str_ends_with($contents, ".json")){
                $contents = json_encode((new CommentedJsonDecoder())->decode($contents), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $archive->addFromString($innerPath, $contents);

            $fullContents .= $contents;
        }

        if($this->getPackId() === "00000000-0000-0000-0000-000000000000"){
            $this->manifest->header->uuid = Uuid::fromString(md5($fullContents))->toString();
            foreach($this->manifest->modules as $key => $module){
                if($module->uuid === "00000000-0000-0000-0000-000000000000"){
                    $module->uuid = UUID::fromString(md5($fullContents . $key))->toString();
                }
            }
        }
        $archive->addFromString(self::MANIFEST_FILE, json_encode($this->manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $archive->close();

        $this->contents = file_get_contents($tmp);
        $this->sha256 = hash("sha256", $this->contents, true);
    }

    /** Returns the Manifest object of the ResourcePack. */
    public function getManifest() : Manifest{
        // HACK: Deserialize after serialization for deep copy
        return unserialize(serialize($this->manifest), ['allowed_classes' => true]);
    }

    /** Returns the human-readable name of the resource pack */
    public function getPackName() : string{
        return $this->manifest->header->name;
    }

    /** Returns the pack's UUID as a human-readable string */
    public function getPackId() : string{
        return $this->manifest->header->uuid;
    }

    /** Returns the size of the pack on disk in bytes. */
    public function getPackSize() : int{
        return strlen($this->contents) + 1;
    }

    /** Returns a version number for the pack in the format major.minor.patch */
    public function getPackVersion() : string{
        return implode(".", $this->manifest->header->version);
    }

    /**
     * Returns the raw SHA256 sum of the compressed resource pack zip. This is used by clients to validate pack downloads.
     *
     * @return string byte-array length 32 bytes
     */
    public function getSha256() : string{
        return $this->sha256;
    }

    /**
     * Returns a chunk of the resource pack zip as a byte-array for sending to clients.
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