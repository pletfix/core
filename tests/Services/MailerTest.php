<?php

namespace Core\Tests\Services;

use Core\Exceptions\MailException;
use Core\Services\DI;
use Core\Services\Logger;
use Core\Services\Mailer;
use Core\Services\View;
use Core\Testing\TestCase;
use Exception;

require_once 'fakes/mail.php.fake'; // todo oder stub? Begriffsdefinition klären!

class MailerTest extends TestCase
{
    /**
     * @var Mailer
     */
    private $mailer;

    public static function setUpBeforeClass()
    {
        $config = DI::getInstance()->get('config');

        $config->set('mail', [
            'replyTo' => [],
            'from'    => 'mail@example.com',
            'pretend' => false,
        ]);

        $config->set('logger', [
            'type' => 'single',
            'level' => 'debug',
            'max_files' => 5,
            'app_file' => '~test.log',
            'cli_file' => '~test.log',
            'permission' => 0664,
        ]);
        DI::getInstance()->set('logger', Logger::class, true);

        file_put_contents(storage_path('temp/~foo.txt'), 'foo');
        file_put_contents(storage_path('temp/~foo.png'), 'foo');
    }

    public static function tearDownAfterClass()
    {
        @unlink(storage_path('temp/~foo.txt'));
        @unlink(storage_path('temp/~foo.png'));
        @unlink(storage_path('logs/~test.log'));
    }

    protected function setUp()
    {
        $this->mailer = new Mailer;
    }

