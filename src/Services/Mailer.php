<?php

namespace Core\Services;

use Core\Exceptions\MailException;
use Core\Services\Contracts\Mailer as MailerContract;

/**
 * Mailer
 *
 * @see http://www.decocode.de/?177
 * @see https://www.drweb.de/magazin/aufbau-von-mime-mails-2/
 * @see https://www.php-einfach.de/experte/php-codebeispiele/emails-mit-anhang-versenden/
 */
class Mailer implements MailerContract
{
    /**
     * From Address
     *
     * @var string
     */
    private $from;

    /**
     * Pretended Mail
     *
     * @var string
     */
    private $pretend;

    /**
     * Create a new Mailer instance.
     */
    public function __construct()
    {
        $config = array_merge([
            'from'    => null,
            'pretend' => false,
        ], config('mail'));

        $this->from    = $config['from'];
        $this->pretend = $config['pretend'];
    }

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
    public function send($to, $subject, $message, $attachments = [], $cc = null, $bcc = null, $reply = null, $from = null, $embeddedImages = true)
    {
        if (empty($to)) {
            throw new MailException('Receiver not specified.');
        }

        if ($from === null) {
            $from = $this->from;
        }

        if (empty($from)) {
            throw new MailException('From Address is not set. Check the configuration in config/mail.php.');
        }

        // Header

        $header = "From: $from\r\n";
        if ($cc !== null) {
            $header .= "Cc: $cc\r\n";
        }
        if ($bcc !== null) {
            $header .= "Bcc: $bcc\r\n";
        }
        if ($reply !== null) {
            $header .= "Reply-To: $reply\r\n";
        }
        $header .= "MIME-Version: 1.0\r\n";

        // Content

        $encoding = mb_detect_encoding($message, "UTF-8, ISO-8859-1, cp1252");
        $isHtml   = strncasecmp($message, '<!DOCTYPE html', 14) === 0 || strncasecmp($message, '<html', 5) === 0;
        $text     = $isHtml ? html_entity_decode(strip_tags($message), ENT_QUOTES, $encoding) : $message;
        $images   = $isHtml && $embeddedImages ? $this->extractImages($message) : [];

        if (!$isHtml && empty($attachments)) { // just a simple plain text mail...
            $header .= "Content-Type: text/plain;\r\n\tcharset=\"$encoding\"\r\n";
            $header .= "Content-Transfer-Encoding: quoted-printable\r\n";
            $header .= "\r\n";
            $content = "$text\r\n";
        }
        else { // multi-part message...
            $content = "This is a multi-part message in MIME format.\r\n";
            $uid = uniqid();
            $boundary1 = "x----_=_NextPart_001_$uid";
            if (!$isHtml) { // plain text mail with attachments...
                $header .= "Content-Type: multipart/mixed;\r\n\tboundary=\"$boundary1\"\r\n";
                $header .= "\r\n";
                $this->embedPlainText($content, $text, $encoding, $boundary1);
                $this->embedAttachments($content, $attachments, $boundary1);
            }
            else { // is HTML mail...
                if (empty($images)) {
                    if (empty($attachments)) { // just a simple HTML mail (without embedded images and without attachments)
                        $header .= "Content-Type: multipart/alternative;\r\n\tboundary=\"$boundary1\"\r\n";
                        $header .= "\r\n";
                        $this->embedPlainText($content, $text, $encoding, $boundary1);
                        $this->embedHtml($content, $message, $encoding, $boundary1);
                    }
                    else { // HTML mail with attachments (but without embedded images)
                        $header .= "Content-Type: multipart/mixed;\r\n\tboundary=\"$boundary1\"\r\n";
                        $header .= "\r\n";
                        $this->embedTextAndHtml($content, $text, $message, $encoding, $boundary1);
                        $this->embedAttachments($content, $attachments, $boundary1);
                    }
                }
                else {
                    if (empty($attachments)) { // HTML mail with embedded images (but without attachments)
                        $header .= "Content-Type: multipart/related;\r\n\tboundary=\"$boundary1\";\r\n\ttype=\"multipart/alternative\"\r\n";
                        $header .= "\r\n";
                        $this->embedTextAndHtml($content, $text, $message, $encoding, $boundary1);
                        $this->embedImages($content, $images, $boundary1);
                    }
                    else { // HTML mail with embedded images and attachments
                        $header .= "Content-Type: multipart/mixed;\r\n\tboundary=\"$boundary1\"\r\n";
                        $header .= "\r\n";
                        $this->embedTextAndHtmlWithImages($content, $text, $message, $encoding, $images, $boundary1);
                        $this->embedAttachments($content, $attachments, $boundary1);
                    }
                }
            }
            $content .= "\r\n--$boundary1--";
        }

        // Send!

        if ($this->pretend) {
            DI::getInstance()->get('logger')->debug('mail', compact('to', 'subject', 'content', 'header'));
            return;
        }

        if (mail($to, $subject, $content, $header) === false) {
            throw new MailException('The mail was not accepted for delivery.');
        }
    }

