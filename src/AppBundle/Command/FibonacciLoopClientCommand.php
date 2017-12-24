<?php

namespace AppBundle\Command;

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
            for ($i = 1; $i < 50; $i++) {

                $requestId = \uniqid("{$i}_", false);
                $client = $this->getContainer()->get('old_sound_rabbit_mq.fibonacci_rpc');
                $client->addRequest($i, 'symfony-rpc', $requestId);
                $this->logger->info("Send request '{$requestId}'", [
                    'request' => $i,
                    'requestId' => $requestId,
                ]);
                $replies = $client->getReplies();
                $response = $replies[$requestId];
                $this->logger->info("Getting replies '{$response}' for request '{$requestId}'", [
                    'response' => $replies[$requestId],
                    'request' => $i
                ]);

            }
            \sleep(5);

        }

    }
}
