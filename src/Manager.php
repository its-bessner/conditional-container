<?php

namespace Itsbessner\ConditionalContainer;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\CoreBundle\ContaoCoreBundle;

class Manager implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ItsbessnerConditionalContainerBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}