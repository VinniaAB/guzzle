<?php declare(strict_types=1);

namespace Vinnia\Guzzle\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Log\AbstractLogger;
use Vinnia\Guzzle\LogMiddleware;

final class LogMiddlewareTest extends AbstractTest
{
    public ?LogMiddleware $middleware = null;

    /**
     * @var string[]
     */
    public array $messages = [];

    public function setUp(): void
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

        $this->middleware = new LogMiddleware($countingLogger);
    }

    public function testLogsOnceOnRequestAndOnceOnResponse()
    {
        $client = $this->getClient($this->successHandler(), [$this->middleware]);
        $client->request('GET', 'http://www.google.com');

        $this->assertCount(2, $this->messages);
    }

    public function testBodyFormatter()
    {
        $this->middleware->setBodyFormatter(function (string $body) {
            return 'THIS BODY IS FORMATTED';
        });
        $client = $this->getClient($this->successHandler(), [$this->middleware]);
        $client->request('GET', 'http://www.google.com');
        $this->assertTrue(strpos($this->messages[1], 'THIS BODY IS FORMATTED') !== false);
    }

    public function testLogsExceptionsThrownOnRequest()
    {
        $client = $this->getClient(function (RequestInterface $request, array $options = []) {
            return Create::rejectionFor(new RequestException('Some Error', $request, new Response(400)));
        }, [$this->middleware]);
        try {
            $client->request('GET', 'http://www.google.com');
        }
        catch (\Exception $e) {
            $this->assertInstanceOf(RequestException::class, $e);
            $this->assertCount(2, $this->messages);
        }
    }

}
