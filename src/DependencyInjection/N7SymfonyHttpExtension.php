<?php

declare(strict_types=1);

namespace N7\SymfonyHttpBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class N7SymfonyHttpExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = $this->createLoader($container);
        $loader->load('services.yaml');
    }

    private function createLoader(ContainerBuilder $container): YamlFileLoader
    {
        return new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../resources/config')
        );
    }
}
