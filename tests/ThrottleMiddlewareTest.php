<?php
/**
 * Created by PhpStorm.
 * User: johan
 * Date: 2017-06-22
 * Time: 14:32
 */

namespace Vinnia\Guzzle\Tests;


use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Vinnia\Guzzle\ThrottleMiddleware;

class ThrottleMiddlewareTest extends AbstractTest
{

    public function testThrottlesToOneRequestPerSecond()
    {
        $stack = HandlerStack::create($this->handler);
        $stack->push(function (callable $next) {
            return new ThrottleMiddleware($next, 0.5);
        });
        $client = new Client([
            'handler' => $stack,
        ]);

        $now = microtime(true);

        // first request shouldn't be throttled
        $client->get('http://google.com');

        $this->assertLessThan(1, microtime(true) - $now);

        $now = microtime(true);

        $client->get('http://google.com');

        $diff = microtime(true) - $now;
        $this->assertLessThan(0.1, $diff - 2);
    }

}
