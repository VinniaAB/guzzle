<?php declare(strict_types=1);

namespace Vinnia\Guzzle\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

abstract class AbstractTest extends TestCase
{
    protected function successHandler(): callable
    {
        return function (RequestInterface $request, array $options = []) {
            return Create::promiseFor(new Response(200));
        };
    }

    protected function getClient(callable $handler, array $middleware = []): ClientInterface
    {
        $stack = HandlerStack::create($handler);

        foreach ($middleware as $m) {
            $stack->push($m);
        }

        return new Client([
            'handler' => $stack,
        ]);
    }

}
