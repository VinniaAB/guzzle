# Vinnia Guzzle
A collection of helpful guzzle middleware.

## `Vinnia\Guzzle\LogMiddleware`
Logs requests and responses to a PSR-compatible logger.

## `Vinnia\Guzzle\ThrottleMiddleware`
Throttles the request chain (blocking) with a specified wait amount.

## Example usage
```php
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;
use Vinnia\Guzzle\ThrottleMiddleware;
use Vinnia\Guzzle\LogMiddleware;

$stack = HandlerStack::create($this->handler);
$stack->push(new ThrottleMiddleware(500));
$stack->push(new LogMiddleware($psrLogger));

$client = new Client([
    'handler' => $stack,
]);

$client->request('GET', 'http://www.google.com');
```
