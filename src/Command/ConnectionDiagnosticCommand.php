<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\Bundle\MongoDBBundle\DataCollector\ConnectionDiagnostic;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Throwable;

use function array_diff;
use function array_keys;
use function implode;
use function sprintf;

/** @internal */
#[AsCommand(
    name: 'doctrine:mongodb:connection:diagnostic',
    description: 'Diagnose MongoDB configuration and server capabilities for each connection.',
)]
final class ConnectionDiagnosticCommand extends Command
{
    /** @param ServiceProviderInterface<ConnectionDiagnostic> $diagnostics */
    public function __construct(private readonly ServiceProviderInterface $diagnostics)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('connection', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The name of the connection to diagnose. If not specified, all connections will be diagnosed.', [], $this->getConnectionNames(...));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        return Command::SUCCESS;
    }

    /** @return list<string> */
    private function getConnectionNames(): array
    {
        return array_keys($this->diagnostics->getProvidedServices());
    }
}
