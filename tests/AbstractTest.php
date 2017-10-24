<?php
/**
 * Created by PhpStorm.
 * User: johan
 * Date: 2017-06-22
 * Time: 14:30
 */

namespace Vinnia\Guzzle\Tests;


use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use function GuzzleHttp\Promise\promise_for;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

abstract class AbstractTest extends TestCase
{

    protected function successHandler(): callable
    {
        return function (RequestInterface $request, array $options = []) {
            return promise_for(new Response(200));
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
