<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Configuration;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter\BasicFilter;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter\ComplexFilter;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Filter\DisabledFilter;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Repository\CustomGridFSRepository;
use Doctrine\Bundle\MongoDBBundle\Tests\Fixtures\Repository\CustomRepository;
use Doctrine\ODM\MongoDB\Configuration as ODMConfiguration;
use Doctrine\ODM\MongoDB\Repository\DefaultGridFSRepository;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Yaml\Yaml;

use function array_key_exists;
use function file_get_contents;
use function method_exists;

class ConfigurationTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testDefaults(): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();
        $options       = $processor->processConfiguration($configuration, []);

        $defaults = [
            'auto_generate_hydrator_classes' => false,
            'auto_generate_proxy_classes'    => ODMConfiguration::AUTOGENERATE_EVAL,
            'auto_generate_persistent_collection_classes' => ODMConfiguration::AUTOGENERATE_NEVER,
            'enable_lazy_ghost_objects'      => method_exists(ODMConfiguration::class, 'setUseLazyGhostObject'),
            'default_database'               => 'default',
            'document_managers'              => [],
            'connections'                    => [],
            'proxy_dir'                      => '%kernel.cache_dir%/doctrine/odm/mongodb/Proxies',
            'resolve_target_documents'       => [],
            'proxy_namespace'                => 'MongoDBODMProxies',
            'hydrator_dir'                   => '%kernel.cache_dir%/doctrine/odm/mongodb/Hydrators',
            'hydrator_namespace'             => 'Hydrators',
            'default_commit_options'         => [],
            'persistent_collection_dir'      => '%kernel.cache_dir%/doctrine/odm/mongodb/PersistentCollections',
            'persistent_collection_namespace' => 'PersistentCollections',
            'types'                          => [],
            'controller_resolver'            => [
                'enabled'      => true,
                'auto_mapping' => true,
            ],
        ];

        $this->assertEquals($defaults, $options);
    }

    /** @dataProvider provideFullConfiguration */
    public function testFullConfiguration(array $config): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();
        $options       = $processor->processConfiguration($configuration, [$config]);

        $expected = [
            'auto_generate_hydrator_classes' => 1,
            'auto_generate_proxy_classes'    => ODMConfiguration::AUTOGENERATE_FILE_NOT_EXISTS,
            'auto_generate_persistent_collection_classes' => ODMConfiguration::AUTOGENERATE_EVAL,
            'enable_lazy_ghost_objects'      => method_exists(ODMConfiguration::class, 'setUseLazyGhostObject'),
            'default_connection'             => 'conn1',
            'default_database'               => 'default_db_name',
            'default_document_manager'       => 'default_dm_name',
            'hydrator_dir'                   => '%kernel.cache_dir%/doctrine/odm/mongodb/Test_Hydrators',
            'hydrator_namespace'             => 'Test_Hydrators',
            'proxy_dir'                      => '%kernel.cache_dir%/doctrine/odm/mongodb/Test_Proxies',
            'proxy_namespace'                => 'Test_Proxies',
            'persistent_collection_dir'      => '%kernel.cache_dir%/doctrine/odm/mongodb/Test_Pcolls',
            'persistent_collection_namespace' => 'Test_Pcolls',
            'default_commit_options' => [
                'j' => false,
                'timeout' => 10,
                'w' => 'majority',
                'wtimeout' => 10,
            ],
            'connections' => [
                'conn1' => [
                    'server'  => 'mongodb://localhost',
                    'options' => [
                        'connectTimeoutMS'  => 500,
                        'db'                => 'database_val',
                        'journal'           => true,
                        'password'          => 'password_val',
                        'readPreference'    => 'secondaryPreferred',
                        'readPreferenceTags' => [
                            ['dc' => 'east', 'use' => 'reporting'],
                            ['dc' => 'west'],
                            [],
                        ],
                        'replicaSet'                           => 'foo',
                        'socketTimeoutMS'                      => 1000,
                        'ssl'                                  => true,
                        'tls'                                  => true,
                        'tlsAllowInvalidCertificates'          => false,
                        'tlsAllowInvalidHostnames'             => false,
                        'tlsCAFile'                            => '/path/to/cert.pem',
                        'tlsCertificateKeyFile'                => '/path/to/key.crt',
                        'tlsCertificateKeyFilePassword'        => 'secret',
                        'tlsDisableCertificateRevocationCheck' => false,
                        'tlsDisableOCSPEndpointCheck'          => false,
                        'tlsInsecure'                          => false,
                        'authMechanism'                        => 'MONGODB-X509',
                        'authSource'                           => 'some_db',
                        'username'                             => 'username_val',
                        'retryReads'                           => false,
                        'retryWrites'                          => false,
                        'w'                                    => 'majority',
                        'wTimeoutMS'                           => 1000,
                    ],
                    'driver_options' => ['context' => 'conn1_context_service'],
                ],
                'conn2' => ['server' => 'mongodb://otherhost'],
            ],
            'document_managers' => [
                'dm1' => [
                    'default_document_repository_class' => DocumentRepository::class,
                    'default_gridfs_repository_class' => DefaultGridFSRepository::class,
                    'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory',
                    'persistent_collection_factory' => null,
                    'logging'      => '%kernel.debug%',
                    'auto_mapping' => false,
                    'filters' => [
                        'disabled_filter' => [
                            'class' => DisabledFilter::class,
                            'enabled' => false,
                            'parameters' => [],
                        ],
                        'basic_filter' => [
                            'class' => BasicFilter::class,
                            'enabled' => true,
                            'parameters' => [],
                        ],
                        'complex_filter' => [
                            'class' => ComplexFilter::class,
                            'enabled' => true,
                            'parameters' => [
                                'integer' => 1,
                                'string' => 'foo',
                                'object' => ['key' => 'value'],
                                'array' => [1, 2, 3],
                            ],
                        ],
                    ],
                    'metadata_cache_driver' => [
                        'type'           => 'memcached',
                        'class'          => 'fooClass',
                        'host'           => 'host_val',
                        'port'           => 1234,
                        'instance_class' => 'instance_val',
                    ],
                    'mappings' => [
                        'FooBundle' => [
                            'type'    => 'attribute',
                            'mapping' => true,
                        ],
                    ],
                    'profiler' => [
                        'enabled' => true,
                        'pretty'  => false,
                    ],
                    'use_transactional_flush' => false,
                ],
                'dm2' => [
                    'connection'   => 'dm2_connection',
                    'database'     => 'db1',
                    'logging'      => true,
                    'default_document_repository_class' => CustomRepository::class,
                    'default_gridfs_repository_class' => CustomGridFSRepository::class,
                    'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory',
                    'persistent_collection_factory' => null,
                    'auto_mapping' => false,
                    'filters'      => [],
                    'metadata_cache_driver' => ['type' => 'apcu'],
                    'mappings' => [
                        'BarBundle' => [
                            'type'      => 'xml',
                            'dir'       => '%kernel.cache_dir%',
                            'prefix'    => 'prefix_val',
                            'alias'     => 'alias_val',
                            'is_bundle' => false,
                            'mapping'   => true,
                        ],
                    ],
                    'profiler' => [
                        'enabled' => '%kernel.debug%',
                        'pretty'  => '%kernel.debug%',
                    ],
                    'use_transactional_flush' => false,
                ],
            ],
            'resolve_target_documents' => ['Foo\BarInterface' => 'Bar\FooClass'],
            'types' => [],
            'controller_resolver' => [
                'enabled'      => true,
                'auto_mapping' => true,
            ],
        ];

        $this->assertEquals($expected, $options);
    }

    /** @return array<mixed[]> */
    public static function provideFullConfiguration(): array
    {
        $yaml = Yaml::parse(file_get_contents(__DIR__ . '/Fixtures/config/yml/full.yml'));
        $yaml = $yaml['doctrine_mongodb'];

        $xml = XmlUtils::loadFile(__DIR__ . '/Fixtures/config/xml/full.xml');
        $xml = XmlUtils::convertDomElementToArray($xml->getElementsByTagName('config')->item(0));

        return [
            [$yaml],
            [$xml],
        ];
    }

    /**
     * @param array $configs  An array of configuration arrays to process
     * @param array $expected Array of key/value options expected in the processed configuration
     *
     * @dataProvider provideMergeOptions
     */
    public function testMergeOptions(array $configs, array $expected): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();
        $options       = $processor->processConfiguration($configuration, $configs);

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $options[$key]);
        }
    }

    /** @return array<mixed[]> */
    public static function provideMergeOptions(): array
    {
        $cases = [];

        // single config, testing normal option setting
        $cases[] = [
            [
                ['default_document_manager' => 'foo'],
            ],
            ['default_document_manager' => 'foo'],
        ];

        // single config, testing normal option setting with dashes
        $cases[] = [
            [
                ['default-document-manager' => 'bar'],
            ],
            ['default_document_manager' => 'bar'],
        ];

        // testing the normal override merging - the later config array wins
        $cases[] = [
            [
                ['default_document_manager' => 'foo'],
                ['default_document_manager' => 'baz'],
            ],
            ['default_document_manager' => 'baz'],
        ];

        // the "options" array is totally replaced
        $cases[] = [
            [
                ['connections' => ['default' => ['options' => ['socketTimeoutMS' => 2000]]]],
                ['connections' => ['default' => ['options' => ['username' => 'foo']]]],
            ],
            ['connections' => ['default' => ['options' => ['username' => 'foo']]]],
        ];

        // mappings are merged non-recursively.
        $cases[] = [
            [
                ['document_managers' => ['default' => ['mappings' => ['foomap' => ['type' => 'val1'], 'barmap' => ['dir' => 'val2']]]]],
                ['document_managers' => ['default' => ['mappings' => ['barmap' => ['prefix' => 'val3']]]]],
            ],
            ['document_managers' => ['default' => ['metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null, 'filters' => [], 'mappings' => ['foomap' => ['type' => 'val1', 'mapping' => true], 'barmap' => ['prefix' => 'val3', 'mapping' => true]], 'use_transactional_flush' => false]]],
        ];

        // connections are merged non-recursively.
        $cases[] = [
            [
                ['connections' => ['foocon' => ['server' => 'val1'], 'barcon' => ['options' => ['username' => 'val2']]]],
                ['connections' => ['barcon' => ['server' => 'val3']]],
            ],
            [
                'connections' => [
                    'foocon' => ['server' => 'val1'],
                    'barcon' => ['server' => 'val3'],
                ],
            ],
        ];

        // connection options are merged non-recursively.
        $cases[] = [
            [
                ['connections' => ['foocon' => ['options' => ['db' => 'val1']]]],
                ['connections' => ['foocon' => ['options' => ['replicaSet' => 'val2']]]],
            ],
            [
                'connections' => [
                    'foocon' => ['options' => ['replicaSet' => 'val2']],
                ],
            ],
        ];

        // connection option readPreferenceTags are merged non-recursively.
        $cases[] = [
            [
                ['connections' => ['foocon' => ['options' => ['readPreferenceTags' => [['dc' => 'east', 'use' => 'reporting']]]]]],
                ['connections' => ['foocon' => ['options' => ['readPreferenceTags' => [['dc' => 'west'], []]]]]],
            ],
            [
                'connections' => [
                    'foocon' => ['options' => ['readPreferenceTags' => [['dc' => 'west'], []]]],
                ],
            ],
        ];

        // managers are merged non-recursively.
        $cases[] = [
            [
                ['document_managers' => ['foodm' => ['database' => 'val1'], 'bardm' => ['database' => 'val2']]],
                ['document_managers' => ['bardm' => ['database' => 'val3']]],
            ],
            [
                'document_managers' => [
                    'foodm' => ['database' => 'val1', 'metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null, 'filters' => [], 'mappings' => [], 'use_transactional_flush' => false],
                    'bardm' => ['database' => 'val3', 'metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null, 'filters' => [], 'mappings' => [], 'use_transactional_flush' => false],
                ],
            ],
        ];

        return $cases;
    }

    /**
     * @param array $configs  A configuration array to process
     * @param array $expected Array of key/value options expected in the processed configuration
     *
     * @dataProvider provideNormalizeOptions
     */
    public function testNormalizeOptions(array $config, array $expected): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();
        $options       = $processor->processConfiguration($configuration, [$config]);

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $options[$key]);
        }
    }

    /** @return array<mixed[]> */
    public static function provideNormalizeOptions(): array
    {
        $cases = [];

        // connection versus connections (id is the identifier)
        $cases[] = [
            [
                'connection' => [
                    ['server' => 'mongodb://abc', 'id' => 'foo'],
                    ['server' => 'mongodb://def', 'id' => 'bar'],
                ],
            ],
            [
                'connections' => [
                    'foo' => ['server' => 'mongodb://abc'],
                    'bar' => ['server' => 'mongodb://def'],
                ],
            ],
        ];

        // document_manager versus document_managers (id is the identifier)
        $cases[] = [
            [
                'document_manager' => [
                    ['connection' => 'conn1', 'id' => 'foo'],
                    ['connection' => 'conn2', 'id' => 'bar'],
                ],
            ],
            [
                'document_managers' => [
                    'foo' => ['connection' => 'conn1', 'metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null, 'filters' => [], 'mappings' => [], 'use_transactional_flush' => false],
                    'bar' => ['connection' => 'conn2', 'metadata_cache_driver' => ['type' => 'array'], 'logging' => '%kernel.debug%', 'profiler' => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'], 'auto_mapping' => false, 'default_document_repository_class' => DocumentRepository::class, 'default_gridfs_repository_class' => DefaultGridFSRepository::class, 'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory', 'persistent_collection_factory' => null,'filters' => [], 'mappings' => [], 'use_transactional_flush' => false],
                ],
            ],
        ];

        // mapping configuration that's beneath a specific document manager
        $cases[] = [
            [
                'document_manager' => [
                    [
                        'id' => 'foo',
                        'connection' => 'conn1',
                        'mapping' => [
                            'type' => 'xml',
                            'name' => 'foo-mapping',
                        ],
                    ],
                ],
            ],
            [
                'document_managers' => [
                    'foo' => [
                        'connection'   => 'conn1',
                        'metadata_cache_driver' => ['type' => 'array'],
                        'default_document_repository_class' =>  DocumentRepository::class,
                        'default_gridfs_repository_class' => DefaultGridFSRepository::class,
                        'repository_factory' => 'doctrine_mongodb.odm.container_repository_factory',
                        'persistent_collection_factory' => null,
                        'mappings'     => ['foo-mapping' => ['type' => 'xml', 'mapping' => true]],
                        'logging'      => '%kernel.debug%',
                        'profiler'     => ['enabled' => '%kernel.debug%', 'pretty' => '%kernel.debug%'],
                        'auto_mapping' => false,
                        'filters'      => [],
                        'use_transactional_flush' => false,
                    ],
                ],
            ],
        ];

        return $cases;
    }

    public function testPasswordAndUsernameShouldBeUnsetIfNull(): void
    {
        $config = [
            'connections' => [
                'conn1' => [
                    'server' => 'mongodb://localhost',
                    'options' => [
                        'username' => null,
                        'password' => 'bar',
                    ],
                ],
                'conn2' => [
                    'server' => 'mongodb://localhost',
                    'options' => [
                        'username' => 'foo',
                        'password' => null,
                    ],
                ],
                'conn3' => [
                    'server' => 'mongodb://localhost',
                    'options' => [
                        'username' => null,
                        'password' => null,
                    ],
                ],
            ],
        ];

        $processor     = new Processor();
        $configuration = new Configuration();
        $options       = $processor->processConfiguration($configuration, [$config]);

        $this->assertEquals(['password' => 'bar'], $options['connections']['conn1']['options']);
        $this->assertEquals(['username' => 'foo'], $options['connections']['conn2']['options']);
        $this->assertEquals([], $options['connections']['conn3']['options']);
    }

    public function testInvalidReplicaSetValue(): void
    {
        $config = [
            'connections' => [
                'conn1' => [
                    'server'  => 'mongodb://localhost',
                    'options' => ['replicaSet' => true],
                ],
            ],
        ];

        $processor     = new Processor();
        $configuration = new Configuration();

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The replicaSet option must be a string');

        $processor->processConfiguration($configuration, [$config]);
    }

    public function testNullReplicaSetValue(): void
    {
        $config = [
            'connections' => [
                'conn1' => [
                    'server'  => 'mongodb://localhost',
                    'options' => ['replicaSet' => null],
                ],
            ],
        ];

        $processor       = new Processor();
        $configuration   = new Configuration();
        $processedConfig = $processor->processConfiguration($configuration, [$config]);
        $this->assertFalse(array_key_exists('replicaSet', $processedConfig['connections']['conn1']['options']));
    }

    protected function processConfiguration(array $config): array
    {
        $processor = new Processor();
        $configuration = new Configuration();

        return $processor->processConfiguration($configuration, [$this->getMinimalValidConfig($config)]);
    }

    protected function getMinimalValidConfig(array $config = []): array
    {
        $baseConfig = [
            'connections' => [
                'default' => [
                    'driver_options' => [], // Placeholder for autoEncryption or other options
                ],
            ],
            'document_managers' => [
                'default' => [],
            ],
        ];

        // Deep merge config into baseConfig
        if (isset($config['connections']['default']['driver_options'])) {
            $baseConfig['connections']['default']['driver_options'] = array_merge(
                $baseConfig['connections']['default']['driver_options'],
                $config['connections']['default']['driver_options']
            );
            unset($config['connections']['default']['driver_options']);
        }

        if (isset($config['connections']['default'])) {
            $baseConfig['connections']['default'] = array_merge(
                $baseConfig['connections']['default'],
                $config['connections']['default']
            );
            unset($config['connections']['default']);
        }
        if (isset($config['connections'])) {
            $baseConfig['connections'] = array_merge(
                $baseConfig['connections'],
                $config['connections']
            );
            unset($config['connections']);
        }


        return array_merge($baseConfig, $config);
    }

    private function assertProcessedConfigurationEquals(array $expected, array $config): void
    {
        $this->assertEquals($expected, $this->processConfiguration($config));
    }

    private function assertConfigurationIsInvalid(array $config, ?string $expectedMessage = null): void
    {
        $this->expectException(InvalidConfigurationException::class);
        if ($expectedMessage !== null) {
            $this->expectExceptionMessageMatches($expectedMessage);
        }
        $this->processConfiguration($config);
    }

    public function testBasicValidAutoEncryptionConfig(): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'encryption.__keyVault',
                            'kmsProviders' => ['local' => ['key' => base64_encode(random_bytes(96))]],
                            'bypassAutoEncryption' => false,
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionWithKeyVaultClientService(): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.collection',
                            'keyVaultClient' => 'my_key_vault_client_service',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']], // "password" in base64
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionValidKeyVaultNamespace(): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'mydb.mycollection',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionInvalidKeyVaultNamespace(): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'invalid_namespace_format',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertConfigurationIsInvalid(
            $config,
            '/Invalid keyVaultNamespace format. It should be "database.collection"/'
        );
    }

    public function testAutoEncryptionWithVariousKmsProviders(): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => [
                                'aws' => ['accessKeyId' => 'keyId', 'secretAccessKey' => 'secret'],
                                'azure' => ['tenantId' => 'tenant', 'clientId' => 'client', 'clientSecret' => 'secret'],
                                'gcp' => ['email' => 'name@example.com', 'privateKey' => 'key'],
                                'kmip' => ['endpoint' => 'localhost:5696'],
                                'local' => ['key' => 'cGFzc3dvcmQ='],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionWithEmptyKmsProviders(): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => [],
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionWithSchemaMap(): void
    {
        $schemaMap = [
            'db.coll' => [
                'bsonType' => 'object',
                'properties' => [
                    'encryptedField' => [
                        'encrypt' => [
                            'keyId' => '/dataKeyId',
                            'bsonType' => 'string',
                            'algorithm' => 'AEAD_AES_256_CBC_HMAC_SHA_512-Deterministic',
                        ],
                    ],
                ],
            ],
        ];
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                            'schemaMap' => $schemaMap,
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $expected['connections']['default']['driver_options']['autoEncryption']['schemaMap'] = $schemaMap;
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionWithEncryptedFieldsMap(): void
    {
        $encryptedFieldsMap = [
            'db.coll' => [
                'fields' => [
                    [
                        'path' => 'encryptedField',
                        'keyId' => '/dataKeyId',
                        'bsonType' => 'string',
                    ],
                ],
            ],
        ];
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                            'encryptedFieldsMap' => $encryptedFieldsMap,
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $expected['connections']['default']['driver_options']['autoEncryption']['encryptedFieldsMap'] = $encryptedFieldsMap;
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionWithSchemaAndEncryptedFieldsMap(): void
    {
        $schemaMap = ['db.coll' => ['bsonType' => 'object', 'properties' => ['foo' => []]]];
        $encryptedFieldsMap = ['db.coll' => ['fields' => [['path' => 'bar']]]];
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                            'schemaMap' => $schemaMap,
                            'encryptedFieldsMap' => $encryptedFieldsMap,
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $expected['connections']['default']['driver_options']['autoEncryption']['schemaMap'] = $schemaMap;
        $expected['connections']['default']['driver_options']['autoEncryption']['encryptedFieldsMap'] = $encryptedFieldsMap;
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionWithEmptySchemaAndEncryptedFieldsMap(): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                            'schemaMap' => [],
                            'encryptedFieldsMap' => [],
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $expected['connections']['default']['driver_options']['autoEncryption']['schemaMap'] = [];
        $expected['connections']['default']['driver_options']['autoEncryption']['encryptedFieldsMap'] = [];
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    /** @dataProvider provideBooleanFlags */
    public function testAutoEncryptionBooleanFlags(string $flagName, bool $value): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                            $flagName => $value,
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public static function provideBooleanFlags(): array
    {
        return [
            ['bypassAutoEncryption', true],
            ['bypassAutoEncryption', false],
            ['bypassQueryAnalysis', true],
            ['bypassQueryAnalysis', false],
        ];
    }

    public function testAutoEncryptionExtraOptions(): void
    {
        $extraOptions = [
            'mongocryptdSpawnPath' => '/opt/mongodb/mongocryptd',
            'mongocryptdBypassSpawn' => true,
            'cryptSharedLibPath' => '/usr/local/lib/mongo_crypt_shared.so',
            'cryptSharedLibRequired' => true,
        ];
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                            'extraOptions' => $extraOptions,
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $expected['connections']['default']['driver_options']['autoEncryption']['extraOptions'] = $extraOptions;
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionInvalidTypeOfBypassAutoEncryption(): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                            'bypassAutoEncryption' => 'not-a-boolean',
                        ],
                    ],
                ],
            ],
        ];
        $this->assertConfigurationIsInvalid(
            $config,
            '/Invalid type for path "doctrine_mongodb.connections.default.driver_options.autoEncryption.bypassAutoEncryption". Expected "bool", but got "string"./'
        );
    }

    public function testAutoEncryptionInvalidTypeOfKeyVaultNamespace(): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => ['not-a-string'],
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertConfigurationIsInvalid(
            $config,
            '/Invalid type for path "doctrine_mongodb.connections.default.driver_options.autoEncryption.keyVaultNamespace". Expected "scalar", but got "array"./'
        );
    }

    public function testAutoEncryptionWithOtherDriverOptionContext(): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'context' => 'my_context_service',
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'encryption.__keyVault',
                            'kmsProviders' => ['local' => ['key' => base64_encode(random_bytes(96))]],
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionWithFullTlsOptions(): void
    {
        $tlsOptions = [
            'tlsCAFile' => '/path/to/ca.pem',
            'tlsCertificateKeyFile' => '/path/to/client.pem',
            'tlsCertificateKeyFilePassword' => 'password123',
            'tlsAllowInvalidCertificates' => true,
            'tlsAllowInvalidHostnames' => true,
            'tlsDisableCertificateRevocationCheck' => true,
            'tlsDisableOCSPEndpointCheck' => true,
            'tlsInsecure' => true,
        ];
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                            'tlsOptions' => $tlsOptions,
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        // Ensure the structure is as expected, especially the nested tlsOptions
        $expected['connections']['default']['driver_options']['autoEncryption']['tlsOptions'] = $tlsOptions;
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionWithPartialTlsOptions(): void
    {
        $tlsOptions = [
            'tlsCAFile' => '/path/to/another_ca.pem',
            'tlsAllowInvalidHostnames' => true,
        ];
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                            'tlsOptions' => $tlsOptions,
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        $expected['connections']['default']['driver_options']['autoEncryption']['tlsOptions'] = $tlsOptions;
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionWithEmptyTlsOptions(): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                            'tlsOptions' => [], // Empty map
                        ],
                    ],
                ],
            ],
        ];
        $expected = $this->getMinimalValidConfig($config);
        // Expect an empty array for tlsOptions in the processed config
        $expected['connections']['default']['driver_options']['autoEncryption']['tlsOptions'] = [];
        $this->assertProcessedConfigurationEquals($expected, $config);
    }

    public function testAutoEncryptionWithoutTlsOptions(): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                            // tlsOptions is not provided
                        ],
                    ],
                ],
            ],
        ];
        $processed = $this->processConfiguration($config);
        // Assert that tlsOptions key is not even present in the autoEncryption array
        $this->assertArrayNotHasKey('tlsOptions', $processed['connections']['default']['driver_options']['autoEncryption']);
    }

    /** @dataProvider provideInvalidTlsOptionTypes */
    public function testAutoEncryptionWithInvalidTlsOptionType(string $optionName, $invalidValue, string $expectedMessagePart): void
    {
        $config = [
            'connections' => [
                'default' => [
                    'driver_options' => [
                        'autoEncryption' => [
                            'keyVaultNamespace' => 'db.coll',
                            'kmsProviders' => ['local' => ['key' => 'cGFzc3dvcmQ=']],
                            'tlsOptions' => [
                                $optionName => $invalidValue,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertConfigurationIsInvalid(
            $config,
            sprintf('/Invalid type for path "doctrine_mongodb.connections.default.driver_options.autoEncryption.tlsOptions.%s". Expected %s/', $optionName, $expectedMessagePart)
        );
    }

    public static function provideInvalidTlsOptionTypes(): array
    {
        return [
            'tlsCAFile_as_boolean' => ['tlsCAFile', true, '"scalar", but got "bool"'],
            'tlsCertificateKeyFile_as_array' => ['tlsCertificateKeyFile', ['arr'], '"scalar", but got "array"'],
            'tlsCertificateKeyFilePassword_as_int' => ['tlsCertificateKeyFilePassword', 123, '"scalar", but got "int"'],
            'tlsAllowInvalidCertificates_as_string' => ['tlsAllowInvalidCertificates', 'not-a-boolean', '"bool", but got "string"'],
            'tlsAllowInvalidHostnames_as_int' => ['tlsAllowInvalidHostnames', 0, '"bool", but got "int"'],
            'tlsDisableCertificateRevocationCheck_as_array' => ['tlsDisableCertificateRevocationCheck', [], '"bool", but got "array"'],
            'tlsDisableOCSPEndpointCheck_as_string' => ['tlsDisableOCSPEndpointCheck', 'true_string', '"bool", but got "string"'],
            'tlsInsecure_as_scalar_string' => ['tlsInsecure', 'false_string', '"bool", but got "string"'],
        ];
    }
}
