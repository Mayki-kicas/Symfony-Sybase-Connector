<?php

declare(strict_types=1);

namespace SybaseConnector\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sybase_connector');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('dsn')
                    ->info('Full ODBC DSN string (overrides host/port/database/tds_version if set)')
                    ->defaultNull()
                ->end()
                ->scalarNode('host')
                    ->defaultValue('localhost')
                ->end()
                ->integerNode('port')
                    ->defaultValue(2639)
                ->end()
                ->scalarNode('database')
                    ->defaultValue('')
                ->end()
                ->scalarNode('user')
                    ->defaultValue('')
                ->end()
                ->scalarNode('password')
                    ->defaultValue('')
                ->end()
                ->scalarNode('tds_version')
                    ->defaultValue('5.0')
                ->end()
                ->scalarNode('connection_name')
                    ->info('Doctrine DBAL connection name')
                    ->defaultValue('sqlanywhere')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
