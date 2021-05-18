<?php

declare(strict_types=1);

/*
 * Contao Facebook Import Bundle for Contao Open Source CMS
 *
 * @copyright  Copyright (c), Moritz Vondano
 * @license    MIT
 * @link       https://github.com/m-vo/contao-facebook-import
 *
 * @author     Moritz Vondano
 */

namespace Mvo\ContaoFacebookImport\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('mvo_contao_facebook_import');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->integerNode('request_limit_per_node')
                    ->min(0)
                    ->defaultValue(150)
                ->end()
                ->integerNode('request_window_per_node')
                    ->defaultValue(3600)
                    ->min(0)
                ->end()
                ->integerNode('max_execution_time')
                    ->min(1)
                    ->defaultValue(16)
                ->end()
                ->enumNode('trigger_type')
                    ->values(['internal', 'route'])
                    ->defaultValue('internal')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
