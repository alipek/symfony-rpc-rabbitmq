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

                $requestId = \uniqid("{$i}", false);
                $client = $this->getContainer()->get('old_sound_rabbit_mq.fibonacci_rpc');
                $client->addRequest($i, 'symfony-rpc', $requestId);
                $client->addRequest($i + 1, 'symfony-rpc', $requestId . '2');
                $this->logger->info("Send request '{request}'", [
                    'request' => $i,
                    'requestId' => $requestId,
                ]);
                $response = $client->getReplies();
                $this->logger->info("Getting response '{response}' for request '{request}'", [
                    'response' => $response[$requestId],
                    'response2' => $response[$requestId . '2'],
                    'request' => $i,
                    'requestId' => $requestId,
                ]);

            }
            \sleep(5);

        }

    }
}
