<?php

/**
 * @author Andrey Vinichenko <andrey.vinichenko@gmail.com>
 */

namespace Ameotoko\StripeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('stripe');
        $treeBuilder
            ->getRootNode()
            ->children()
            ->scalarNode('secret_key')
                ->cannotBeEmpty()
                ->defaultValue('')
            ->end()
            ->scalarNode('publishable_key')
                ->cannotBeEmpty()
                ->defaultValue('')
            ->end()
        ;

        return $treeBuilder;
    }
}