    /**
     * Embed images into the given content.
     *
     * @param string &$content
     * @param array $images
     * @param string $boundary
     * @throws MailException
     */
    private function embedImages(&$content, $images, $boundary)
    {
        foreach ($images as $i => $image) {
            $name = basename($image);
            if (($data = @file_get_contents($image)) === false) {
                throw new MailException('Image could bot be read: ' . $image);
            }
            $data = chunk_split(base64_encode($data));
            $type = self::getMimeTypeOfImage($image);
            $content .= "\r\n";
            $content .= "--$boundary\r\n";
            $content .= "Content-Type: $type;\r\n\tname=\"$name\"\r\n";
            $content .= "Content-Transfer-Encoding: base64\r\n";
            $content .= "Content-ID: <img" . ($i + 1) . ">\r\n";
            $content .= "Content-Description: \"$name\"\r\n";
            $content .= "Content-Location: \"$name\"\r\n";
            $content .= "\r\n";
            $content .= "$data";
        }
    }

    /**
     * Embed attachments into the given content.
     *
     * @param string &$content
     * @param array $attachments
     * @param string $boundary
     * @throws MailException
     */
    private function embedAttachments(&$content, $attachments, $boundary)
    {
        foreach($attachments as $name => $file) {
            if (is_int($name)) {
                $name = basename($file);
            }
            if (($data = @file_get_contents($file)) === false) {
                throw new MailException('File could bot be read: ' . $file);
            }
            $data = chunk_split(base64_encode($data));
            $type = mime_content_type($file);
            $content .= "\r\n";
            $content .= "--$boundary\r\n";
            $content .= "Content-Type: $type;\r\n\tname=\"$name\"\r\n";
            $content .= "Content-Transfer-Encoding: base64\r\n";
            $content .= "Content-Description: \"$name\"\r\n";
            $content .= "Content-Disposition: attachment;\r\n\tfilename=\"$name\"\r\n";
            $content .= "\r\n";
            $content .= "$data";
        }
    }

    /**
     * Embed plain text into the given content.
     *
     * @param string &$content
     * @param string $text
     * @param string $encoding
     * @param string $boundary
     */
    private function embedPlainText(&$content, $text, $encoding, $boundary)
    {
        $content .= "\r\n";
        $content .= "--$boundary\r\n";
        $content .= "Content-Type: text/plain;\r\n\tcharset=\"$encoding\"\r\n";
        $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $content .= "\r\n";
        $content .= "$text\r\n";
    }

    /**
     * Embed HTML into the given content.
     *
     * @param string &$content
     * @param string $html
     * @param string $encoding
     * @param string $boundary
     */
    private function embedHtml(&$content, $html, $encoding, $boundary)
    {
        $content .= "\r\n";
        $content .= "--$boundary\r\n";
        $content .= "Content-Type: text/html;\r\n\tcharset=\"$encoding\"\r\n";
        $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $content .= "\r\n";
        $content .= "$html\r\n";
    }

