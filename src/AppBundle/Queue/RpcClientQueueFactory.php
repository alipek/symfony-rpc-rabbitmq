<?php
/**
 * Created by PhpStorm.
 * User: andrzej
 * Date: 31.12.17
 * Time: 14:06
 */

namespace AppBundle\Queue;


use Humus\Amqp\Constants;
use Humus\Amqp\Driver\AmqpExtension\Channel;
use Humus\Amqp\Driver\AmqpExtension\Connection;
use Humus\Amqp\Driver\AmqpExtension\Exchange;
use Humus\Amqp\Driver\AmqpExtension\Queue;

class RpcClientQueueFactory
{
    /** @var Channel */
    protected $channel;

    public function __construct(Connection $connection)
    {
        $connection->connect();
        $this->channel = $connection->newChannel();
    }


    public function create()
    {
        if(!$this->channel->isConnected()){
            $this->channel->getConnection()->connect();
        }
        $queue = new Queue($this->channel);
        $queueName = \uniqid('clientid', true);

        $queue = new Queue($this->channel);
        $queue->setName($queueName);
        $queue->setFlags(Constants::AMQP_EXCLUSIVE);

        $queue->declareQueue();

        $exchange = new Exchange($this->channel);
        $exchange->setName($queueName);
        $exchange->setType('direct');
        $exchange->setFlags(Constants::AMQP_AUTODELETE);
        $exchange->declareExchange();

        $queue->bind($queueName);

        return $queue;
    }

}