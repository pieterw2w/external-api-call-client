# external-api-call-client
Setup code for making a client to an external API.

Some very low-level classes used to structure calls to external API's. Internally this works like a state machine for API calls. And in every step the API call can fail.

![State machine](https://www.plantuml.com/plantuml/png/JSmn2e0m40NHFgTOqkS2AQm4iHBq11f-4LaITJTDJYz25EitZ1tJKPJojYEeTTsCiq3Kqu8hhXmhcfacv3gQ8KTE0az3gPL1WIEIKbYPpOjFQAUYlxsd7l9zDR_h6m00)


## Code usage:
Basically you use ExternalClient as base class and extend it and add functionality like this:

```php
<?php
use GuzzleHttp\Client;
use PaqtCom\ExternalApiCallClient\Services\ExternalClient;
use PaqtCom\ExternalApiCallClient\ErrorHandlers\ThrowExceptionOnError;

class ExampleClient extends ExternalClient
{
  public function __construct(Client $client)
  {
      parent::__construct(new JsonRequestFactory(), $client, new JsonResponseHandler(), new ThrowExceptionOnError(), new LogToDatabase());
  }
  
  public function createOrder(OrderDto $order): OrderPlacedDto
  {
      return $this->post(new ExternalClientCall('Place order ' . $order->id, '/api/Order/' . $order->id , OrderPlacedDto::class), $order);
  }
}
```

The ExternalClient makes the actual call and uses the ExternalClientCall to remember the current state of an API call.
