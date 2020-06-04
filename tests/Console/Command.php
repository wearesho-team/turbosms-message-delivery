<?php

namespace Wearesho\Delivery\TurboSms\Tests\Console;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wearesho\Delivery\TurboSms;

class Command extends Console\Command\Command
{
    protected static $defaultName = 'client';

    protected TurboSms\Service $client;

    protected function configure()
    {
        $this->setDescription('TurboSMS Client');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->client = TurboSms\Service::instance();
        $output->writeln($this->client->auth());
        $balance = $this->client->balance();
        $output->writeln("Баланс: " . $balance);

        return Command::SUCCESS;
    }
}
