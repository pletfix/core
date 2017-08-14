<?php

namespace Core\Services\Contracts;

use Core\Exceptions\UploadException;

interface UploadedFile
{
    /**
     * Get the original name of the uploaded file.
     *
     * @return string
     */
    public function originalName();

    /**
     * Get the file size in bytes.
     *
     * The method returns false, if the file was not uploaded successfully.
     *
     * @return int|false
     */
    public function size();

    /**
     * Get the last modification time of the uploaded file.
     *
     * The method returns false, if the file was not uploaded successfully.
     *
     * @return string|false The time the file was last modified, or false on failure.
     */
    public function modificationTime();

    /**
     * Get the MIME type of the uploaded file.
     *
     * The method returns false, if the file was not uploaded successfully or the MIME type is unknown.
     *
     * @return string|false
     */
    public function mimeType();

    /**
     * Guess the file extension based on the mime type.
     *
     * The method returns false, if the file was not uploaded successfully or the MIME type is unknown.
     *
     * @return string|false
     */
    public function guessExtension();

    /**
     * Get the unique 32 character hash value of the file.
     *
     * The method returns false, if the file was not uploaded successfully.
     *
     * @return string|false
     */
    public function hash();

    /**
     * Get the error code.
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     *
     * @return int
     */
    public function errorCode();

    /**
     * Get the error message.
     *
     * @return string
     */
    public function errorMessage();

    /**
     * Determine if the file was uploaded successfully via HTTP POST and is ready to move.
     *
     * Note, that the method returns false if the file has already been moved from the temporary directory.
     *
     * @return bool
     */
    public function isValid();

    /**
     * Move the given uploaded file from the temporary directory to the destination folder.
     *
     * The method returns the new full file path.
     *
     * @param string $directory The destination folder
     * @param string $name The new file name
     * @return string
     *
     * @throws UploadException if, for any reason, the file could not have been saved
     */
    public function move($directory, $name = null);
}
