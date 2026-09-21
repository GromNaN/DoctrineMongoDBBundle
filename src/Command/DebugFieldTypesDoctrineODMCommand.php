<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function assert;
use function ksort;
use function sprintf;

/**
 * Show the registered field types of a document manager.
 *
 * @internal
 */
final class DebugFieldTypesDoctrineODMCommand extends DoctrineODMCommand
{
    protected function configure(): void
    {
        $this
            ->setName('doctrine:mongodb:debug-field-types')
            ->addOption('dm', null, InputOption::VALUE_OPTIONAL, 'The document manager to use for this command.')
            ->setDescription('List the field types registered on a document manager.')
            ->setHelp(<<<'EOT'
The <info>doctrine:mongodb:debug-field-types</info> command lists the field types
registered on a document manager, resolving container-backed types lazily.

  <info>./bin/console doctrine:mongodb:debug-field-types</info>

Pick a specific document manager with the <info>--dm</info> option:

  <info>./bin/console doctrine:mongodb:debug-field-types --dm=default</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $managerName     = $input->getOption('dm') ?? $this->getManagerRegistry()->getDefaultManagerName();
        $documentManager = $this->getManagerRegistry()->getManager($managerName);
        assert($documentManager instanceof DocumentManager);

        $types = [];
        foreach ($documentManager->getConfiguration()->getTypeProvider() as $name => $type) {
            $types[$name] = $type::class;
        }

        ksort($types);

        $output->writeln(sprintf('Field types of document manager <info>%s</info>:', $managerName));

        foreach ($types as $name => $class) {
            $output->writeln(sprintf('  <info>%-20s</info> %s', $name, $class));
        }

        return 0;
    }
}
