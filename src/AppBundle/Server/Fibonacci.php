<?php
/**
 * Created by PhpStorm.
 * User: andrzej
 * Date: 19.12.17
 * Time: 11:07
 */

namespace AppBundle\Server;


use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class Fibonacci implements ConsumerInterface
{

    /**
     * @param AMQPMessage $msg The message
     * @return mixed false to reject and requeue, any other value to acknowledge
     */
    public function execute(AMQPMessage $msg)
    {

        $number = $msg->getBody();
        $result = $this->getFib($number);
        return $result;
    }
    private  function getFib($n)
    {
        return round((((sqrt(5) + 1) / 2) ** $n) / sqrt(5));
    }
}