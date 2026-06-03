<?php

declare(strict_types=1);

namespace SybaseConnector\DependencyInjection;

use SybaseConnector\DBAL\SchemaManager\SQLAnywhereSchemaManagerFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class SybaseConnectorExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sybase_connector.dsn', $config['dsn']);
        $container->setParameter('sybase_connector.host', $config['host']);
        $container->setParameter('sybase_connector.port', $config['port']);
        $container->setParameter('sybase_connector.database', $config['database']);
        $container->setParameter('sybase_connector.user', $config['user']);
        $container->setParameter('sybase_connector.password', $config['password']);
        $container->setParameter('sybase_connector.tds_version', $config['tds_version']);
        $container->setParameter('sybase_connector.connection_name', $config['connection_name']);

        // Register the schema manager factory as a service
        $container->register('sybase_connector.schema_manager_factory', SQLAnywhereSchemaManagerFactory::class);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }

    /**
     * Prepend Doctrine DBAL config to automatically register
     * the SQL Anywhere connection.
     */
    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $connectionName = $config['connection_name'];

        // Build DBAL connection config
        $dbalConnection = [
            'driver_class' => \SybaseConnector\DBAL\Driver\SybaseDriver::class,
            'user' => $config['user'],
            'password' => $config['password'],
            'schema_manager_factory' => 'sybase_connector.schema_manager_factory',
        ];

        if ($config['dsn'] !== null && $config['dsn'] !== '') {
            $dbalConnection['driverOptions'] = ['dsn' => $config['dsn']];
        } else {
            $dbalConnection['host'] = $config['host'];
            $dbalConnection['port'] = $config['port'];
            $dbalConnection['dbname'] = $config['database'];
            $dbalConnection['driverOptions'] = ['tds_version' => $config['tds_version']];
        }

        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'connections' => [
                    $connectionName => $dbalConnection,
                ],
            ],
        ]);
    }
}
