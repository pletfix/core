<?php

namespace Core\Services;

use Core\Exceptions\MailException;
use Core\Services\Contracts\Mailer as MailerContract;
use Exception;

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
     * Subject of the message.
     *
     * @var string
     */
    private $subject = '';

    /**
     * HTML or plain text message.
     *
     * @var string
     */
    private $body = '';

    /**
     * The plain text message.
     *
     * @var string
     */
    private $altBody = '';

    /**
     * List of receivers.
     *
     * The formatting of the address must comply with RFC 2822. Some examples are:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @var array
     */
    private $to = [];

    /**
     * List of Carbon Copies
     *
     * The formatting of the address must comply with RFC 2822. Some examples are:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @var array
     */
    private $cc = [];

    /**
     * List of Blind Carbon Copies.
     *
     * The formatting of the address must comply with RFC 2822. Some examples are:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @var array
     */
    private $bcc = [];

    /**
     * List of Reply-to Addresses
     *
     * The formatting of the address must comply with RFC 2822. Some examples are:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @var string
     */
    private $replyTo = [];

    /**
     * Sender address
     *
     * The formatting of the address must comply with RFC 2822, e.g.:
     *      "user@example.com"
     *      "User <user@example.com>"
     *
     * @var string
     */
    private $from;

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
            'from'    => null,
            'replyTo' => [],
            'pretend' => false,
        ], config('mail', []));

        $this->from    = $config['from'];
        $this->replyTo = $config['replyTo'];
        $this->pretend = $config['pretend'];
    }

    /**
     * @inheritdoc
     */
    public function subject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @inheritdoc
     */
    public function body($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function altBody($text)
    {
        $this->altBody = $text;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAltBody()
    {
        return $this->altBody;
    }

    /**
     * @inheritdoc
     */
    public function view($name, array $variables = [])
    {
        $body = DI::getInstance()->get('view')->render($name, $variables);

        return $this->body($body);
    }

    /**
     * @inheritdoc
     */
    public function to($to, $name = null)
    {
        if ($this->findAddress($to, $this->to) === false) {
            $this->to[] = $name !== null ? "$name <$to>" : $to;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function removeTo($to)
    {
        if (($index = $this->findAddress($to, $this->to)) !== false) {
            unset($this->to[$index]);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clearTo()
    {
        $this->to = [];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @inheritdoc
     */
    public function cc($cc, $name = null)
    {
        if (is_array($cc)) {
            foreach ($cc as $address) {
                if ($this->findAddress($address, $this->cc) === false) {
                    $this->cc[] = $address;
                }
            }
        }
        else if ($this->findAddress($cc, $this->cc) === false) {
            $this->cc[] = $name !== null ? "$name <$cc>" : $cc;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function removeCC($cc)
    {
        foreach ((array)$cc as $addr) {
            if (($index = $this->findAddress($addr, $this->cc)) !== false) {
                unset($this->cc[$index]);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clearCC()
    {
        $this->cc = [];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCC()
    {
        return $this->cc;
    }

    /**
     * @inheritdoc
     */
    public function bcc($bcc, $name = null)
    {
        if (is_array($bcc)) {
            foreach ($bcc as $address) {
                if ($this->findAddress($address, $this->bcc) === false) {
                    $this->bcc[] = $address;
                }
            }
        }
        else if ($this->findAddress($bcc, $this->bcc) === false) {
            $this->bcc[] = $name !== null ? "$name <$bcc>" : $bcc;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function removeBCC($bcc)
    {
        foreach ((array)$bcc as $addr) {
            if (($index = $this->findAddress($addr, $this->bcc)) !== false) {
                unset($this->bcc[$index]);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clearBCC()
    {
        $this->bcc = [];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBCC()
    {
        return $this->bcc;
    }

    /**
     * @inheritdoc
     */
    public function replyTo($replyTo, $name = null)
    {
        if (is_array($replyTo)) {
            foreach ($replyTo as $address) {
                if ($this->findAddress($address, $this->replyTo) === false) {
                    $this->replyTo[] = $address;
                }
            }
        }
        else if ($this->findAddress($replyTo, $this->replyTo) === false) {
            $this->replyTo[] = $name !== null ? "$name <$replyTo>" : $replyTo;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function removeReplyTo($replyTo)
    {
        foreach ((array)$replyTo as $addr) {
            if (($index = $this->findAddress($addr, $this->replyTo)) !== false) {
                unset($this->replyTo[$index]);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clearReplyTo()
    {
        $this->replyTo = [];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * @inheritdoc
     */
    public function from($from, $name = null)
    {
        $this->from = $name !== null ? "$name <$from>" : $from;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function resetFrom()
    {
        $this->from = config('mail.from');

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function detach($file)
    {
        unset($this->attachments[$file]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clearAttachments()
    {
        $this->attachments = [];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function removeEmbeddedFile($file)
    {
        unset($this->cids[$file]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clearEmbeddedFiles()
    {
        $this->cids = [];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getEmbeddedFiles()
    {
        return $this->cids;
    }

    /**
     * @inheritdoc
     */
    public function send($to = null, $subject = null, $body = null)
    {
        if ($to === null) {
            $to = implode(', ', $this->to);
        }
        else if (is_array($to)) {
            $to = implode(', ', $to);
        }

        if (empty($to)) {
            throw new MailException('Receiver not specified.');
        }

        if ($subject === null) {
            $subject = $this->subject;
        }

        if ($body === null) {
            $body = $this->body;
        }

        // Header

        if (empty($this->from)) {
            throw new MailException('From Address is not set. Check the configuration in config/mail.php.');
        }
        $from = $this->from;
        $header = "From: $from\r\n";

        if (!empty($this->cc)) {
            $cc = implode(', ', $this->cc);
            $header .= "Cc: $cc\r\n";
        }

        if (!empty($this->bcc)) {
            $bcc = implode(', ', $this->bcc);
            $header .= "Bcc: $bcc\r\n";
        }

        if (!empty($this->replyTo)) {
            $replyTo = implode(', ', $this->replyTo);
            $header .= "Reply-To: $replyTo\r\n";
        }

        $header .= "MIME-Version: 1.0\r\n";

        // Content

        $encoding = mb_detect_encoding($body, "UTF-8, ISO-8859-1, cp1252");
        $isHtml   = strncasecmp($body, '<html', 5) === 0 || strncasecmp($body, '<!DOCTYPE html', 14) === 0;
        $text     = $isHtml ? ($this->altBody === null ? html_entity_decode(strip_tags($body), ENT_QUOTES, $encoding) : $this->altBody) : $body;

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
                        $this->renderHtml($content, $body, $encoding, $boundary1);
                    }
                    else { // HTML mail with attachments (but without embedded files)
                        $header .= "Content-Type: multipart/mixed;\r\n\tboundary=\"$boundary1\"\r\n";
                        $header .= "\r\n";
                        $this->renderTextAndHtml($content, $text, $body, $encoding, $boundary1);
                        $this->renderAttachments($content, $boundary1);
                    }
                }
                else {
                    if (empty($this->attachments)) { // HTML mail with embedded files (but without attachments)
                        $header .= "Content-Type: multipart/related;\r\n\tboundary=\"$boundary1\";\r\n\ttype=\"multipart/alternative\"\r\n";
                        $header .= "\r\n";
                        $this->renderTextAndHtml($content, $text, $body, $encoding, $boundary1);
                        $this->renderEmbeddedFiles($content, $boundary1);
                    }
                    else { // HTML mail with embedded files and attachments
                        $header .= "Content-Type: multipart/mixed;\r\n\tboundary=\"$boundary1\"\r\n";
                        $header .= "\r\n";
                        $this->renderTextAndHtmlWithEmbededFiles($content, $text, $body, $encoding, $boundary1);
                        $this->renderAttachments($content, $boundary1);
                    }
                }
            }
            $content .= "\r\n--$boundary1--";
        }

        // Send!

        if ($this->pretend) {
            DI::getInstance()->get('logger')->debug('mail', compact('to', 'subject', 'content', 'header'));
            return $this;
        }

        if (mail($to, $subject, $content, $header) === false) {
            throw new MailException('The mail was not accepted for delivery.');  // @codeCoverageIgnore
        }

        return $this;
    }

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
            if (!is_readable($image)) {
                throw new MailException('Image could bot be read: ' . $image);  // @codeCoverageIgnore
            }
            $type = self::getMimeTypeOfImage($image);
            $name = basename($image);
            $data = chunk_split(base64_encode(file_get_contents($image)));
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
            if (!is_readable($file)) {
                throw new MailException('File could not be read: ' . $file); // @codeCoverageIgnore
            }
            $data = chunk_split(base64_encode(file_get_contents($file)));
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
        if ($mimes === null) {
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
     * Get the address without the name.
     *
     * @param string $address e.g. "User <user@example.com>"
     * @return string e.g. "user@example.com"
     */
    private function justAddress($address)
    {
        if (($pos = strpos($address, '<')) === false) {
            return $address;
        }

        $n = strlen($address);
        if ($address[$n - 1] == '>') {
            $address = substr($address, $pos + 1, -1);
        }

        return $address;
    }

    /**
     * Get the index of the given address.
     *
     * @param string $needle Address with or without name
     * @param array $haystack List of Addresses (with or without name)
     * @return int|false Index of the address or FALSE if the address does not exist.
     */
    private function findAddress($needle, array $haystack)
    {
        $address = $this->justAddress($needle);
        foreach ($haystack as $i => $item) {
            if ($this->justAddress($item) == $address) {
                return $i;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getPretend()
    {
        return $this->pretend;
    }

    /**
     * @inheritdoc
     */
    public function setPretend($pretend)
    {
        $this->pretend = $pretend;

        return $this;
    }
}