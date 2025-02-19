<?php

declare(strict_types=1);

namespace Wearesho\Delivery\TurboSms\Tests\Console;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wearesho\Delivery\TurboSms;
use Wearesho\Delivery;

class Command extends Console\Command\Command
{
    protected static $defaultName = 'balance';

    protected TurboSms\Service $client;

    protected function configure()
    {
        $this->setProcessTitle("TurboSMS");
        $this->setDescription('TurboSMS Check balance');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->client = TurboSms\Service::instance();
        try {
            $balance = $this->client->balance();
        } catch (Delivery\Exception $exception) {
            $message = $exception instanceof TurboSms\Exception ? $exception->responseStatus : $exception->getMessage();
            $output->writeln("Failed with code {$exception->getCode()}: {$message}");
            return Command::FAILURE;
        }

        $output->writeln("Баланс: " . $balance);

        return Command::SUCCESS;
    }
}
