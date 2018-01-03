<?php
/**
 * Created by PhpStorm.
 * User: andrzej
 * Date: 31.12.17
 * Time: 12:55
 */

namespace AppBundle\DependencyInjection\CompilerPass;


use AppBundle\Queue\ProxyAdapter;
use AppBundle\Queue\RpcClientQueueFactory;
use AppBundle\Queue\RpcExchanges;
use Humus\Amqp\Driver\AmqpExtension\Channel;
use Humus\Amqp\Driver\AmqpExtension\Connection;
use Humus\Amqp\Driver\AmqpExtension\Exchange;
use Humus\Amqp\Driver\AmqpExtension\Queue;
use Humus\Amqp\JsonRpc\JsonRpcClient;
use ProxyManager\Factory\RemoteObjectFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RpcClientsCompiler implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        $services = $container->findTaggedServiceIds('rpc_client');

        $container->getDefinition(Connection::class)->addMethodCall('connect');
        $exchanges = $this->declareExchanges($services, $container);
        $defClientQueue = new Definition(Queue::class);
        $defClientQueue->setFactory([new Reference(RpcClientQueueFactory::class), 'create',]);
        $proxyClientQueue = 'app.json_rpc.client_queue.default';
        $container->setDefinition($proxyClientQueue, $defClientQueue);

        $defExchangesCollection = new Definition(RpcExchanges::class);
        $defExchangesCollection->setFactory([new Reference(RpcExchanges::class), 'getExchanges',]);
        $defExchangesName = 'app.json_rpc.exchanges.default';
        $container->setDefinition($defExchangesName, $defExchangesCollection);

        $rpcClientDef = new Definition(JsonRpcClient::class, [new Reference($proxyClientQueue), new Reference($defExchangesName)]);
        $container->setDefinition(JsonRpcClient::class, $rpcClientDef);

        foreach ($services as $serviceName => $tags) {
            $shortName = (new \ReflectionClass($serviceName))->getShortName();

            $definition = new Definition($serviceName);
            $definition->setFactory([new Reference(RemoteObjectFactory::class), 'createProxy',]);
            $definition->setArgument(0, $serviceName);
            $id = "app.json_rpc.client_proxy.{$shortName}";
            $container->setDefinition($id, $definition);
            $container->setAlias($serviceName, $id);

        }


    }

    private function declareExchanges($services, ContainerBuilder $container)
    {
        $exchanges = [];
        foreach ($services as $serviceName => $tags) {
            foreach ($tags as $tag) {
                $exchangeName = isset($tag['exchange']) ? $tag['exchange'] : '';

                $exchangeServiceName = empty($exchangeName) ? 'app.json_rpc.exchange.default' : "app.json_rpc.exchange.{$exchangeName}";
                $exchanges[$exchangeName] = $exchangeServiceName;

                if ($container->hasDefinition($exchangeServiceName)) {
                    continue;
                }

                $definition = new Definition(Exchange::class);
                $definition->setArgument(0, new Reference(Channel::class));
                $definition->addMethodCall('setName', [$exchangeName]);

                $container->setDefinition($exchangeServiceName, $definition);

                $container->getDefinition(RpcExchanges::class)
                    ->addMethodCall('addExchange', [$exchangeName, new Reference($exchangeServiceName)]);

                $container->getDefinition(ProxyAdapter::class)
                    ->addMethodCall('addClassExchange', [$serviceName, $exchangeName]);

            }
        }

        return $exchanges;
    }
}