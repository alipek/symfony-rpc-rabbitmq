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
use Humus\Amqp\JsonRpc\Client;
use Humus\Amqp\JsonRpc\JsonRpcClient;
use Humus\Amqp\JsonRpc\JsonRpcRequest;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use Psr\Log\LoggerInterface;

class ProxyAdapter implements AdapterInterface
{
    /** @var JsonRpcClient */
    protected $client;
    /** @var LoggerInterface */
    protected $logger;
    protected $classes = [];
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
        $id = \uniqid('id', true);

        $promise = new Promise(
            [$this, 'execute'],
            function () use ($id) {
                return $this->cancel($id);
            }
        );

        $this->client->addRequest(new JsonRpcRequest($serverName, $method, $params, $id));
        $this->handleIds[$id] = $promise;

        return $promise;
    }

    public function execute()
    {
        $responses = $this->client->getResponseCollection();
        foreach ($this->handleIds as $handleId => $promise) {
            $response = $responses->getResponse($handleId);

            if (null !== $response) {
                $error = $response->error();
                if (null !== $error) {
                    $this->logger->warning($error->message());
                    $promise->reject($error);
                } else {
                    $promise->resolve($response->result());
                }

            } else {
                $promise->reject(new \RuntimeException('Null Exception'));
            }
        }

    }

    private function cancel($id)
    {
        $this->handleIds[$id]->cancel();
        unset($this->handleIds[$id]);
    }

}