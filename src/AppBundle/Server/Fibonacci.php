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
use Psr\Log\LoggerInterface;

class Fibonacci implements ConsumerInterface
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @param AMQPMessage $msg The message
     * @return mixed false to reject and requeue, any other value to acknowledge
     */
    public function execute(AMQPMessage $msg)
    {

        $start = \microtime(true);
        $number = $msg->getBody();
        $args = \json_decode($number);
        $result = \call_user_func_array([$this, 'getFib'], $args);
        $time = \microtime(true) - $start;
        $correlationId = $msg->get('correlation_id');
        $this->logger->info(sprintf("Response time: %.6f for request id:{$correlationId}, param:{$number}",$time), [
            'time' => $time,
        ]);
        return [
            'result' => $result,
        ];
    }
    private  function getFib($n)
    {
        return round((((sqrt(5) + 1) / 2) ** $n) / sqrt(5));
    }
}