<?php

namespace Mvo\ContaoFacebookImport\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Dependency\DependentPluginInterface;
use Mvo\ContaoFacebookImport\MvoContaoFacebookImportBundle;

class Plugin implements BundlePluginInterface, DependentPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(MvoContaoFacebookImportBundle::class)
                ->setLoadAfter(
                    [
                        \Contao\CoreBundle\ContaoCoreBundle::class,
                        'haste'
                    ]
                ),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPackageDependencies()
    {
        return [
            'facebook/graph-sdk',
            'guzzlehttp/guzzle',
        ];
    }

}
