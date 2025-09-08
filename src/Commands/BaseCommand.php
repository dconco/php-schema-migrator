<?php

namespace Dconco\SchemaMigrator\Commands;

use Dconco\SchemaMigrator\ConfigManager;
use Dconco\SchemaMigrator\DatabaseManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{
    protected function initializeDatabase(InputInterface $input, OutputInterface $output): bool
    {
        if (!DatabaseManager::isConfigured()) {
            $output->writeln('<error>Project not initialized. Run "schema-migrator init" first.</error>');
            return false;
        }

        try {
            $config = ConfigManager::loadConfig();
            DatabaseManager::initialize($config['database']);
            return true;
        } catch (\Exception $e) {
            $output->writeln('<error>Database connection failed: ' . $e->getMessage() . '</error>');
            return false;
        }
    }
}