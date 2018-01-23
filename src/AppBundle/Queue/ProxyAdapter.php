<?php
/**
 * Created by PhpStorm.
 * User: andrzej
 * Date: 31.12.17
 * Time: 14:23
 */

namespace AppBundle\Queue;


use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use function GuzzleHttp\Promise\queue;
use GuzzleHttp\Promise\RejectedPromise;
use Humus\Amqp\JsonRpc\Client;
use Humus\Amqp\JsonRpc\JsonRpcClient;
use Humus\Amqp\JsonRpc\JsonRpcRequest;
use Humus\Amqp\JsonRpc\ResponseCollection;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use Psr\Log\LoggerInterface;

class ProxyAdapter implements AdapterInterface
{
    /** @var JsonRpcClient */
    protected $client;
    /** @var LoggerInterface */
    protected $logger;
    protected $classes = [];
    /**
     * @var ResponseCollection
     */
    protected $responses;
    /** @var array|PromiseInterface[] */
    private $handleIds = [];


    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function addClassExchange($class, $exchangeName)
    {
        $this->classes[$class] = $exchangeName;
    }


    /**
     * Call remote object
     *
     * @param string $wrappedClass
     * @param string $method
     * @param array $params
     * @return PromiseInterface
     * @throws \LogicException
     */
    public function call(string $wrappedClass, string $method, array $params = []): Promise
    {


        if (!isset($this->classes[$wrappedClass])) {
            throw new \LogicException("Not defined exchage for class: {$wrappedClass}");
        }
        $serverName = $this->classes[$wrappedClass];

        $queue = queue();
        $id = \uniqid('id', true);
        $queue->add(function () use ($serverName, $method, $params) {
            $request = new JsonRpcRequest($serverName, $method, $params);
            $this->client->addRequest($request);
        });
        $promise = (new Promise(
            [$this, 'execute'],
            function () use ($id) {
                return $this->cancel($id);
            }
        ));
        $queue->add(function () use ($id, $promise) {
            $response = $this->responses->getResponse($id);
            if (null !== $response) {
                $error = $response->error();
                if (null !== $error) {
                    $this->logger->warning($error->message());
                    $promise->reject(new RejectedPromise($error));

                } else {
                    $promise->resolve($response->result());
                }

            } else {
                $promise->reject(new RejectedPromise(new \RuntimeException('Null Exception')));
            }
        });
        $this->handleIds[$id] = $promise;
        return $promise;

    }

    public function execute()
    {
        $queue = queue();
        $this->responses = $this->client->getResponseCollection();
        $queue->run();

    }

    private function cancel($id)
    {
        $this->handleIds[$id]->cancel();
        unset($this->handleIds[$id]);
    }

}