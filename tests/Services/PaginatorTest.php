<?php

namespace Core\Tests\Services;

use Core\Services\DI;
use Core\Services\Paginator;
use Core\Services\Request;
use Core\Testing\TestCase;

class PaginatorTest extends TestCase
{
    private static $origRequest;

    public static function setUpBeforeClass()
    {
        self::$origRequest = DI::getInstance()->get('request');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('request', self::$origRequest, true);
    }

    protected function setUp()
    {
    }

    protected function tearDown()
    {
        unset($_GET['page']);
    }

    private function mockRequest()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['baseUrl', 'path'])->getMock();
        $request->expects($this->any())->method('baseUrl')->willReturn('my_base_url');
        $request->expects($this->any())->method('path')->willReturn('my_path');
        DI::getInstance()->set('request', $request, true);
    }

    public function testWithoutPage()
    {
        $p = new Paginator(0, 4, 2);
        $this->assertSame(0, $p->total());
        $this->assertSame(4, $p->limit());
        $this->assertSame(0, $p->offset());
        $this->assertSame(0, $p->currentPage());
        $this->assertSame(0, $p->lastPage());
        $this->assertFalse($p->hasMultiplePages());
        $this->assertSame([], $p->pages());
    }

    public function testWithOnePage()
    {
        $_GET['page'] = '0';
        $p = new Paginator(4, 4);
        $this->assertSame(4, $p->total());
        $this->assertSame(4, $p->limit());
        $this->assertSame(0, $p->offset());
        $this->assertSame(1, $p->currentPage());
        $this->assertSame(1, $p->lastPage());
        $this->assertFalse($p->hasMultiplePages());
        $this->assertSame([1], $p->pages());
        unset($_GET['page']);
    }

    public function testWithTwoPages()
    {
        $_GET['page'] = '2';
        $p = new Paginator(5, 4);
        $this->assertSame(5, $p->total());
        $this->assertSame(4, $p->limit());
        $this->assertSame(4, $p->offset());
        $this->assertSame(2, $p->currentPage());
        $this->assertSame(2, $p->lastPage());
        $this->assertTrue($p->hasMultiplePages());
        $this->assertSame([1, 2], $p->pages());
    }

    public function testAllPagesCanBeShown() // <= 13 pages
    {
        $p = new Paginator(49, 4, 3);
        $this->assertSame(8, $p->offset());
        $this->assertSame(3, $p->currentPage());
        $this->assertSame(13, $p->lastPage());
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13], $p->pages());
    }

    public function testPagesWithSliderAtLeftSite() // currentPage <= 7
    {
        $p = new Paginator(53, 4, 1);
        $this->assertSame(0, $p->offset());
        $this->assertSame(14, $p->lastPage());
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, '...', 13, 14], $p->pages());

        $p = new Paginator(53, 4, 7);
        $this->assertSame(24, $p->offset());
        $this->assertSame(14, $p->lastPage());
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, '...', 13, 14], $p->pages());
    }

    public function testPagesWithSliderAtRightSite() // currentPage > lastPage - 7
    {
        $p = new Paginator(53, 4, 8);
        $this->assertSame(28, $p->offset());
        $this->assertSame(14, $p->lastPage());
        $this->assertSame([1, 2, '...', 5, 6, 7, 8, 9, 10, 11, 12, 13, 14], $p->pages());

        $p = new Paginator(53, 4, 14);
        $this->assertSame(52, $p->offset());
        $this->assertSame(14, $p->lastPage());
        $this->assertSame([1, 2, '...', 5, 6, 7, 8, 9, 10, 11, 12, 13, 14], $p->pages());
    }

    public function testPagesWithSliderInMiddle()
    {
        $p = new Paginator(57, 4, 8);
        $this->assertSame(28, $p->offset());
        $this->assertSame(15, $p->lastPage());
        $this->assertSame([1, 2, '...', 5, 6, 7, 8, 9, 10, 11, '...', 14, 15], $p->pages());
    }

    public function testUrl()
    {
        $this->mockRequest();

        $p = new Paginator(49, 4); // 13 pages
        $this->assertSame('my_base_url/my_path?page=1', $p->url(1));
        $this->assertSame('my_base_url/my_path?page=13', $p->url(13));
    }

    public function testUrlWithPageOutOfRange()
    {
        $p = new Paginator(49, 4); // 13 pages
        $this->expectException(\OutOfRangeException::class);
        $p->url(14);
    }

    public function testPreviousUrl()
    {
        $_GET['page'] = '10';
        $this->mockRequest();
        $p = new Paginator(49, 4); // 13 pages
        $this->assertSame('my_base_url/my_path?page=9', $p->previousUrl());
        $p = new Paginator(49, 4, 1);
        $this->assertNull($p->previousUrl());
    }

    public function testNextUrl()
    {
        $_GET['page'] = '10';
        $this->mockRequest();
        $p = new Paginator(49, 4); // 13 pages
        $this->assertSame('my_base_url/my_path?page=11', $p->nextUrl());
        $p = new Paginator(49, 4, 13);
        $this->assertNull($p->nextUrl());
    }

    public function testSetAndGetPageKey()
    {
        $this->mockRequest();
        $p = new Paginator(49, 4); // 13 pages
        $this->assertInstanceOf(Paginator::class, $p->setPageKey('foo'));
        $this->assertSame('foo', $p->getPageKey());
        $this->assertSame('my_base_url/my_path?foo=1', $p->url(1));
    }

    public function testSetAndGetParameters()
    {
        $this->mockRequest();
        $p = new Paginator(49, 4); // 13 pages
        $this->assertInstanceOf(Paginator::class, $p->setParameters(['foo' => 'bar']));
        $this->assertSame(['foo' => 'bar'], $p->getParameters());
        $this->assertSame('my_base_url/my_path?foo=bar&page=1', $p->url(1));
    }

    public function testSetAndGetFragment()
    {
        $this->mockRequest();
        $p = new Paginator(49, 4); // 13 pages
        $this->assertInstanceOf(Paginator::class, $p->setFragment('foo'));
        $this->assertSame('foo', $p->getFragment());
        $this->assertSame('my_base_url/my_path?page=1#foo', $p->url(1));
    }
}
