<?php
/**
 * Created by PhpStorm.
 * User: johan
 * Date: 2017-06-22
 * Time: 14:32
 */

namespace Vinnia\Guzzle\Tests;


use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use Vinnia\Guzzle\ThrottleMiddleware;

class ThrottleMiddlewareTest extends AbstractTest
{

    /**
     * @var ThrottleMiddleware
     */
    public $middleware;

    public function setUp()
    {
        parent::setUp();

        $this->middleware = new ThrottleMiddleware(500);
    }

    public function testDoesntThrottleOnFirstRequest()
    {
        $now = $this->middleware->getTimeInMilliseconds();
        $client = $this->getClient($this->successHandler(), [$this->middleware]);
        $client->request('GET', 'http://google.com');
        $this->assertLessThan(10, abs($this->middleware->getTimeInMilliseconds() - $now));
    }

    public function testThrottlesOnSecondRequest()
    {
        $client = $this->getClient($this->successHandler(), [$this->middleware]);
        $client->request('GET', 'http://google.com');
        $now = $this->middleware->getTimeInMilliseconds();
        $client->request('GET', 'http://google.com');
        $diff = $this->middleware->getTimeInMilliseconds() - $now;
        $this->assertLessThan(10, abs(500 - $diff));
    }

}
