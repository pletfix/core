<?php

namespace Core\Services\Contracts;

use Core\Exceptions\MailException;

interface Mailer
{
    /**
     * Sends an email.
     *
     * The formatting of the receiver, cc, bcc reply-to and from addresses must comply with RFC 2822.
     * Some examples are:
     *      "user@example.com"
     *      "user@example.com, anotheruser@example.com"
     *      "User <user@example.com>"
     *      "User <user@example.com>, Another User <anotheruser@example.com>"
     * @see http://www.faqs.org/rfcs/rfc2822.html RFC 2822
     *
     * @param string $to Receiver, or receivers of the mail.
     * @param string $subject Subject of the email to be sent.
     * @param string $message Message to be sent.
     * @param string|null $cc Carbon Copy
     * @param string|null $bcc Blind Carbon Copy
     * @param string|null $replyTo Reply To
     * @param string|null $from Sender Address If not set, the default setting is used.
     * @throws MailException
     */
    public function send($to, $subject, $message, $cc = null, $bcc = null, $replyTo = null, $from = null);

    /**
     * Attach a file.
     *
     * @param string $file Path of the file
     * @param string $name Display name
     * @return $this
     */
    public function attach($file, $name = null);

    /**
     * Remove an already attached entity.
     *
     * @param string $file Path of the file
     * @return $this
     */
    public function detach($file);

    /**
     * Clear all attachments.
     *
     * @return $this
     */
    public function clearAttachments();

    /**
     * Embed a file and get the source reference.
     *
     * @param string $file Path or URL of the file.
     * @return string The source reference.
     */
    public function embed($file);

    /**
     * Remove embedded file.
     *
     * @param string $file Path or URL of the file.
     * @return $this
     */
    public function removeEmbeddedFile($file);

    /**
     * Clear all embedded files.
     *
     * @return $this
     */
    public function clearEmbeddedFile();
}