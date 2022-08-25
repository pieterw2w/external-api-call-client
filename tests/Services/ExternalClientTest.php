<?php

namespace PaqtCom\Tests\ExternalApiCallClient\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PaqtCom\ExternalApiCallClient\Contracts\ErrorHandlerContract;
use PaqtCom\ExternalApiCallClient\Contracts\LogHandlerContract;
use PaqtCom\ExternalApiCallClient\Contracts\RequestFactoryContract;
use PaqtCom\ExternalApiCallClient\Contracts\ResponseHandlerContract;
use PaqtCom\ExternalApiCallClient\Enums\ExternalClientStateEnum;
use PaqtCom\ExternalApiCallClient\Mediators\ExternalClientCall;
use PaqtCom\ExternalApiCallClient\Services\ExternalClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ExternalClientTest extends TestCase implements LogHandlerContract
{
    use ProphecyTrait;

    private ExternalClient $testItem;

    private MockHandler $mockHandler;

    /**
     * @phpstan-var ObjectProphecy<RequestFactoryContract>
     */
    private ObjectProphecy $requestBuilder;

    /**
     * @phpstan-var ObjectProphecy<ResponseHandlerContract>
     */
    private ObjectProphecy $responseHandler;

    /**
     * @phpstan-var ObjectProphecy<ErrorHandlerContract>
     */
    private ObjectProphecy $errorHandler;

    /**
     * @phpstan-var ExternalClientCall<mixed, mixed, mixed>|null
     */
    private ?ExternalClientCall $expectedClientCall;

    private ?ExternalClientStateEnum $expectedErrorState;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $client = new Client(['handler' => $this->mockHandler]);
        $this->requestBuilder = $this->prophesize(RequestFactoryContract::class);
        $this->responseHandler = $this->prophesize(ResponseHandlerContract::class);
        $this->errorHandler = $this->prophesize(ErrorHandlerContract::class);
        $this->expectedClientCall = null;
        $this->expectedErrorState = null;
        $this->testItem = new ExternalClient(
            $this->requestBuilder->reveal(),
            $client,
            $this->responseHandler->reveal(),
            $this->errorHandler->reveal(),
            $this
        );
    }

    public function testPost(): void
    {
        $input = new class () {
        };
        $request = new Request('POST', 'super-mushroom', [], '{}');
        $response = new Response(200, [], '{"id":42}');
        $call = new ExternalClientCall(__CLASS__, 'super-mushroom', get_class($input));
        try {
            $this->requestBuilder->createPostRequest($call, $input)
                  ->shouldBeCalled()
                  ->willReturn($request);
            $this->mockHandler->append($response);
            $this->responseHandler->handleResponse($call)
                  ->shouldBeCalled()
                  ->willReturn($input);

            // make sure the error handler just throws the exception in case the test fails...
            $this->errorHandler->handleError(Argument::cetera())
                ->will(function (array $args) {
                    throw $args[0]->getThrowable();
                });

            $this->expectedClientCall = $call;

            $this->assertEquals($input, $this->testItem->post($call, $input));
            $this->assertEquals($request, $call->getRequest());
            $this->assertEquals($response, $call->getResponse());
            $this->assertEquals($input, $call->getResult());
        } finally {
            $this->assertNull($call->getThrowable());
        }
    }

    public function testPatch(): void
    {
        $input = new class () {
        };
        $request = new Request('PUT', 'super-mushroom', [], '{}');
        $response = new Response(200, [], '{"id":42}');
        $call = new ExternalClientCall(__CLASS__, 'super-mushroom', get_class($input));
        try {
            $this->requestBuilder->createPutRequest($call, $input)
                ->shouldBeCalled()
                ->willReturn($request);
            $this->mockHandler->append($response);
            $this->responseHandler->handleResponse($call)
                ->shouldBeCalled()
                ->willReturn($input);

            // make sure the error handler just throws the exception in case the test fails...
            $this->errorHandler->handleError(Argument::cetera())
                ->will(function (array $args) {
                    throw $args[0]->getThrowable();
                });

            $this->expectedClientCall = $call;

            $this->assertEquals($input, $this->testItem->patch($call, $input));
            $this->assertEquals($request->withMethod('PATCH'), $call->getRequest());
            $this->assertEquals($response, $call->getResponse());
            $this->assertEquals($input, $call->getResult());
        } finally {
            $this->assertNull($call->getThrowable());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function logRequest(ExternalClientCall $clientCall, ?ExternalClientStateEnum $errorState): void
    {
        if (is_null($this->expectedClientCall)) {
            return;
        }
        $this->assertEquals($this->expectedClientCall, $clientCall);
        $this->assertEquals($this->expectedErrorState, $errorState);
    }
}
