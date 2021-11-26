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

namespace ref\api\addonsmanager\pack;

use Ahc\Json\Comment as CommentedJsonDecoder;
use InvalidArgumentException;
use pocketmine\resourcepacks\ResourcePack as IResourcePack;
use pocketmine\resourcepacks\ResourcePackException;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Webmozart\PathUtil\Path;
use ZipArchive;

use function file_exists;
use function file_get_contents;
use function hash;
use function implode;
use function is_file;
use function json_encode;
use function md5;
use function str_ends_with;
use function strlen;
use function substr;
use function unlink;

class BaseResourcePack implements IResourcePack{
    public const MANIFEST_FILE = "manifest.json";

    protected string $name;
    protected string $id;
    protected string $version;
    protected string $sha256;

    protected string $contents;

    /**
     * @param array<string, string> $files innerPath => realPath
     *
     * @throws ResourcePackException
     */
    protected function __construct(array $files){
        if(!isset($files[self::MANIFEST_FILE]) || !file_exists($manifestPath = $files[self::MANIFEST_FILE])){
            throw new ResourcePackException("manifest.json not found in the pack");
        }

        $manifestData = file_get_contents($manifestPath);
        if($manifestData === false){
            throw new ResourcePackException("Failed to open manifest.json file.");
        }

        try{
            $manifest = (new CommentedJsonDecoder())->decode($manifestData, true);
        }catch(RuntimeException $e){
            throw new ResourcePackException("Failed to parse manifest.json: " . $e->getMessage(), $e->getCode(), $e);
        }finally{
            unset($files[self::MANIFEST_FILE]);
        }

        if(!isset(
            $manifest["header"]["name"],
            $manifest["header"]["uuid"],
            $manifest["header"]["version"],
            $manifest["modules"]
        )){
            throw new ResourcePackException("manifest.json is missing required fields");
        }

        $header = $manifest["header"];
        $this->name = $header["name"];
        $this->version = implode(".", $header["version"]);
        $this->id = $header["uuid"];

        $tmp = Path::canonicalize(Server::getInstance()->getDataPath()) . "/\$TEMP_" . md5($manifestPath) . ".zip";
        $fullContents = "";

        $archive = new ZipArchive();
        $archive->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach($files as $innerPath => $realPath){
            if(is_file($realPath)){
                $contents = file_get_contents($realPath);
                if(str_ends_with($realPath, ".json")){
                    $contents = json_encode((new CommentedJsonDecoder())->decode($contents), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
                $archive->addFromString($innerPath, $contents);

                $fullContents .= $contents;
            }
        }

        if($this->id === "00000000-0000-0000-0000-000000000000"){
            $this->id = Uuid::fromString(md5($fullContents))->toString();
            $manifest["header"]["uuid"] = $this->id;
            if(isset($manifest["modules"])){
                foreach($manifest["modules"] as $key => $module){
                    if($manifest["modules"][$key]["uuid"] === "00000000-0000-0000-0000-000000000000"){
                        $manifest["modules"][$key]["uuid"] = UUID::fromString(md5($fullContents . $key))->toString();
                    }
                }
            }
        }
        $archive->addFromString(self::MANIFEST_FILE, json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $archive->close();

        $this->contents = file_get_contents($tmp);
        $this->sha256 = hash("sha256", $this->contents, true);
        unlink($tmp);
    }

    /** Returns the human-readable name of the resource pack */
    public function getPackName() : string{
        return $this->name;
    }

    /** Returns the pack's UUID as a human-readable string */
    public function getPackId() : string{
        return $this->id;
    }

    /** Returns the size of the pack on disk in bytes. */
    public function getPackSize() : int{
        return strlen($this->contents) + 1;
    }

    /** Returns a version number for the pack in the format major.minor.patch */
    public function getPackVersion() : string{
        return $this->version;
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