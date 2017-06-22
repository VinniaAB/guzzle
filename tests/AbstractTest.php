<?php
/**
 * Created by PhpStorm.
 * User: johan
 * Date: 2017-06-22
 * Time: 14:30
 */

namespace Vinnia\Guzzle\Tests;


use function GuzzleHttp\Promise\promise_for;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

abstract class AbstractTest extends TestCase
{

    /**
     * @var callable
     */
    public $handler;

    public function setUp()
    {
        parent::setUp();

        $this->handler = function (RequestInterface $request, array $options = []) {
            return promise_for(new Response(200));
        };
    }

}
