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
     * Attachments.
     *
     * @var array
     */
    private $attachments = [];

    /**
     * Content-ID of embedded files
     *
     * @var array
     */
    private $cids = [];

    /**
     * Default Reply-to address or addresses
     *
     * The formatting of the address must comply with RFC 2822. Some examples are:
     *      "user@example.com"
     *      "user@example.com, anotheruser@example.com"
     *      "User <user@example.com>"
     *      "User <user@example.com>, Another User <anotheruser@example.com>"
     *
     * @var string
     */
    private $replyTo;

    /**
     * Default Sender address
     *
     * The formatting of the address must comply with RFC 2822, e.g.:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @var string
     */
    private $from;

    /**
     * Pretended mail
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
            'replyTo' => null,
            'from'    => null,
            'pretend' => false,
        ], config('mail'));

        $this->replyTo = $config['replyTo'];
        $this->from    = $config['from'];
        $this->pretend = $config['pretend'];
    }

    /**
     * Attach a file.
     *
     * @param string $file Path of the file
     * @param string $name Display name
     * @return $this
     */
    public function attach($file, $name = null)
    {
        if ($name === null) {
            $name = basename($file);
        }
        $this->attachments[$file] = $name;

        return $this;
    }

    /**
     * Remove an already attached file.
     *
     * @param string $file Path of the file
     * @return $this
     */
    public function detach($file)
    {
        unset($this->attachments[$file]);

        return $this;
    }

    /**
     * Clear all attachments.
     *
     * @return $this
     */
    public function clearAttachments()
    {
        $this->attachments = [];

        return $this;
    }

    /**
     * Embed a file and get the source reference.
     *
     * @param string $file Path or URL of the file.
     * @return string The source reference.
     */
    public function embed($file)
    {
        if (isset($this->cids[$file])) {
            $cid = $this->cids[$file];
        }
        else {
            $cid = uniqid('data_');
            $this->cids[$file] = $cid;
        }

        return 'cid:' . $cid;
    }

    /**
     * Remove embedded file.
     *
     * @param string $file Path or URL of the file.
     * @return $this
     */
    public function removeEmbeddedFile($file)
    {
        unset($this->cids[$file]);

        return $this;
    }

    /**
     * Clear all embedded files.
     *
     * @return $this
     */
    public function clearEmbeddedFile()
    {
        $this->cids = [];

        return $this;
    }

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
    public function send($to, $subject, $message, $cc = null, $bcc = null, $replyTo = null, $from = null)
    {
        if (empty($to)) {
            throw new MailException('Receiver not specified.');
        }

        if ($replyTo === null) {
            $replyTo = $this->replyTo;
        }

        if ($from === null) {
            $from = $this->from;
        }

        if (empty($this->from)) {
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
        if ($replyTo !== null) {
            $header .= "Reply-To: $replyTo\r\n";
        }
        $header .= "MIME-Version: 1.0\r\n";

        // Content

        $encoding = mb_detect_encoding($message, "UTF-8, ISO-8859-1, cp1252");
        $isHtml   = strncasecmp($message, '<html', 5) === 0 || strncasecmp($message, '<!DOCTYPE html', 14) === 0;
        $text     = $isHtml ? html_entity_decode(strip_tags($message), ENT_QUOTES, $encoding) : $message;

        if (!$isHtml && empty($this->attachments)) { // just a simple plain text mail...
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
                $this->renderPlainText($content, $text, $encoding, $boundary1);
                $this->renderAttachments($content, $boundary1);
            }
            else { // is HTML mail...
                if (empty($this->cids)) {
                    if (empty($this->attachments)) { // just a simple HTML mail (without embedded files and without attachments)
                        $header .= "Content-Type: multipart/alternative;\r\n\tboundary=\"$boundary1\"\r\n";
                        $header .= "\r\n";
                        $this->renderPlainText($content, $text, $encoding, $boundary1);
                        $this->renderHtml($content, $message, $encoding, $boundary1);
                    }
                    else { // HTML mail with attachments (but without embedded files)
                        $header .= "Content-Type: multipart/mixed;\r\n\tboundary=\"$boundary1\"\r\n";
                        $header .= "\r\n";
                        $this->renderTextAndHtml($content, $text, $message, $encoding, $boundary1);
                        $this->renderAttachments($content, $boundary1);
                    }
                }
                else {
                    if (empty($this->attachments)) { // HTML mail with embedded files (but without attachments)
                        $header .= "Content-Type: multipart/related;\r\n\tboundary=\"$boundary1\";\r\n\ttype=\"multipart/alternative\"\r\n";
                        $header .= "\r\n";
                        $this->renderTextAndHtml($content, $text, $message, $encoding, $boundary1);
                        $this->renderEmbeddedFiles($content, $boundary1);
                    }
                    else { // HTML mail with embedded files and attachments
                        $header .= "Content-Type: multipart/mixed;\r\n\tboundary=\"$boundary1\"\r\n";
                        $header .= "\r\n";
                        $this->renderTextAndHtmlWithEmbededFiles($content, $text, $message, $encoding, $boundary1);
                        $this->renderAttachments($content, $boundary1);
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

    ///////////////////////////////////////////////////////////////////
    // Render the body

    /**
     * Render embedded files into the given content.
     *
     * @param string &$content
     * @param string $boundary
     * @throws MailException
     */
    private function renderEmbeddedFiles(&$content, $boundary)
    {
        foreach ($this->cids as $image => $cid) {
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
            $content .= "Content-ID: <" . $cid . ">\r\n";
            $content .= "Content-Description: \"$name\"\r\n";
            $content .= "Content-Location: \"$name\"\r\n";
            $content .= "\r\n";
            $content .= "$data";
        }
    }

    /**
     * Render attachments into the given content.
     *
     * @param string &$content
     * @param string $boundary
     * @throws MailException
     */
    private function renderAttachments(&$content, $boundary)
    {
        foreach($this->attachments as $file => $name) {
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
     * Render plain text into the given content.
     *
     * @param string &$content
     * @param string $text
     * @param string $encoding
     * @param string $boundary
     */
    private function renderPlainText(&$content, $text, $encoding, $boundary)
    {
        $content .= "\r\n";
        $content .= "--$boundary\r\n";
        $content .= "Content-Type: text/plain;\r\n\tcharset=\"$encoding\"\r\n";
        $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $content .= "\r\n";
        $content .= "$text\r\n";
    }

    /**
     * Render HTML into the given content.
     *
     * @param string &$content
     * @param string $html
     * @param string $encoding
     * @param string $boundary
     */
    private function renderHtml(&$content, $html, $encoding, $boundary)
    {
        $content .= "\r\n";
        $content .= "--$boundary\r\n";
        $content .= "Content-Type: text/html;\r\n\tcharset=\"$encoding\"\r\n";
        $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $content .= "\r\n";
        $content .= "$html\r\n";
    }

    /**
     * Render text and html into the given content.
     *
     * @param string &$content
     * @param string $text
     * @param string $html
     * @param string $encoding
     * @param string $boundary
     */
    private function renderTextAndHtml(&$content, $text, $html, $encoding, $boundary)
    {
        $uid = uniqid();
        $boundary2 = "x----_=_NextPart_003_$uid";
        $content .= "\r\n--$boundary\r\n";
        $content .= "Content-Type: multipart/alternative;\r\n\tboundary=\"$boundary2\"\r\n";
        $this->renderPlainText($content, $text, $encoding, $boundary2);
        $this->renderHtml($content, $html, $encoding, $boundary2);
        $content .= "\r\n--$boundary2--\r\n";
    }

    /**
     * Render text and html with embedded files into the given content.
     *
     * @param string &$content
     * @param string $text
     * @param string $html
     * @param string $encoding
     * @param string $boundary
     */
    private function renderTextAndHtmlWithEmbededFiles(&$content, $text, $html, $encoding, $boundary)
    {
        $uid = uniqid();
        $boundary2 = "x----_=_NextPart_002_$uid";
        $content .= "\r\n--$boundary\r\n";
        $content .= "Content-Type: multipart/related;\r\n\tboundary=\"$boundary2\";\r\n\ttype=\"multipart/alternative\"\r\n";
        $this->renderTextAndHtml($content, $text, $html, $encoding, $boundary2);
        $this->renderEmbeddedFiles($content, $boundary2);
        $content .= "\r\n--$boundary2--\r\n";
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

    /**
     * Extract all images to be embedded and refer to the cid.
     *
     * todo entfernen!
     *
     * @param string &$message HTML message
     * @return array List of embedded images
     */
    /*
    public static function extractImages(&$message)
    {
        $images = [];
        $pattern = '/<img'.'\s+[^>]*src=["|\']([^"|^\']*)["|\'][^>]*\/?>/i';
        $message = preg_replace_callback($pattern, function($match) use (&$images) {
            $images[] = $match[1];
            return '"cid:img' . count($images) . '"';
        }, $message);

        return $images;
    }
    */
}