<?php
/**
 * Created by PhpStorm.
 * User: andrzej
 * Date: 31.12.17
 * Time: 12:41
 */

namespace AppBundle\Client;


use GuzzleHttp\Promise\PromiseInterface;

interface Fibonacci
{
    public function fibonacci(int $num): PromiseInterface;
}