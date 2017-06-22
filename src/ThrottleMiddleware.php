<?php
/**
 * Created by PhpStorm.
 * User: johan
 * Date: 2017-03-06
 * Time: 23:35
 */
declare(strict_types = 1);

namespace Vinnia\Guzzle;

use Psr\Http\Message\RequestInterface;

class ThrottleMiddleware
{

    /**
     * @var callable
     */
    private $nextHandler;

    /**
     * @var float
     */
    private $threshold;

    /**
     * @var float
     */
    private $previous;

    /**
     * ThrottleMiddleware constructor.
     * @param callable $nextHandler
     * @param float $requestsPerSecond
     */
    function __construct(callable $nextHandler, float $requestsPerSecond)
    {
        $this->nextHandler = $nextHandler;
        $this->threshold = 1e6/$requestsPerSecond;
        $this->previous = 0;
    }

    /**
     * Get the current time in microseconds.
     * Probably won't work on a 32bit computer.
     * @return int
     */
    private function now(): int
    {
        return (int) (microtime(true) * 1e6);
    }

    private function throttle(): void
    {
        $diff = $this->now() - $this->previous;
        if ($this->threshold > $diff) {
            $sleep = $this->threshold - $diff;
            usleep((int)$sleep);
        }
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return mixed
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        $this->throttle();
        $next = $this->nextHandler;
        $result = $next($request, $options);
        $this->previous = $this->now();
        return $result;
    }

}
