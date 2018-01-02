<?php
/**
 * Created by PhpStorm.
 * User: andrzej
 * Date: 31.12.17
 * Time: 14:23
 */

namespace AppBundle\Queue;


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

    public function __construct(JsonRpcClient $client, LoggerInterface $logger)
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
     */
    public function call(string $wrappedClass, string $method, array $params = [])
    {
        if (!isset($this->classes[$wrappedClass])) {
            throw new \LogicException("Not defined exchage for class: {$wrappedClass}");
        }
        $serverName = $this->classes[$wrappedClass];
        $id = \uniqid('id', true);
        $this->client->addRequest(new JsonRpcRequest($serverName, $method, $params, $id));
        $responses = $this->client->getResponseCollection();

        $response = $responses->getResponse($id);
        if (null !== $response->error()) {
            $error = $response->error();
            $this->logger->warning($error->message());
        }
        return $response;
    }
}