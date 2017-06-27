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
     * @var ClientInterface
     */
    public $client;

    /**
     * @var LogMiddleware
     */
    public $middleware;

    /**
     * @var string[]
     */
    public $messages;

    public function setUp()
    {
        parent::setUp();

        $this->messages = [];

        $countingLogger = new class($this) extends AbstractLogger
        {
            private $other;

            function __construct($other)
            {
                $this->other = $other;
            }

            public function log($level, $message, array $context = array())
            {
                $this->other->messages[] = $message;
            }
        };

        $stack = HandlerStack::create($this->handler);
        $stack->push($this->middleware = new LogMiddleware($countingLogger));
        $this->client = new Client([
            'handler' => $stack,
        ]);
    }

    public function testLogsOnceOnRequestAndOnceOnResponse()
    {
        $this->client->request('GET', 'http://www.google.com');

        $this->assertEquals(2, count($this->messages));
    }

    public function testBodyFormatter()
    {
        $this->middleware->setBodyFormatter(function (string $body) {
            return 'THIS BODY IS FORMATTED';
        });
        $this->client->request('GET', 'http://www.google.com');
        $this->assertTrue(strpos($this->messages[1], 'THIS BODY IS FORMATTED') !== false);
    }

}