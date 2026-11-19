<?php

namespace Dconco\SchemaMigrator;

use Dconco\SchemaMigrator\Commands\InitCommand;
use Dconco\SchemaMigrator\Commands\MakeCommand;
use Dconco\SchemaMigrator\Commands\MigrateCommand;
use Dconco\SchemaMigrator\Commands\RollbackCommand;
use Dconco\SchemaMigrator\Commands\StatusCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class SchemaMigratorApplication extends Application
{
    public function __construct()
    {
        parent::__construct('Schema Migrator', '1.1.0');

        $this->add(new InitCommand());
        $this->add(new MakeCommand());
        $this->add(new MigrateCommand());
        $this->add(new RollbackCommand());
        $this->add(new StatusCommand());
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(
            new InputOption('--config', '-c', InputOption::VALUE_REQUIRED, 'Path to configuration file')
        );

        return $definition;
    }

    public function getLongVersion(): string
    {
        return sprintf(
            '<info>%s</info> version <comment>%s</comment> by <info>dconco</info>',
            $this->getName(),
            $this->getVersion()
        );
    }
}
