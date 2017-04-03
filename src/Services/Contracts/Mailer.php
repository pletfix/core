<?php

namespace Core\Services\Contracts;

use Core\Exceptions\MailException;

interface Mailer
{
    /**
     * Sends an email.
     *
     * The formatting of the receiver/receivers must comply with RFC 2822. Some examples are:
     * - user@example.com
     * - user@example.com, anotheruser@example.com
     * - User <user@example.com>
     * - User <user@example.com>, Another User <anotheruser@example.com>
     * @see http://www.faqs.org/rfcs/rfc2822.html RFC 2822
     *
     * @param string $to Receiver, or receivers of the mail.
     * @param string $subject Subject of the email to be sent.
     * @param string $message Message to be sent.
     * @param array $attachments Attachments to be sent
     * @param string|null $cc Carbon Copy
     * @param string|null $bcc Blind Carbon Copy
     * @param string|null $reply Reply To
     * @param string|null $from Sender Address If not set, the default setting is used.
     * @param bool $embeddedImages If true the images are embedded into the mail.
     * @throws MailException
     */
    public function send($to, $subject, $message, $attachments = [], $cc = null, $bcc = null, $reply = null, $from = null, $embeddedImages = true);
}