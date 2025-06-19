<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/** @internal */
#[AsCommand(
    name: 'doctrine:mongodb:connection:diagnostic',
    description: 'Diagnose MongoDB configuration and server capabilities for each connection.',
)]
final class ConnectionDiagnosticCommand extends Command
{
    public function __construct(private readonly ServiceProviderInterface $diagnostics)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
