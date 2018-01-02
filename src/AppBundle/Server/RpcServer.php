<?php
/**
 * Created by PhpStorm.
 * User: andrzej
 * Date: 27.12.17
 * Time: 16:11
 */

namespace AppBundle\Server;

use OldSound\RabbitMqBundle\RabbitMq\RpcServer as OldSoundRpcServer;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RpcServer extends OldSoundRpcServer
{
    protected function sendReply($result, $client, $correlationId)
    {
        $reply = new AMQPMessage($result, array(
                'correlation_id' => $correlationId,
                'content_type' => 'application/json',
                'content_encoding' => 'UTF-8',
            )
        );

        $headersTable = new AMQPTable([
            'jsonrpc' => '2.0',
        ]);

        $reply->set('application_headers', $headersTable);
        $this->getChannel()->basic_publish($reply, '', $client);
    }


}