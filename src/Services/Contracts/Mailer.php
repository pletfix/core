<?php

namespace Core\Services\Contracts;

use Core\Exceptions\MailException;

interface Mailer
{
    /**
     * Set the subject of the message.
     *
     * @param string $subject
     * @return $this
     */
    public function subject($subject);

    /**
     * Set the body of the message.
     *
     * @param string $body HTML or plain text message
     * @return $this
     */
    public function body($body);

    /**
     * Set the plain-text message body.
     *
     * It will be ignored if the body message is not an HTML message.
     * If altBody is null, it is build from the body via strip_tags().
     *
     * @param string $text Plain text message
     * @return $this
     */
    public function altBody($text);

    /**
     * Set the view to render the body of the message.
     *
     * @param string $name Name of the view
     * @param array $variables
     * @return $this
     */
    public function view($name, array $variables = []);

    /**
     * Add a receiver.
     *
     * The formatting of the address must comply with RFC 2822, e.g.:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @see http://www.faqs.org/rfcs/rfc2822.html RFC 2822
     *
     * @param string $to
     * @param string|null $name
     * @return $this
     */
    public function to($to, $name = null);

    /**
     * Remove a receiver.
     *
     * The formatting of the address must comply with RFC 2822, e.g.:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @param string $to
     * @return $this
     */
    public function removeTo($to);

    /**
     * Clear all receivers.
     *
     * @return $this
     */
    public function clearTo();

    /**
     * Add a Carbon Copy.
     *
     * The formatting of the address must comply with RFC 2822, e.g.:
     *      "user@example.com"
     *      "User <user@example.com>"
     * @see http://www.faqs.org/rfcs/rfc2822.html RFC 2822
     *
     * @param string|array $cc
     * @param string|null $name
     * @return $this
     */
    public function cc($cc, $name = null);

    /**
     * Remove a Carbon Copy.
     *
     * The formatting of the address must comply with RFC 2822, e.g.:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @param string|array $cc
     * @return $this
     */
    public function removeCC($cc);

    /**
     * Remove all Carbon Copies.
     *
     * @return $this
     */
    public function clearCC();

    /**
     * Add a Blind Carbon Copy.
     *
     * The formatting of the address must comply with RFC 2822, e.g.:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @param string|array $bcc
     * @param string|null $name
     * @return $this
     */
    public function bcc($bcc, $name = null);

    /**
     * Remove a Blind Carbon Copy.
     *
     * The formatting of the address must comply with RFC 2822, e.g.:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @param string|array $bcc
     * @return $this
     */
    public function removeBCC($bcc);

    /**
     * Remove all Blind Carbon Copies.
     *
     * @return $this
     */
    public function clearBCC();

    /**
     * Add a Reply-To address.
     *
     * The formatting of the address must comply with RFC 2822, e.g.:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @param string|array $replyTo
     * @param string|null $name
     * @return $this
     */
    public function replyTo($replyTo, $name = null);

    /**
     * Remove a Reply-To address.
     *
     * The formatting of the address must comply with RFC 2822, e.g.:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @param string|array $replyTo
     * @return $this
     */
    public function removeReplyTo($replyTo);

    /**
     * Remove all Reply-To adresses.
     *
     * @return $this
     */
    public function clearReplyTo();

    /**
     * Override the default sender address.
     *
     * The formatting of the address must comply with RFC 2822, e.g.:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @param string $from
     * @param string|null $name
     * @return $this
     */
    public function from($from, $name = null);

    /**
     * Reset the from address to the default.
     *
     * @return $this
     */
    public function resetFrom();

    /**
     * Attach a file.
     *
     * @param string $file Path of the file
     * @param string $name Display name
     * @return $this
     */
    public function attach($file, $name = null);

    /**
     * Attach in-memory data as an attachment.
     *
     * @param string $data The bytes to be attached.
     * @param string $name
     * @param string $mimeType MIME type
     * @return $this
     * @see https://www.sitepoint.com/web-foundations/mime-types-complete-list List of MIME Types
     */
    public function attachData($data, $name, $mimeType);

    /**
     * Remove an already attached entity.
     *
     * @param string $file Path of the file
     * @return $this
     */
    public function detach($file);

    /**
     * Remove all attachments.
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
     * Embed in-memory data in the message and get the CID.
     *
     * @param string $data The bytes to be embedded.
     * @param string $name
     * @param string $mimeType MIME type
     * @return string The source reference.
     * @see https://www.sitepoint.com/web-foundations/mime-types-complete-list List of MIME Types
     */
    public function embedData($data, $name, $mimeType);

    /**
     * Remove embedded file.
     *
     * @param string $file Path or URL of the file.
     * @return $this
     */
    public function removeEmbeddedFile($file);

    /**
     * Remove all embedded files.
     *
     * @return $this
     */
    public function clearEmbeddedFile();

    /**
     * Sends an email.
     *
     * The formatting of the receiver must comply with RFC 2822.
     *
     * @param string|array|null $to Receiver, or receivers of the mail, e.g. "user@example.com" or "User <user@example.com>".
     * @param string|null $subject Subject of the email to be sent.
     * @param string|null $body Message to be sent.
     * @throws MailException
     */
    public function send($to = null, $subject = null, $body = null);
}