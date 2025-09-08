<?php

namespace Dconco\SchemaMigrator\Commands;

use Dconco\SchemaMigrator\MigrationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StatusCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('migrate:status')
            ->setDescription('Show the status of migrations')
            ->setHelp('This command shows which migrations have been executed and which are pending.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        if (!$this->initializeDatabase($input, $output)) {
            return Command::FAILURE;
        }

        try {
            $io->title('📊 Migration Status');
            
            $allFiles = MigrationManager::getMigrationFiles();
            $executed = MigrationManager::getExecutedMigrations();
            $pending = MigrationManager::getPendingMigrations();
            
            if (empty($allFiles)) {
                $io->warning('No migration files found.');
                return Command::SUCCESS;
            }

            $rows = [];
            foreach ($allFiles as $file) {
                $filename = basename($file, '.php');
                $status = in_array($filename, $executed) ? '<info>✅ Executed</info>' : '<comment>⏳ Pending</comment>';
                $rows[] = [$filename, $status];
            }
            
            $io->table(['Migration', 'Status'], $rows);
            
            $io->section('Summary');
            $io->writeln(sprintf('📄 Total migrations: <info>%d</info>', count($allFiles)));
            $io->writeln(sprintf('✅ Executed: <info>%d</info>', count($executed)));
            $io->writeln(sprintf('⏳ Pending: <comment>%d</comment>', count($pending)));
            
            if (!empty($pending)) {
                $io->note('Run "schema-migrator migrate" to execute pending migrations.');
            }
            
        } catch (\Exception $e) {
            $io->error('Failed to get status: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}