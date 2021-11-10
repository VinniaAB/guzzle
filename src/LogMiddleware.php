<?php declare(strict_types = 1);

namespace Vinnia\Guzzle;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Closure;
use Psr\Log\LogLevel;

final class LogMiddleware
{
    private LoggerInterface $logger;

    /**
     * @var mixed
     */
    private $level;
    private Closure $bodyFormatter;

    /**
     * @param LoggerInterface $logger
     * @param mixed $level
     */
    public function __construct(LoggerInterface $logger, $level = LogLevel::DEBUG)
    {
        $this->logger = $logger;
        $this->level = $level;
        $this->bodyFormatter = function (string $body) {
            return $body;
        };
    }

    /**
     * @param MessageInterface $message
     * @param int $id
     */
    private function log(MessageInterface $message, int $id): void
    {
        $data = [];

        if ($message instanceof RequestInterface) {
            $data[] = sprintf('%s %s HTTP/%s', $message->getMethod(), rawurldecode((string) $message->getUri()), $message->getProtocolVersion());
        }
        else if ($message instanceof ResponseInterface) {
            $data[] = sprintf('HTTP/%s %d %s', $message->getProtocolVersion(), $message->getStatusCode(), $message->getReasonPhrase());
        }

        foreach ($message->getHeaders() as $name => $values) {
            $data[] = sprintf('%s: %s', $name, implode(';', $values));
        }

        $data[] = call_user_func($this->bodyFormatter, (string) $message->getBody());

        $this->logger->log($this->level, implode("\n", $data), [
            'id' => $id,
        ]);
    }

    public function __invoke(callable $next): Closure
    {
        return function (RequestInterface $request, array $options) use ($next): PromiseInterface {
            $id = random_int(0, PHP_INT_MAX);
            $this->log($request, $id);
            return $next($request, $options)->then(function (MessageInterface $message) use ($id) {
                $this->log($message, $id);
                return $message;
            }, function ($error) use ($id) {
                if ($error instanceof RequestException && $response = $error->getResponse()) {
                    $this->log($response, $id);
                }
                return Create::rejectionFor($error);
            });
        };
    }

    public function setBodyFormatter(callable $formatter): void
    {
        $this->bodyFormatter = Closure::fromCallable($formatter);
    }

}
