<?php

namespace FormStepBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->root('form_step')->cannotBeEmpty();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()
                ->arrayNode('forms')
                    //->isRequired(false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')
                                ->isRequired(true)
                            ->end()

                            ->arrayNode('entities')
                                ->isRequired(true)
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('entity')
                                            ->isRequired(true)
                                        ->end()
                                        ->scalarNode('property')
                                            ->isRequired(true)
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()


                            ->arrayNode('steps')
                                ->isRequired(true)
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('type')
                                            ->isRequired(true)
                                        ->end()
                                        ->scalarNode('finalStep')->end()
                                        ->arrayNode('fields')
                                            ->isRequired(true)
                                            ->prototype('array')
                                                ->children()
                                                    ->scalarNode('formtype')
                                                        // en commun entre les forms et propriétés
                                                        ->isRequired()
                                                    ->end()
                                                    ->scalarNode('entity')
                                                        ->defaultValue(null)
                                                    ->end()
                                                    ->scalarNode('property')
                                                        ->defaultValue(null)
                                                    ->end()
                                                    ->arrayNode('entityProperties')
                                                        ->prototype('scalar')->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
