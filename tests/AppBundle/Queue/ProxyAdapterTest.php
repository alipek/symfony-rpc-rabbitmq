<?php
/**
 * Created by PhpStorm.
 * User: andrzej
 * Date: 20.01.18
 * Time: 15:18
 */

namespace Tests\AppBundle\Queue;

use AppBundle\Client\Fibonacci;
use AppBundle\Queue\ProxyAdapter;
use function GuzzleHttp\Promise\all;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use function GuzzleHttp\Promise\settle;
use Humus\Amqp\JsonRpc\Client;
use Humus\Amqp\JsonRpc\Response;
use Humus\Amqp\JsonRpc\ResponseCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProxyAdapterTest extends TestCase
{

    public function testCallMultiple()
    {

        $jsonRpcClient = $this->createMock(Client::class);
        $logger = $this->createMock(LoggerInterface::class);
        $proxyAdapter = new ProxyAdapter($jsonRpcClient, $logger);
        $responseCollection = $this->createMock(ResponseCollection::class);

        $jsonRpcClient->expects($this->once())->method('getResponseCollection')->willReturn($responseCollection);

        $rpcResponse = $this->createMock(Response::class);
        $rpcResponse->expects($this->any())->method('result')->willReturn(2);
        $responseCollection->expects($this->any())
            ->method('getResponse')
            ->willReturn($rpcResponse);


        $proxyAdapter->addClassExchange(Fibonacci::class, 'exchange');

        $promiseFirst = $proxyAdapter
            ->call(Fibonacci::class, 'fibonacci', [])
            ->then(function (&$result) {
                return $result = ($result * 2);
            });
        $promiseSecond = $proxyAdapter
            ->call(Fibonacci::class, 'fibonacci', [])
            ->then(function ($result) {
                return new FulfilledPromise($result * 4);
            });

        $this->assertInstanceOf(PromiseInterface::class, $promiseFirst);
        $this->assertInstanceOf(PromiseInterface::class, $promiseSecond);


        $result = settle([
            'a' => $promiseFirst,
            'b' => $promiseSecond,
        ])->wait();

        $this->assertEquals(
            [
                'a' => [
                    'state' => 'fulfilled',
                    'value' => 4,
                ],
                'b' => [
                    'state' => 'fulfilled',
                    'value' => 8,
                ],
            ],
            $result);
    }

    public function testCall()
    {

        $jsonRpcClient = $this->createMock(Client::class);
        $logger = $this->createMock(LoggerInterface::class);
        $proxyAdapter = new ProxyAdapter($jsonRpcClient, $logger);
        $responseCollection = $this->createMock(ResponseCollection::class);

        $jsonRpcClient->expects($this->any())->method('getResponseCollection')->willReturn($responseCollection);

        $rpcResponse = $this->createMock(Response::class);
        $rpcResponse->expects($this->any())->method('result')->willReturn(2);
        $responseCollection->expects($this->any())
            ->method('getResponse')
            ->willReturn($rpcResponse);


        $proxyAdapter->addClassExchange(Fibonacci::class, 'exchange');

        $promiseFirst = $proxyAdapter->call(Fibonacci::class, 'fibonacci', []);
        $promiseSecond = $proxyAdapter->call(Fibonacci::class, 'fibonacci', []);

        $this->assertInstanceOf(PromiseInterface::class, $promiseFirst);
        $this->assertInstanceOf(PromiseInterface::class, $promiseSecond);

        $result = $promiseFirst->then(function ($value) {
            return new FulfilledPromise($value * 2);
        })->wait();
        $this->assertEquals(4, $result);

    }
}
