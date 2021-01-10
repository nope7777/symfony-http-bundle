<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class N7SymfonyHttpBundleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = $this->createLoader();
        $loader->load('services.xml');
    }

    private function createLoader(): YamlFileLoader
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../resources/config')
        );
    }
}
