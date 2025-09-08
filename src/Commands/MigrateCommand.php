<?php

namespace Dconco\SchemaMigrator\Commands;

use Dconco\SchemaMigrator\MigrationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('migrate')
            ->setDescription('Run pending database migrations')
            ->setHelp('This command runs all pending migrations in your migrations directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        if (!$this->initializeDatabase($input, $output)) {
            return Command::FAILURE;
        }

        try {
            $io->title('🔄 Running Database Migrations');
            
            $pending = MigrationManager::getPendingMigrations();
            
            if (empty($pending)) {
                $io->success('No pending migrations found. Database is up to date!');
                return Command::SUCCESS;
            }

            $io->writeln(sprintf('Found <info>%d</info> pending migrations:', count($pending)));
            
            foreach ($pending as $file) {
                $io->writeln('  📄 ' . basename($file, '.php'));
            }
            
            $io->newLine();
            
            $executed = MigrationManager::runMigrations();
            
            if (!empty($executed)) {
                $io->success(sprintf('Successfully executed %d migrations:', count($executed)));
                
                foreach ($executed as $migration) {
                    $io->writeln('  ✅ ' . $migration);
                }
            }
            
        } catch (\Exception $e) {
            $io->error('Migration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}