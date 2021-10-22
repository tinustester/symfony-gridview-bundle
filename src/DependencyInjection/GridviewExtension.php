<?php

namespace Tinustester\Bundle\GridviewBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class GridviewExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(dirname(dirname(__DIR__)).'/config')
        );

        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

//        foreach ($this->servicesWithCompatibilityIssue as $serviceName) {
//
//            $definition = $container->getDefinition($serviceName);
//
//            if (Kernel::MAJOR_VERSION == 2 && Kernel::MINOR_VERSION < 8) {
//                $definition->setScope('prototype');
//            } else {
//                $definition->setShared(false);
//            }
//        }

    }
}