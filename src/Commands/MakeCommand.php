<?php

namespace Dconco\SchemaMigrator\Commands;

use Dconco\SchemaMigrator\MigrationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('make:migration')
            ->setDescription('Create a new migration file')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the migration')
            ->setHelp('This command creates a new migration file in the migrations directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $name = $input->getArgument('name');
        
        // Convert PascalCase to snake_case
        $name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
        
        // Remove 'table' from the end if present
        if (str_ends_with($name, '_table')) {
            $name = substr($name, 0, -6);
        }

        try {
            $path = MigrationManager::createMigration($name);
            $filename = basename($path);
            
            $io->success("Migration created successfully!");
            $io->writeln("📄 <info>{$filename}</info>");
            $io->writeln("📁 <comment>{$path}</comment>");
            
            $io->note([
                'Next steps:',
                '1. Edit the migration file to define your schema changes',
                '2. Run migrations: schema-migrator migrate',
            ]);
            
        } catch (\Exception $e) {
            $io->error('Failed to create migration: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}