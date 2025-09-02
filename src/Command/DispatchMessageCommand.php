<?php

namespace App\Command;

use App\Message\ExampleMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:dispatch-message',
    description: 'Dispatches an example message to test the messenger system',
)]
class DispatchMessageCommand extends Command
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        parent::__construct();
        $this->messageBus = $messageBus;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('content', InputArgument::OPTIONAL, 'Message content', 'Default test message');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $content = $input->getArgument('content');

        $message = new ExampleMessage($content);
        $this->messageBus->dispatch($message);

        $io->success(sprintf('Message dispatched: "%s"', $content));

        return Command::SUCCESS;
    }
}