    public function testSubject()
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer->subject('My Subject!'));
        $this->assertSame('My Subject!', $this->mailer->getSubject());
    }

    public function testBody()
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer->body('My Body!'));
        $this->assertSame('My Body!', $this->mailer->getBody());
    }

    public function testAltBody()
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer->altBody('my alternate body'));
        $this->assertSame('my alternate body', $this->mailer->getAltBody());
    }

    public function testView()
    {
        $view = $this->getMockBuilder(View::class)->setMethods(['render'])->getMock();
        $view->expects($this->once())->method('render')->with('foo', ['a' => 'A', 'b' => 'B'])->willReturn('Hello World!');
        DI::getInstance()->set('view', $view, true);
        $body = $this->mailer->view('foo', ['a' => 'A', 'b' => 'B']);
        $this->assertInstanceOf(Mailer::class, $body);
        $this->assertSame('Hello World!', $this->mailer->getBody());
    }

    public function testTo()
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer->to('user1@example.com'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->to('user1@example.com'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->to('User2 <user2@example.com>'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->to('User2 <user2@example.com>'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->to('user3@example.com', 'User3'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->to('user3@example.com', 'User3'));
        $this->assertSame(['user1@example.com', 'User2 <user2@example.com>', 'User3 <user3@example.com>'], $this->mailer->getTo());
        $this->assertInstanceOf(Mailer::class, $this->mailer->removeTo('user2@example.com'));
        $this->assertSame([0 => 'user1@example.com', 2 => 'User3 <user3@example.com>'], $this->mailer->getTo());
        $this->assertInstanceOf(Mailer::class, $this->mailer->clearTo());
        $this->assertEmpty($this->mailer->getTo());
    }

    public function testCC()
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer->cc('user1@example.com'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->cc('user1@example.com'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->cc('User2 <user2@example.com>'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->cc('User2 <user2@example.com>'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->cc('user3@example.com', 'User3'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->cc('user3@example.com', 'User3'));
        $this->assertSame(['user1@example.com', 'User2 <user2@example.com>', 'User3 <user3@example.com>'], $this->mailer->getCC());
        $this->assertInstanceOf(Mailer::class, $this->mailer->removeCC('user2@example.com'));
        $this->assertSame([0 => 'user1@example.com', 2 => 'User3 <user3@example.com>'], $this->mailer->getCC());
        $this->assertInstanceOf(Mailer::class, $this->mailer->clearCC());
        $this->assertEmpty($this->mailer->getCC());
        $this->assertInstanceOf(Mailer::class, $this->mailer->cc(['user1@example.com', 'User2 <user2@example.com>']));
        $this->assertInstanceOf(Mailer::class, $this->mailer->cc(['user2@example.com', 'User3 <user3@example.com>']));
        $this->assertSame(['user1@example.com', 'User2 <user2@example.com>', 'User3 <user3@example.com>'], $this->mailer->getCC());
    }

    public function testBCC()
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer->bcc('user1@example.com'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->bcc('user1@example.com'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->bcc('User2 <user2@example.com>'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->bcc('User2 <user2@example.com>'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->bcc('user3@example.com', 'User3'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->bcc('user3@example.com', 'User3'));
        $this->assertSame(['user1@example.com', 'User2 <user2@example.com>', 'User3 <user3@example.com>'], $this->mailer->getBCC());
        $this->assertInstanceOf(Mailer::class, $this->mailer->removeBCC('user2@example.com'));
        $this->assertSame([0 => 'user1@example.com', 2 => 'User3 <user3@example.com>'], $this->mailer->getBCC());
        $this->assertInstanceOf(Mailer::class, $this->mailer->clearBCC());
        $this->assertEmpty($this->mailer->getBCC());
        $this->assertInstanceOf(Mailer::class, $this->mailer->bcc(['user1@example.com', 'User2 <user2@example.com>']));
        $this->assertInstanceOf(Mailer::class, $this->mailer->bcc(['user2@example.com', 'User3 <user3@example.com>']));
        $this->assertSame(['user1@example.com', 'User2 <user2@example.com>', 'User3 <user3@example.com>'], $this->mailer->getBCC());
    }

    public function testReplyTo()
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer->replyTo('user1@example.com'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->replyTo('user1@example.com'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->replyTo('User2 <user2@example.com>'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->replyTo('User2 <user2@example.com>'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->replyTo('user3@example.com', 'User3'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->replyTo('user3@example.com', 'User3'));
        $this->assertSame(['user1@example.com', 'User2 <user2@example.com>', 'User3 <user3@example.com>'], $this->mailer->getReplyTo());
        $this->assertInstanceOf(Mailer::class, $this->mailer->removeReplyTo('user2@example.com'));
        $this->assertSame([0 => 'user1@example.com', 2 => 'User3 <user3@example.com>'], $this->mailer->getReplyTo());
        $this->assertInstanceOf(Mailer::class, $this->mailer->clearReplyTo());
        $this->assertEmpty($this->mailer->getReplyTo());
        $this->assertInstanceOf(Mailer::class, $this->mailer->replyTo(['user1@example.com', 'User2 <user2@example.com>']));
        $this->assertInstanceOf(Mailer::class, $this->mailer->replyTo(['user2@example.com', 'User3 <user3@example.com>']));
        $this->assertSame(['user1@example.com', 'User2 <user2@example.com>', 'User3 <user3@example.com>'], $this->mailer->getReplyTo());
    }

    public function testFrom()
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer->from('user@example.com'));
        $this->assertSame('user@example.com', $this->mailer->getFrom());
        $this->assertInstanceOf(Mailer::class, $this->mailer->from('User <user@example.com>'));
        $this->assertSame('User <user@example.com>', $this->mailer->getFrom());
        $this->assertInstanceOf(Mailer::class, $this->mailer->from('user2@example.com', 'User2'));
        $this->assertSame('User2 <user2@example.com>', $this->mailer->getFrom());
        $this->assertInstanceOf(Mailer::class, $this->mailer->resetFrom());
        $this->assertSame('mail@example.com', $this->mailer->getFrom());
    }

    public function testAttachments()
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer->attach('images/foo1.png'));
        $this->assertInstanceOf(Mailer::class, $this->mailer->attach('images/foo2.png', 'bar.png'));
        $this->assertSame(['images/foo1.png' => 'foo1.png', 'images/foo2.png' => 'bar.png'], $this->mailer->getAttachments());
        $this->assertInstanceOf(Mailer::class, $this->mailer->detach('images/foo2.png'));
        $this->assertSame(['images/foo1.png' => 'foo1.png'], $this->mailer->getAttachments());
        $this->assertInstanceOf(Mailer::class, $this->mailer->clearAttachments());
        $this->assertEmpty($this->mailer->getAttachments());
    }

    public function testAttachData()
    {
        $this->expectException(\Exception::class);
        $this->mailer->attachData('foo', 'blub.pdf', 'application/pdf');
    }

    public function testEmbed()
    {
        $cid1 = $this->mailer->embed('images/foo1.png');
        $this->assertStringStartsWith('cid:data_', $cid1);
        $this->assertSame(22, strlen($cid1));
        $cid2 = $this->mailer->embed('images/foo2.png');
        $this->assertNotSame($cid1, $cid2);
        $this->assertSame($cid1, $this->mailer->embed('images/foo1.png'));
        $this->assertSame(['images/foo1.png' => substr($cid1, 4), 'images/foo2.png' => substr($cid2, 4)], $this->mailer->getEmbeddedFiles());
        $this->assertInstanceOf(Mailer::class, $this->mailer->removeEmbeddedFile('images/foo2.png'));
        $this->assertSame(['images/foo1.png' => substr($cid1, 4)], $this->mailer->getEmbeddedFiles());
        $this->assertInstanceOf(Mailer::class, $this->mailer->clearEmbeddedFiles());
        $this->assertEmpty($this->mailer->getEmbeddedFiles());
    }

    public function testEmbedData()
    {
        $this->expectException(Exception::class);
        $this->mailer->embedData('foo', 'blub.pdf', 'application/pdf');
    }

    public function testSendWithoutReceiver()
    {
        $this->expectException(MailException::class);
        $this->mailer->send();
    }

    public function testSendWithoutFrom()
    {
        $this->expectException(MailException::class);
        $this->mailer->from(null)->send('to@example.com');
    }

    public function testSendPlainText()
    {
        $this->assertInstanceOf(Mailer::class,
            $this->mailer->send(
                ['to@example.com', 'to2@example.com'],
                'My Subject!',
                'My Body!'
            )
        );
    }

    public function testSendSendPlainTextFluent()
    {
        $this->assertInstanceOf(Mailer::class,
            $this->mailer
                ->to('to@example.com')
                ->cc('cc@example.com')
                ->bcc('bcc@example.com')
                ->replyTo('replyto@example.com')
                ->subject('My Subject!')
                ->body('My Body!')
                ->send()
        );
//          ->body('<html><body><h1>Hello World!</h1><img src="' . $this->mailer->embed(public_path('images/logo.png')) . '"/></body></html>');
    }

    public function testSendPlainTextWithAttachment()
    {
        $this->assertInstanceOf(Mailer::class,
            $this->mailer
                ->body('My Body!')
                ->attach(storage_path('temp/~foo.txt'))
                ->send('to@example.com', 'My Subject!')
        );
    }

    public function testSendHtmlMail()
    {
        $this->assertInstanceOf(Mailer::class,
            $this->mailer
                //->body('<html><body><h1>Hello World!</h1><img src="' . $this->mailer->embed(public_path('images/logo.png')) . '"/></body></html>')
                ->body('<html><body><h1>My Body!</h1></body></html>')
                ->send('to@example.com', 'My Subject!')
        );
    }

    public function testSendHtmlMailWithAttachment()
    {
        $this->assertInstanceOf(Mailer::class,
            $this->mailer
                //->body('<html><body><h1>Hello World!</h1><img src="' . $this->mailer->embed(public_path('images/logo.png')) . '"/></body></html>')
                ->body('<html><body><h1>My Body!</h1></body></html>')
                ->attach(storage_path('temp/~foo.txt'))
                ->send('to@example.com', 'My Subject!')
        );
    }

    public function testSendHtmlMailWithEmbeddedFile()
    {
        $this->assertInstanceOf(Mailer::class,
            $this->mailer
                ->body('<html><body><h1>My Body!</h1><img src="' . $this->mailer->embed(storage_path('temp/~foo.png')) . '"/></body></html>')
                ->send('to@example.com', 'My Subject!')
        );
    }

    public function testSendHtmlMailWithAttachmentAndEmbeddedFile()
    {
        $this->assertInstanceOf(Mailer::class,
            $this->mailer
                ->body('<html><body><h1>My Body!</h1><img src="' . $this->mailer->embed(storage_path('temp/~foo.png')) . '"/></body></html>')
                ->attach(storage_path('temp/~foo.txt'))
                ->send('to@example.com', 'My Subject!')
        );
    }

    public function testPretend()
    {
        $log = storage_path('logs/~test.log');
        @unlink($log);

        $this->assertFalse($this->mailer->getPretend());
        $this->assertInstanceOf(Mailer::class, $this->mailer->setPretend(true));
        $this->assertTrue($this->mailer->getPretend());
        $this->assertInstanceOf(Mailer::class,
            $this->mailer->send(
                ['to@example.com', 'to2@example.com'],
                'My Subject!',
                'My Body!'
            )
        );

        $this->assertFileExists($log);
    }

}