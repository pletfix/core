<?php

namespace Core\Tests\Services;

use Core\Exceptions\UploadException;
use Core\Services\Contracts\Request;
use Core\Services\DI;
use Core\Services\UploadedFile;
use Core\Testing\TestCase;

require_once __DIR__ . '/../_data/fakes/is_uploaded_file.php.fake';
require_once __DIR__ . '/../_data/fakes/move_uploaded_file.php.fake';

class UploadedFileTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        @mkdir(storage_path('~test'));
        copy(__DIR__ . '/../_data/images/logo_50x50.png', storage_path('~test/tempfile'));
        touch(storage_path('~test/tempfile'), 1502756455);
    }

    public static function tearDownAfterClass()
    {
        @unlink(storage_path('~test/tempfile'));
        @rmdir(storage_path('~test'));
    }

    public function testAttributes()
    {
        $file = new \Core\Services\UploadedFile(storage_path('~test/tempfile'), 'logo.png', UPLOAD_ERR_OK);
        $this->assertSame('logo.png', $file->originalName());
        $this->assertSame(1871, $file->size());
        $this->assertSame(1502756455, $file->modificationTime());
        $this->assertSame('image/png', $file->mimeType());
        $this->assertSame('png', $file->guessExtension());
        $this->assertSame('1940e9131ce10861e448e076d74241c2', $file->hash());
        $this->assertTrue($file->isValid());
        $this->assertSame(UPLOAD_ERR_OK, $file->errorCode());
        $this->assertEmpty($file->errorMessage());
    }

    public function testNotUploadedViaHTTP()
    {
        $file = new \Core\Services\UploadedFile(storage_path('~test/invalid'), 'logo.png', UPLOAD_ERR_OK); // '~test/invalid' -> fake that is not uploaded via HTTP
        $this->assertFalse($file->isValid());
        $this->assertSame(UPLOAD_ERR_OK, $file->errorCode());
        $this->assertSame('The file "logo.png" was not uploaded via HTTP POST or has already been moved.', $file->errorMessage());
    }

    public function testFileIsTooBig()
    {
        $file = new \Core\Services\UploadedFile(storage_path('~test/tempfile'), 'logo.png', UPLOAD_ERR_INI_SIZE); // UPLOAD_ERR_INI_SIZE -> the file is to big
        $this->assertFalse($file->isValid());
        $this->assertSame(UPLOAD_ERR_INI_SIZE, $file->errorCode());
        $this->assertStringStartsWith('The file "logo.png" exceeds your upload_max_filesize ini directive', $file->errorMessage());
    }

    public function testMoveInValid()
    {
        $file = new \Core\Services\UploadedFile('', '', UPLOAD_ERR_NO_FILE);
        $this->expectException(UploadException::class);
        $file->move(storage_path('~test'));
    }

    public function testMoveNotWriteable()
    {
        $file = new \Core\Services\UploadedFile(storage_path('~test/tempfile'), 'logo.png', UPLOAD_ERR_OK);
        $this->expectException(UploadException::class);
        $file->move('/bin/tempfile'); // bin is not writeable
    }

    public function testMoveFailed()
    {
        $file = new \Core\Services\UploadedFile(storage_path('~test/tempfile'), 'wrong.png', UPLOAD_ERR_OK);
        $this->expectException(UploadException::class);
        $file->move(storage_path('~test'), 'wrong.png'); // 'wrong.png' -> fake that the move operation failed
    }

    public function testMoveSuccessfully()
    {
        $file = new \Core\Services\UploadedFile(storage_path('~test/tempfile'), 'logo.png', UPLOAD_ERR_OK);
        $this->assertSame(storage_path('~test/logo.png'), $file->move(storage_path('~test')));
    }
}