    /**
     * Embed text and html into the given content.
     *
     * @param string &$content
     * @param string $text
     * @param string $html
     * @param string $encoding
     * @param string $boundary
     */
    private function embedTextAndHtml(&$content, $text, $html, $encoding, $boundary)
    {
        $uid = uniqid();
        $boundary2 = "x----_=_NextPart_003_$uid";
        $content .= "\r\n--$boundary\r\n";
        $content .= "Content-Type: multipart/alternative;\r\n\tboundary=\"$boundary2\"\r\n";
        $this->embedPlainText($content, $text, $encoding, $boundary2);
        $this->embedHtml($content, $html, $encoding, $boundary2);
        $content .= "\r\n--$boundary2--\r\n";
    }

    /**
     * Embed text and html into the given content.
     *
     * @param string &$content
     * @param string $text
     * @param string $html
     * @param string $encoding
     * @param string $images
     * @param string $boundary
     */
    private function embedTextAndHtmlWithImages(&$content, $text, $html, $encoding, $images, $boundary)
    {
        $uid = uniqid();
        $boundary2 = "x----_=_NextPart_002_$uid";
        $content .= "\r\n--$boundary\r\n";
        $content .= "Content-Type: multipart/related;\r\n\tboundary=\"$boundary2\";\r\n\ttype=\"multipart/alternative\"\r\n";
        $this->embedTextAndHtml($content, $text, $html, $encoding, $boundary2);
        $this->embedImages($content, $images, $boundary2);
        $content .= "\r\n--$boundary2--\r\n";
    }

    /**
     * Extract all images to be embedded and refer to the cid.
     *
     * @param string &$message HTML message
     * @return array List of embedded images
     */
    private function extractImages(&$message)
    {
        $images = [];

        $pattern = '/<img'.'\s+[^>]*src=["|\']([^"|^\']*)["|\'][^>]*\/?>/i';
        $message = preg_replace_callback($pattern, function($match) use (&$images) {
            $images[] = $match[1];
            return '"cid:img' . count($images) . '"';
        }, $message);

        return $images;
    }

    /**
     * Gets the MIME Type of the given image.
     *
     * The file must be exists.
     *
     * @param string $image
     * @return string
     */
    private static function getMimeTypeOfImage($image)
    {
        static $mimes;
        if (!isset($mimes)) {
            $mimes = [
                'cod'  => 'image/cis-cod',
                'ras'  => 'image/cmu-raster',
                'fif'  => 'image/fif',
                'gif'  => 'image/gif',
                'ief'  => 'image/ief',
                'jpeg' => 'image/jpeg',
                'jpg'  => 'image/jpeg',
                'jpe'  => 'image/jpeg',
                'png'  => 'image/png',
                'tiff' => 'image/tiff',
                'tif'  => 'image/tiff',
                'mcf'  => 'image/vasa',
                'wbmp' => 'image/vnd.wap.wbmp',
                'fh4'  => 'image/x-freehand',
                'fh5'  => 'image/x-freehand',
                'fhc'  => 'image/x-freehand',
                'ico'  => 'image/x-icon',
                'pnm'  => 'image/x-portable-anymap',
                'pbm'  => 'image/x-portable-bitmap',
                'pgm'  => 'image/x-portable-graymap',
                'ppm'  => 'image/x-portable-pixmap',
                'rgb'  => 'image/x-rgb',
                'wxd'  => 'image/x-windowdump',
                'xbm'  => 'image/x-xbitmap',
                'xpm'  => 'image/x-xpixmap',
            ];
        }

        $ext = pathinfo($image, PATHINFO_EXTENSION);
        $type = isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream'; // RFC 2046 states in section 4.5.1: The "octet-stream" subtype is used to indicate that a body contains arbitrary binary data.

        return $type;
    }
}