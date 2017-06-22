<?php
/**
 * Created by PhpStorm.
 * User: johan
 * Date: 2017-06-22
 * Time: 14:45
 */

namespace Vinnia\Guzzle\Tests;


use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use Psr\Log\AbstractLogger;
use Vinnia\Guzzle\LogMiddleware;

class LogMiddlewareTest extends AbstractTest
{

    /**
     * @var int
     */
    public $count;

    /**
     * @var ClientInterface
     */
    public $client;

    public function setUp()
    {
        parent::setUp();

        $this->count = 0;

        $countingLogger = new class($this) extends AbstractLogger
        {
            private $other;

            function __construct($other)
            {
                $this->other = $other;
            }

            public function log($level, $message, array $context = array())
            {
                $this->other->count += 1;
            }
        };

        $stack = HandlerStack::create($this->handler);
        $stack->push(function (callable $next) use ($countingLogger) {
            return new LogMiddleware($next, $countingLogger);
        });
        $this->client = new Client([
            'handler' => $stack,
        ]);
    }

    public function testLogsOnceOnRequestAndOnceOnResponse()
    {
        $this->client->request('GET', 'http://www.google.com');

        $this->assertEquals(2, $this->count);
    }

}