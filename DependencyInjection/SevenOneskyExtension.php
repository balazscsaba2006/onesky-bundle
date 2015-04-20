<?php

namespace Seven\Bundle\OneskyBundle\DependencyInjection;

use Onesky\Api\Client;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SevenOneskyExtension extends Extension
{
    const DEFAULT_OUTPUT = '[filename].[locale].[extension]';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->addDefinitions(array(
            'seven_onesky_client'     => $this->getClientDefinition($config),
            'seven_onesky_downloader' => $this->getDownloaderDefinition($config),
        ));
    }

    /**
     * @param $config
     *
     * @return Definition
     */
    private function getClientDefinition($config)
    {
        $client = new Definition('Onesky\Api\Client');
        $client->addMethodCall('setApiKey', array($config['api_key']));
        $client->addMethodCall('setSecret', array($config['secret']));

        return $client;
    }

    /**
     * @param $config
     *
     * @return Definition
     */
    private function getDownloaderDefinition($config)
    {
        $downloader = new Definition('Seven\Bundle\OneskyBundle\Onesky\Downloader', array(
            new Reference('seven_onesky_client'),
            $config['project'],
            $config['locale_format']
        ));

        foreach ($config['mappings'] as $mappingConfig) {
            $downloader->addMethodCall('addMapping', array(new Definition(
                'Seven\Bundle\OneskyBundle\Onesky\Mapping',
                array(
                    isset($mappingConfig['sources']) ? $mappingConfig['sources'] : array(),
                    isset($mappingConfig['locales']) ? $mappingConfig['locales'] : array(),
                    isset($mappingConfig['output'])  ? $mappingConfig['output'] : self::DEFAULT_OUTPUT,
                )
            )));
        }

        return $downloader;
    }
}
