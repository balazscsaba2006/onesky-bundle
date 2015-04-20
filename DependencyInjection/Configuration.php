<?php

namespace Seven\Bundle\OneskyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('seven_onesky');

        $rootNode
            ->children()
                ->scalarNode('api_key')->isRequired()->end()
                ->scalarNode('secret')->isRequired()->end()
                ->scalarNode('project')->isRequired()->end()
                ->arrayNode('locale_format')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('parts')
                            ->prototype('scalar')
                                ->validate()
                                    ->ifNotInArray(array('code', 'locale', 'region'))
                                    ->thenInvalid('Invalid locale format part "%s"')
                                ->end()
                            ->end()
                            ->isRequired()
                            ->defaultValue(array('locale'))
                        ->end()
                        ->scalarNode('separator')
                            ->validate()
                                ->ifNotInArray(array('_', '-'))
                                ->thenInvalid('Invalid locale format separator "%s"')
                            ->end()
                            ->defaultValue('_')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('mappings')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('sources')->prototype('scalar')->end()->end()
                            ->arrayNode('locales')->prototype('scalar')->end()->end()
                            ->scalarNode('output')->defaultValue('[filename].[locale].[extension]')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
