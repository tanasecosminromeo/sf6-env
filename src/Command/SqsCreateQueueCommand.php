<?php

namespace App\Command;

use AsyncAws\Core\Configuration;
use AsyncAws\Sqs\SqsClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sqs:create-queue',
    description: 'Creates the SQS queue if it does not exist',
)]
class SqsCreateQueueCommand extends Command
{
    private string $accessKey;
    private string $secretKey;
    private string $region;

    public function __construct(string $accessKey, string $secretKey, string $region)
    {
        parent::__construct();
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->region = $region;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->section('SQS Queue Setup');
        
        // Create SQS client
        $sqsClient = new SqsClient([
            'accessKeyId' => $this->accessKey,
            'accessKeySecret' => $this->secretKey,
            'region' => $this->region,
        ]);
        
        $queueName = 'sf6-messages-test';
        $io->info("Checking if queue '$queueName' exists...");
        
        try {
            // Try to get queue URL - will throw exception if queue doesn't exist
            $result = $sqsClient->getQueueUrl([
                'QueueName' => $queueName,
            ]);
            
            $io->success("Queue '$queueName' already exists at: " . $result->getQueueUrl());
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->warning("Queue does not exist: " . $e->getMessage());
            
            try {
                $io->info("Creating queue '$queueName'...");
                $result = $sqsClient->createQueue([
                    'QueueName' => $queueName,
                    'Attributes' => [
                        'DelaySeconds' => '0',
                        'MessageRetentionPeriod' => '86400', // 1 day
                    ],
                ]);
                
                $io->success("Queue created successfully: " . $result->getQueueUrl());
                return Command::SUCCESS;
            } catch (\Exception $e) {
                $io->error("Failed to create queue: " . $e->getMessage());
                return Command::FAILURE;
            }
        }
    }
}
