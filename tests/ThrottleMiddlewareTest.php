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
     * @var ClientInterface
     */
    public $client;

    /**
     * @var ThrottleMiddleware
     */
    public $middleware;

    public function setUp()
    {
        parent::setUp();

        $stack = HandlerStack::create($this->handler);
        $stack->push($this->middleware = new ThrottleMiddleware(500));
        $this->client = new Client([
            'handler' => $stack,
        ]);
    }

    public function testDoesntThrottleOnFirstRequest()
    {
        $now = $this->middleware->getTimeInMilliseconds();
        $this->client->request('GET', 'http://google.com');
        $this->assertLessThan(10, abs($this->middleware->getTimeInMilliseconds() - $now));
    }

    public function testThrottlesOnSecondRequest()
    {
        $this->client->request('GET', 'http://google.com');
        $now = $this->middleware->getTimeInMilliseconds();
        $this->client->request('GET', 'http://google.com');
        $diff = $this->middleware->getTimeInMilliseconds() - $now;
        $this->assertLessThan(10, abs(500 - $diff));
    }

}
