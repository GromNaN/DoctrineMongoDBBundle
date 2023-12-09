<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Command;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function assert;
use function sprintf;
use function str_replace;
use function strtolower;
use function trigger_deprecation;

use const DIRECTORY_SEPARATOR;

/**
 * Base class for Doctrine ODM console commands to extend.
 *
 * @internal since version 5.0
 */
abstract class DoctrineODMCommand extends Command
{
    /** @var ContainerInterface|null */
    protected $container;

    private ?ManagerRegistry $managerRegistry;

    public function __construct(?ManagerRegistry $registry = null)
    {
        parent::__construct();

        $this->managerRegistry = $registry;
    }

    /** @param string $dmName */
    public static function setApplicationDocumentManager(Application $application, $dmName)
    {
        $dm        = $application->getKernel()->getContainer()->get('doctrine_mongodb')->getManager($dmName);
        $helperSet = $application->getHelperSet();
        $helperSet->set(new DocumentManagerHelper($dm), 'dm');
    }

    /**
     * @internal
     *
     * @return ManagerRegistry
     */
    protected function getManagerRegistry()
    {
        return $this->managerRegistry;
    }
}
