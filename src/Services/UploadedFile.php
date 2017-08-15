<?php

namespace Core\Services;

use Core\Exceptions\UploadException;
use Core\Services\Contracts\UploadedFile as UploadedFileContract;

/**
 * The Request class represents an HTTP request.
 *
 * errorMessage(), move() and getMaxFileSize() are based on Symfony's UploadedFile.
 *
 * @see https://github.com/symfony/http-foundation/blob/3.2/File/UploadedFile.php Symfony's UploadedFile on GitHub
 */
class UploadedFile implements UploadedFileContract
{
    /**
     * The full temporary path to the file or the path after moving the uploaded file
     *
     * @var string
     */
    private $path;

    /**
     * The original file name
     *
     * @var string
     */
    private $name;

    /**
     * The UPLOAD_ERR_XXX constant.
     *
     * @var string
     */
    private $error;

    /**
     * Create a new UploadedFile instance.
     *
     * @param string $path The full temporary path to the file
     * @param string $name The original file name
     * @param int $error The UPLOAD_ERR_XXX constant
     */
    public function __construct($path, $name, $error)
    {
        $this->path  = $path;
        $this->name  = basename(str_replace('\\', '/', $name)); // basename() may prevent filesystem traversal attacks, see http://php.net/manual/en/function.move-uploaded-file.php
        $this->error = $error;
    }

    /**
     * @inheritdoc
     */
    public function originalName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function size()
    {
        return filesize($this->path);
    }

    /**
     * @inheritdoc
     */
    public function modificationTime()
    {
        return filemtime($this->path);
    }

    /**
     * @inheritdoc
     */
    public function mimeType()
    {
        return mime_type($this->path);
    }

    /**
     * @inheritdoc
     */
    public function guessExtension()
    {
        $mime = mime_type($this->path);

        return $mime ? guess_file_extension($mime) : false;
    }

    /**
     * @inheritdoc
     */
    public function hash()
    {
        return @md5_file($this->path);
    }

    /**
     * @inheritdoc
     */
    public function errorCode()
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function errorMessage()
    {
        if ($this->error === UPLOAD_ERR_OK && !is_uploaded_file($this->path)) {
            return sprintf('The file "%s" was not uploaded via HTTP POST or has already been moved.', $this->name);
        }

        static $messages = [
            UPLOAD_ERR_OK         => '',
            UPLOAD_ERR_INI_SIZE   => 'The file "%s" exceeds your upload_max_filesize ini directive (limit is %d KiB).',
            UPLOAD_ERR_FORM_SIZE  => 'The file "%s" exceeds the upload limit defined in your form.',
            UPLOAD_ERR_PARTIAL    => 'The file "%s" was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
            UPLOAD_ERR_CANT_WRITE => 'The file "%s" could not be written on disk.',
            UPLOAD_ERR_EXTENSION  => 'File upload was stopped by a PHP extension.',
        ];

        $maxSize = $this->error === UPLOAD_ERR_INI_SIZE ? $this->getMaxFileSize() / 1024 : 0;
        $message = isset($messages[$this->error]) ? $messages[$this->error] : 'The file "%s" was not uploaded due to an unknown error.';

        return sprintf($message, $this->name, $maxSize);
    }

    /**
     * @inheritdoc
     */
    public function isValid()
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->path);
    }

    /**
     * @inheritdoc
     */
    public function move($directory, $name = null)
    {
        // validate the error code
        if (!$this->isValid()) {
            throw new UploadException($this->errorMessage());
        }

        // validate the upload directory (is writable?)
        if (!is_writable($directory)) {
            throw new UploadException(sprintf('The directory "%s" is not writable.', $directory));
        }

        // save the file
        $target = realpath($directory) . DIRECTORY_SEPARATOR . ($name ?: $this->name);
        if (!@move_uploaded_file($this->path, $target)) {
            $error = error_get_last();
            $message = $error !== null ? strip_tags($error['message']) : 'The file could not be moved to the upload directory.';
            throw new UploadException($message);
        }

        // change the file permissions
        @chmod($target, 0666 & ~umask());

        $this->path = $target;

        return $target;
    }

    /**
     * Returns the maximum size of an uploaded file as configured in php.ini.
     *
     * @return int The maximum size of an uploaded file in bytes
     * @codeCoverageIgnore
     */
    private function getMaxFileSize()
    {
        $iniMax = strtolower(ini_get('upload_max_filesize'));

        if ($iniMax === '') {
            return PHP_INT_MAX;
        }

        $max = ltrim($iniMax, '+');
        if (strpos($max, '0x') === 0) {
            $max = intval($max, 16);
        }
        elseif (strpos($max, '0') === 0) {
            $max = intval($max, 8);
        }
        else {
            $max = (int)$max;
        }

        switch (substr($iniMax, -1)) {
            case 't': $max *= 1024^4; break;
            case 'g': $max *= 1024^3; break;
            case 'm': $max *= 1024^2; break;
            case 'k': $max *= 1024;   break;
        }

        return $max;
    }
}