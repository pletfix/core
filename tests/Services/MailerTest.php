<?php

namespace Core\Tests\Services {

    use Core\Middleware\Contracts\Middleware;
    use Core\Services\Contracts\Response;
    use Core\Services\Contracts\Delegate;
    use Core\Services\Contracts\Request;
    use Core\Services\DI;
    use Core\Services\Mailer;
    use Core\Testing\TestCase;

    class MailerTest extends TestCase
    {
        /**
         * @var Mailer
         */
        private $mailer;

        protected function setUp()
        {
            DI::getInstance()->get('config')
                ->set('mail', [
                    'replyTo' => [],
                    'from'    => 'mail@example.com',
                    'pretend' => false,
                ]);

            $this->mailer = new Mailer;
        }

        public function testSetter()
        {
            $this->assertInstanceOf(Mailer::class, $this->mailer->subject('my subject'));
            $this->assertInstanceOf(Mailer::class, $this->mailer->body('my body'));
            $this->assertInstanceOf(Mailer::class, $this->mailer->altBody('my alternate body'));

        }

        public function testView()
        {
            $view = $this->getMockBuilder(\Core\Services\View::class)->setMethods(['render'])->getMock();
            $view->expects($this->once())->method('render')->with('foo', ['a' => 'A', 'b' => 'B'])->willReturn('Hello World!');
            di()->set('view', $view, false);

            $this->assertInstanceOf(Mailer::class, $this->mailer->view('foo', ['a' => 'A', 'b' => 'B']));
        }
    }

}

namespace Core\Services {

    function mail($to, $subject, $content, $header)
    {
        return true;
    }

}