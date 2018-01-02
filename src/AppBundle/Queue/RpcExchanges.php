<?php
/**
 * Created by PhpStorm.
 * User: andrzej
 * Date: 31.12.17
 * Time: 13:43
 */

namespace AppBundle\Queue;


use Humus\Amqp\Exchange;

class RpcExchanges
{
    public function __construct()
    {
        $this->exchanges = [];
    }

    public function addExchange($name, Exchange $exchange)
    {
        $this->exchanges[$name] = $exchange;
    }

    /**
     * @return array
     */
    public function getExchanges()
    {
        return $this->exchanges;
    }

}