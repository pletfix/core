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

        $encoding = mb_detect_encoding($message, "UTF-8, ISO-8859-1, cp1252");
        $isHtml   = strncasecmp($message, '<!DOCTYPE html', 14) === 0 || strncasecmp($message, '<html', 5) === 0;
        $text     = $isHtml ? html_entity_decode(strip_tags($message), ENT_QUOTES, $encoding) : $message;
        $images   = $isHtml && $embeddedImages ? $this->extractImages($message) : [];

        //$boundary = md5(uniqid(microtime(), true));
        $uid = uniqid();
        $boundary1 = "x----_=_NextPart_001_$uid";
        $boundary2 = "x----_=_NextPart_002_$uid";
        $boundary3 = "x----_=_NextPart_003_$uid";

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

        if (!$isHtml) {
            if (empty($attachments)) {

                // just a simple plain text mail

                $header .= "Content-Type: text/plain;\r\n\tcharset=\"$encoding\"\r\n";
                $header .= "Content-Transfer-Encoding: quoted-printable\r\n";
                $header .= "\r\n";

                // Content

                $content = "$text\r\n";
            }
            else {

                // plain text mail with attachments

                $header .= "Content-Type: multipart/mixed;\r\n\tboundary=\"$boundary1\"\r\n";
                $header .= "\r\n";

                // Content

                $content = "This is a multi-part message in MIME format.\r\n";
                $content .= "\r\n";
                $content .= "--$boundary1\r\n";
                $content .= "Content-Type: text/plain;\r\n\tcharset=\"$encoding\"\r\n";
                $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
                $content .= "\r\n";
                $content .= "$text\r\n";

                // Attachments

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
                    $content .= "--$boundary1\r\n";
                    $content .= "Content-Type: $type;\r\n\tname=\"$name\"\r\n";
                    $content .= "Content-Transfer-Encoding: base64\r\n";
                    $content .= "Content-Description: \"$name\"\r\n";
                    $content .= "Content-Disposition: attachment;\r\n\tfilename=\"$name\"\r\n";
                    $content .= "\r\n";
                    $content .= "$data";
                }

                $content .= "\r\n";
                $content .= "--$boundary1--";
            }
        }
        else { // is Html
            if (empty($images)) {
                if (empty($attachments)) {

                    // just a simple HTML mail (without embedded images and without attachments)

                    $header .= "Content-Type: multipart/alternative;\r\n\tboundary=\"$boundary1\"\r\n";
                    $header .= "\r\n";

                    // Content

                    $content = "This is a multi-part message in MIME format.\r\n";
                    $content .= "\r\n";
                    $content .= "--$boundary1\r\n";
                    $content .= "Content-Type: text/plain;\r\n\tcharset=\"$encoding\"\r\n";
                    $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
                    $content .= "\r\n";
                    $content .= "$text\r\n";
                    $content .= "\r\n";
                    $content .= "\r\n";
                    $content .= "--$boundary1\r\n";
                    $content .= "Content-Type: text/html;\r\n\tcharset=\"$encoding\"\r\n";
                    $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
                    $content .= "\r\n";
                    $content .= "$message\r\n";
                    $content .= "--$boundary1--";
                }
                else {

                    // HTML mail with attachments (but without embedded images)

                    $header .= "Content-Type: multipart/mixed;\r\n\tboundary=\"$boundary1\"\r\n";
                    $header .= "\r\n";

                    // Content

                    $content = "This is a multi-part message in MIME format.\r\n";
                    $content .= "\r\n";
                    $content .= "--$boundary1\r\n";
                    $content .= "Content-Type: multipart/alternative;\r\n\tboundary=\"$boundary2\"\r\n";
                    $content .= "\r\n";
                    $content .= "\r\n";
                    $content .= "--$boundary2\r\n";
                    $content .= "Content-Type: text/plain;\r\n\tcharset=\"$encoding\"\r\n";
                    $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
                    $content .= "\r\n";
                    $content .= "$text\r\n";
                    $content .= "\r\n";
                    $content .= "\r\n";
                    $content .= "--$boundary2\r\n";
                    $content .= "Content-Type: text/html;\r\n\tcharset=\"$encoding\"\r\n";
                    $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
                    $content .= "\r\n";
                    $content .= "$message\r\n";
                    $content .= "--$boundary2--\r\n";

                    // Attachments

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
                        $content .= "--$boundary1\r\n";
                        $content .= "Content-Type: $type;\r\n\tname=\"$name\"\r\n";
                        $content .= "Content-Transfer-Encoding: base64\r\n";
                        $content .= "Content-Description: \"$name\"\r\n";
                        $content .= "Content-Disposition: attachment;\r\n\tfilename=\"$name\"\r\n";
                        $content .= "\r\n";
                        $content .= "$data";
                    }

                    $content .= "\r\n";
                    $content .= "--$boundary1--";
                }
            }
            else {
                if (empty($attachments)) {

                    // HTML mail with embedded images (but without attachments)

                    $header .= "Content-Type: multipart/related;\r\n\tboundary=\"$boundary1\";\r\n\ttype=\"multipart/alternative\"\r\n";
                    $header .= "\r\n";

                    // Content

                    $content = "This is a multi-part message in MIME format.\r\n";
                    $content .= "\r\n";
                    $content .= "--$boundary1\r\n";
                    $content .= "Content-Type: multipart/alternative;\r\n\tboundary=\"$boundary2\"\r\n";
                    $content .= "\r\n";
                    $content .= "\r\n";
                    $content .= "--$boundary2\r\n";
                    $content .= "Content-Type: text/plain;\r\n\tcharset=\"$encoding\"\r\n";
                    $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
                    $content .= "\r\n";
                    $content .= "$text\r\n";
                    $content .= "\r\n";
                    $content .= "\r\n";
                    $content .= "--$boundary2\r\n";
                    $content .= "Content-Type: text/html;\r\n\tcharset=\"$encoding\"\r\n";
                    $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
                    $content .= "\r\n";
                    $content .= "$message\r\n";
                    $content .= "--$boundary2--\r\n";

                    // Add the embedded images.

                    if (!empty($images)) {
                        foreach ($images as $i => $image) {
                            $name = basename($image);
                            if (($data = @file_get_contents($image)) === false) {
                                throw new MailException('Image could bot be read: ' . $image);
                            }
                            $data = chunk_split(base64_encode($data));
                            $type = self::getMimeTypeOfImage($image);
                            $content .= "\r\n";
                            $content .= "--$boundary1\r\n";
                            $content .= "Content-Type: $type;\r\n\tname=\"$name\"\r\n";
                            $content .= "Content-Transfer-Encoding: base64\r\n";
                            $content .= "Content-ID: <img" . ($i + 1) . ">\r\n";
                            $content .= "Content-Description: \"$name\"\r\n";
                            $content .= "Content-Location: \"$name\"\r\n";
                            $content .= "\r\n";
                            $content .= "$data";
                        }
                    }

                    $content .= "\r\n";
                    $content .= "--$boundary1--";
                }
                else {

                    // HTML mail with embedded images and attachments

                    $header .= "Content-Type: multipart/mixed;\r\n\tboundary=\"$boundary1\"\r\n";
                    $header .= "\r\n";

                    // Content

                    $content = "This is a multi-part message in MIME format.\r\n";
                    $content .= "\r\n";
                    $content .= "--$boundary1\r\n";
                    $content .= "Content-Type: multipart/related;\r\n\tboundary=\"$boundary2\";\r\n\ttype=\"multipart/alternative\"\r\n";
                    $content .= "\r\n";
                    $content .= "\r\n";
                    $content .= "--$boundary2\r\n";
                    $content .= "Content-Type: multipart/alternative;\r\n\tboundary=\"$boundary3\"\r\n";
                    $content .= "\r\n";
                    $content .= "\r\n";
                    $content .= "--$boundary3\r\n";
                    $content .= "Content-Type: text/plain;\r\n\tcharset=\"$encoding\"\r\n";
                    $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
                    $content .= "\r\n";
                    $content .= "$text\r\n";
                    $content .= "\r\n";
                    $content .= "\r\n";
                    $content .= "--$boundary3\r\n";
                    $content .= "Content-Type: text/html;\r\n\tcharset=\"$encoding\"\r\n";
                    $content .= "Content-Transfer-Encoding: quoted-printable\r\n";
                    $content .= "\r\n";
                    $content .= "$message\r\n";
                    $content .= "--$boundary3--\r\n";

                    // Add the embedded images.

                    if (!empty($images)) {
                        foreach ($images as $i => $image) {
                            $name = basename($image);
                            if (($data = @file_get_contents($image)) === false) {
                                throw new MailException('Image could bot be read: ' . $image);
                            }
                            $data = chunk_split(base64_encode($data));
                            $type = self::getMimeTypeOfImage($image);
                            $content .= "\r\n";
                            $content .= "--$boundary2\r\n";
                            $content .= "Content-Type: $type;\r\n\tname=\"$name\"\r\n";
                            $content .= "Content-Transfer-Encoding: base64\r\n";
                            $content .= "Content-ID: <img" . ($i + 1) . ">\r\n";
                            $content .= "Content-Description: \"$name\"\r\n";
                            $content .= "Content-Location: \"$name\"\r\n";
                            $content .= "\r\n";
                            $content .= "$data";
                        }
                    }

                    $content .= "\r\n";
                    $content .= "--$boundary2--\r\n";

                    // Attachments

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
                        $content .= "--$boundary1\r\n";
                        $content .= "Content-Type: $type;\r\n\tname=\"$name\"\r\n";
                        $content .= "Content-Transfer-Encoding: base64\r\n";
                        $content .= "Content-Description: \"$name\"\r\n";
                        $content .= "Content-Disposition: attachment;\r\n\tfilename=\"$name\"\r\n";
                        $content .= "\r\n";
                        $content .= "$data";
                    }

                    $content .= "\r\n";
                    $content .= "--$boundary1--";
                }
            }
        }

        if (mail($to, $subject, $content, $header) === false) {
            throw new MailException('The mail was not accepted for delivery.');
        }
    }

    /**
     * Extract all images below the application and refer to the content ID.
     *
     * @param string &$message HTML message
     * @return array List of embedded images
     */
    private function extractImages(&$message)
    {
        $images = [];

        //$baseUrl    = rtrim(config('app.url'), '/');
        $pattern = ',"(([\./]?)+[^"\.\s]+\.(jpg|gif))",i';
        //$pattern    = '/(<img'.'\s+[^>]*)src="' . preg_quote($baseUrl) . '\/(\.*)"/,i';
        $message = preg_replace_callback($pattern, function($match) use (&$images) {
            $images[] = $match[1];
            return '"cid:img' . count($images) . '"';
        }, $message);

        return $images;
    }

    /**
     * Gets an array of the uploaded files witch are embedded in the content.
     *
     * Example of the return value: [
     *       0 => [
     *           'url'      => 'https://example.de/attachments/editor/picture1.png',
     *           'domain'   => 'https://example.de',
     *           'folder'   => 'editor',
     *           'filename' => 'picture1.png',
     *      ],
     *      1 => [
     *           'url'      => 'https://example.de/attachments/editor/picture2.png',
     *           'domain'   => 'https://example.de',
     *           'folder'   => 'editor',
     *           'filename' => 'picture2.png',
     *      ],
     * ]
     *
     * @param string $content Content to parse.
     * @param string|string[]|null $domains Files only from these domains will be listed. (optional)
     * @return array
     */
    protected static function findEmbeddedFiles($content, $domains = null)
    {
        if (!is_null($domains)) {
            if (is_array($domains)) {
                $domains = implode('|', array_map(function ($domain) { return preg_quote(rtrim($domain, '/'), '/'); }, $domains));
            }
            else {
                $domains = preg_quote(rtrim($domains, '/'), '/');
            }

        }
        else {
            $domains = 'https?\:\/\/[a-zA-Z0-9\-\._~\:@\/]+'; // URL-Format s. https://de.wikipedia.org/wiki/URL-Encoding
        }

        $invalidFileChar = ':*?\'"<>|\/\\\\';
        $baseRoute = trim('attachments', '/'); // als Variable einführen
        $pattern = '/(' . $domains  . ')\/' . preg_quote($baseRoute, '/') . '\/([^' . $invalidFileChar . ']*)\/([^' . $invalidFileChar . ']*)/';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        return array_map(function($match) {
            return [
                'url'      => $match[0],
                'domain'   => $match[1],
                'folder'   => $match[2],
                'filename' => $match[3],
            ];
        }, $matches);
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