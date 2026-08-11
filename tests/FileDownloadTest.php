<?php

namespace Arris\Toolkit\Tests;

use Arris\Toolkit\FileDownload;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FileDownloadTest extends TestCase
{
    public function testConstructorThrowsOnNonResource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FileDownload('not a resource', 'file.txt');
    }

    public function testCreateFromFilePathThrowsWhenFileDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FileDownload::createFromFilePath('/tmp/definitely-not-exists-file.txt');
    }

    public function testCreateFromFilePathThrowsWhenFileIsNotReadable(): void
    {
        $file = \tempnam(\sys_get_temp_dir(), 'fd_');
        \chmod($file, 0000);

        if (\is_readable($file)) {
            \unlink($file);
            $this->markTestSkipped('cannot create an unreadable file (running as root?)');
        }

        try {
            $this->expectException(InvalidArgumentException::class);
            FileDownload::createFromFilePath($file);
        } finally {
            \chmod($file, 0644);
            \unlink($file);
        }
    }

    public function testCreateFromFilePathReturnsDownload(): void
    {
        $file = \tempnam(\sys_get_temp_dir(), 'fd_');
        \file_put_contents($file, 'hello');

        $download = FileDownload::createFromFilePath($file);
        $this->assertInstanceOf(FileDownload::class, $download);
        $this->assertSame(5, $download->getFileSize());

        \unlink($file);
    }

    public function testCreateFromStringReturnsDownload(): void
    {
        $download = FileDownload::createFromString('hello world');
        $this->assertInstanceOf(FileDownload::class, $download);
        $this->assertSame(11, $download->getFileSize());
    }

    public function testCreateFromResourceReturnsDownload(): void
    {
        $file = \tempnam(\sys_get_temp_dir(), 'fd_');
        \file_put_contents($file, 'content');
        $pointer = \fopen($file, 'rb');

        $download = FileDownload::createFromResource($pointer);
        $this->assertInstanceOf(FileDownload::class, $download);
        $this->assertSame(7, $download->getFileSize());
        $this->assertTrue(\is_resource($pointer));

        \unlink($file);
    }

    public function testSendDownloadOutputsFileContent(): void
    {
        if (\headers_sent()) {
            $this->markTestSkipped('headers are already sent by the test runner');
        }

        $download = FileDownload::createFromString('download me');

        \ob_start();
        $download->sendDownload('file.txt', true, false);
        $output = \ob_get_clean();

        $this->assertSame('download me', $output);
    }

    public function testSendDownloadThrowsWhenHeadersAlreadySent(): void
    {
        \ob_start();
        echo 'x';
        \ob_end_clean();

        if (!\headers_sent()) {
            $this->markTestSkipped('cannot simulate sent headers in this SAPI');
        }

        $download = FileDownload::createFromString('x');
        $this->expectException(RuntimeException::class);
        $download->sendDownload('file.txt', true, false);
    }

    public function testDestructorClosesFilePointer(): void
    {
        $file = \tempnam(\sys_get_temp_dir(), 'fd_');
        \file_put_contents($file, 'content');
        $pointer = \fopen($file, 'rb');

        $download = FileDownload::createFromResource($pointer);
        $this->assertTrue(\is_resource($pointer));

        unset($download);
        $this->assertFalse(\is_resource($pointer));

        \unlink($file);
    }
}
