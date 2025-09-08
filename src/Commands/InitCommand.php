<?php

namespace Dconco\SchemaMigrator\Commands;

use Dconco\SchemaMigrator\ConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Initialize Schema Migrator in the current project')
            ->setHelp('This command creates a configuration file and migrations directory for Schema Migrator.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🚀 Schema Migrator Initialization');
        
        if (file_exists(ConfigManager::getConfigPath())) {
            $io->warning('Schema Migrator is already initialized in this directory.');
            return Command::SUCCESS;
        }

        $io->section('Database Configuration');
        
        $helper = $this->getHelper('question');
        
        $config = ConfigManager::getDefaultConfig();
        
        // Ask for database configuration
        $questions = [
            'driver' => new Question('Database driver (mysql/pgsql/sqlite): ', 'mysql'),
            'host' => new Question('Database host: ', '127.0.0.1'),
            'port' => new Question('Database port: ', '3306'),
            'database' => new Question('Database name: '),
            'username' => new Question('Database username: '),
            'password' => new Question('Database password: '),
        ];
        
        $questions['password']->setHidden(true);
        $questions['password']->setHiddenFallback(false);
        
        foreach ($questions as $key => $question) {
            $config['database'][$key] = $helper->ask($input, $output, $question) ?? $config['database'][$key];
        }

        // Save configuration
        ConfigManager::saveConfig($config);
        
        // Create migrations directory
        ConfigManager::ensureMigrationsDirectory();
        
        $io->success([
            'Schema Migrator initialized successfully!',
            'Configuration saved to: ' . ConfigManager::getConfigPath(),
            'Migrations directory created: ' . ConfigManager::getMigrationsPath(),
        ]);
        
        $io->note([
            'Next steps:',
            '1. Edit schema-migrator.yml to fine-tune your configuration',
            '2. Create your first migration: schema-migrator make:migration CreateUsersTable',
            '3. Run migrations: schema-migrator migrate',
        ]);

        return Command::SUCCESS;
    }
}