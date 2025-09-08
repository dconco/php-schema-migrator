<?php

namespace Dconco\SchemaMigrator\Commands;

use Dconco\SchemaMigrator\MigrationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RollbackCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('migrate:rollback')
            ->setDescription('Rollback database migrations')
            ->addOption('steps', 's', InputOption::VALUE_OPTIONAL, 'Number of migration batches to rollback', 1)
            ->setHelp('This command rolls back the last batch of migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        if (!$this->initializeDatabase($input, $output)) {
            return Command::FAILURE;
        }

        try {
            $io->title('⏪ Rolling Back Database Migrations');
            
            $steps = (int) $input->getOption('steps');
            
            $rolledBack = [];
            for ($i = 0; $i < $steps; $i++) {
                $batch = MigrationManager::rollbackMigrations();
                if (empty($batch)) {
                    break;
                }
                $rolledBack = array_merge($rolledBack, $batch);
            }
            
            if (empty($rolledBack)) {
                $io->warning('No migrations to rollback.');
                return Command::SUCCESS;
            }

            $io->success(sprintf('Successfully rolled back %d migrations:', count($rolledBack)));
            
            foreach ($rolledBack as $migration) {
                $io->writeln('  ↩️  ' . $migration);
            }
            
        } catch (\Exception $e) {
            $io->error('Rollback failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}