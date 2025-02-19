<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateHydratorsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate the Doctrine ORM document hydrators to your cache directory.
 *
 * @internal since version 4.7.0
 */
class GenerateHydratorsDoctrineODMCommand extends GenerateHydratorsCommand
{
    /** @return void */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('doctrine:mongodb:generate:hydrators')
            ->addOption('dm', null, InputOption::VALUE_OPTIONAL, 'The document manager to use for this command.')
            ->setHelp(<<<'EOT'
The <info>doctrine:mongodb:generate:hydrators</info> command generates hydrator classes for your documents:

  <info>./app/console doctrine:mongodb:generate:hydrators</info>

You can specify the document manager you want to generate the hydrators for:

  <info>./app/console doctrine:mongodb:generate:hydrators --dm=name</info>
EOT
        );
    }

    /** @return int */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        DoctrineODMCommand::setApplicationDocumentManager($this->getApplication(), $input->getOption('dm'));

        return parent::execute($input, $output);
    }
}
