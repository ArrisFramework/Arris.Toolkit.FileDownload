<?php
/**
 * Provides the possibility to easily create file downloads in PHP
 *
 * @author Jannik Zschiesche <hello@apfelbox.net>
 * @author Karel Wintersky <karel.wintersky@gmail.com>
 * @version 1.0
 * @license MIT
 */

namespace Arris\Toolkit;

use InvalidArgumentException;
use RuntimeException;

class FileDownload implements FileDownloadInterface
{
    /**
     * The pointer to the file to download
     *
     * @var resource
     */
    private $filePointer;

    /**
     * @var string
     */
    private $fileName;

    /**
     * Constructs a new file download
     *
     * @param resource $filePointer
     *
     * @throws InvalidArgumentException
     */
    public function __construct($filePointer, string $filePath)
    {
        if (!is_resource($filePointer)) {
            throw new InvalidArgumentException("You must pass a file pointer to the constructor");
        }

        $this->filePointer = $filePointer;
        $this->fileName = pathinfo($filePath, PATHINFO_BASENAME);
    }

    public function sendDownload(string $filename = '', bool $forceDownload = true)
    {
        if (headers_sent()) {
            throw new RuntimeException("Cannot send file to the browser, since the headers were already sent.");
        }

        if (empty($filename)) {
            $filename = $this->fileName;
        }

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Type: {$this->getMimeType($filename)}");

        if ($forceDownload) {
            header("Content-Disposition: attachment; filename=\"{$filename}\";");
        } else {
            header("Content-Disposition: filename=\"{$filename}\";");
        }

        header("Content-Transfer-Encoding: binary");
        header("Content-Length: {$this->getFileSize()}");

        @ob_clean();

        rewind($this->filePointer);
        fpassthru($this->filePointer);
    }

    /**
     * Returns the mime type of a file name
     *
     * @param string $fileName
     *
     * @return string
     */
    private function getMimeType(string $fileName): string
    {
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $mimeTypeHelper = Mimetypes::getInstance();
        $mimeType = $mimeTypeHelper->fromExtension($fileExtension);

        return !is_null($mimeType) ? $mimeType : "application/force-download";
    }

    /**
     * Returns the file size of the file
     *
     * @return int
     */
    public function getFileSize(): int
    {
        $stat = fstat($this->filePointer);
        if ($stat === false) {
            throw new RuntimeException("Get File size error");
        }

        return $stat['size'];
    }

    public static function createFromFilePath(string $filePath): FileDownloadInterface
    {
        if (!is_file($filePath)) {
            throw new InvalidArgumentException("File does not exist");
        } else if (!is_readable($filePath)) {
            throw new InvalidArgumentException("File to download is not readable");
        }

        return new static(fopen($filePath, "rb"), $filePath);
    }

    public static function createFromString(string $content): FileDownloadInterface
    {
        $file = tmpfile();
        fwrite($file, $content);

        return new static($file, '');
    }

    public static function createFromResource($fileResource): FileDownloadInterface
    {
        $meta_data = stream_get_meta_data($fileResource);
        $filename = $meta_data["uri"];

        return new static($fileResource, $filename);
    }


}

# -eof-
