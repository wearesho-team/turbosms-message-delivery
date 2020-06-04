<?php

namespace Wearesho\Delivery\TurboSms\Tests\Console;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class MessageCommand extends Command
{
    protected static $defaultName = 'message';

    protected string $text;
    protected array $recipients = [];

    protected function configure(): void
    {
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $response = $this->client->send($this->text, ...$this->recipients);
        $t = new Table($output);
        $t->addRows(array_map(
            fn($cell) => [array_shift($this->recipients), $cell],
            $response
        ));
        $t->setHeaders(['Phone', 'UUID']);
        $t->render();
        return static::SUCCESS;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);
        $q = new QuestionHelper();
        $question = new Question("Enter SMS text: ", "Hello, World! Привет, Мир!");
        $this->text = $q->ask($input, $output, $question);

        $recipientQuestion = new Question("Recipient: ");
        $recipientQuestion->setValidator(function ($value) {
            if (!preg_match('/^\+?(?:38)?0(\d{9})$/', $value)) {
                throw new \RuntimeException("Invalid Format");
            }
            return $value;
        });
        do {
            $this->recipients[] = $q->ask($input, $output, $recipientQuestion);
        } while ($q->ask($input, $output, new ConfirmationQuestion("One more? [y/n] ")));
    }
}
