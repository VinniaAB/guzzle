<?php declare(strict_types = 1);

namespace Vinnia\Guzzle;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;

final class ThrottleMiddleware
{
    private int $wait;
    private int $previous = 0;

    public function __construct(int $wait)
    {
        $this->wait = $wait;
    }

    /**
     * Get the current time in milliseconds.
     * Probably won't work on a 32bit computer.
     * @return int
     */
    public function getTimeInMilliseconds(): int
    {
        return (int) (microtime(true) * 1e3);
    }

    private function throttle(): void
    {
        $diff = $this->getTimeInMilliseconds() - $this->previous;
        if ($this->wait > $diff) {
            $sleep = (int)(($this->wait - $diff) * 1e3);
            usleep($sleep);
        }
    }

    /**
     * @param callable $next
     * @return Closure
     */
    public function __invoke(callable $next): Closure
    {
        return function (RequestInterface $request, array $options) use ($next): PromiseInterface {
            $this->throttle();
            $result = $next($request, $options);
            $this->previous = $this->getTimeInMilliseconds();
            return $result;
        };
    }

}
