<?php

namespace AppBundle\Command;

use AppBundle\Client\Fibonacci;
use AppBundle\Client\FibonacciRpcClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FibonacciLoopClientCommand extends ContainerAwareCommand
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /** @var FibonacciRpcClient */
    protected $client;
    /** @var Fibonacci */
    protected $fibonacci;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:fibonacci:client-loop')
            ->setDescription('Loop client rpc for fibbonaci ');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        while (true) {
            for ($i = 1; $i < 5; $i++) {

                $requestId = \uniqid("{$i}_", false);
                $this->fibonacci = $this->getContainer()->get(Fibonacci::class);
                $this->logger->info("Send request '{$requestId}'", [
                    'request' => $i,
                    'requestId' => $requestId,
                ]);
                $this->fibonacci->fibonacci($i);

            }
            \sleep(5);

        }

    }
}
