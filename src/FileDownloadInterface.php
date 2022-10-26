<?php

namespace Arris\Toolkit;

use InvalidArgumentException;
use RuntimeException;

interface FileDownloadInterface
{
    /**
     * Constructs a new file download
     *
     * @param resource $filePointer
     *
     * @throws InvalidArgumentException
     */
    public function __construct($filePointer, string $filePath);

    /**
     * Sends the download to the browser
     *
     * @param string $filename
     * @param bool $forceDownload
     *
     * @throws RuntimeException would thrown if the headers are already sent
     */
    public function sendDownload(string $filename = '', bool $forceDownload = true);

    /**
     * Returns the file size of the file
     *
     * @return int
     */
    public function getFileSize(): int;

    /**
     * Creates a new file download from a file path
     *
     * @param string $filePath
     *
     * @return FileDownloadInterface
     * @throws InvalidArgumentException is thrown, if the given file does not exist or is not readable
     *
     */
    public static function createFromFilePath(string $filePath): FileDownloadInterface;

    /**
     * Creates a new file download helper with a given content
     *
     * @static
     *
     * @param string $content the file content
     *
     * @return FileDownloadInterface
     */
    public static function createFromString(string $content): FileDownloadInterface;
}