<?php

declare(strict_types=1);

/**
 * @author Andrey Vinichenko <andrey.vinichenko@gmail.com>
 */

namespace Ameotoko\StripeBundle\DependencyInjection;

use Ameotoko\StripeBundle\Controller\StripeController;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AmeotokoStripeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setParameter('stripe.secret_key', $config['secret_key']);
        $container->setParameter('stripe.publishable_key', $config['publishable_key']);

        $container->getDefinition(StripeController::class)->setArgument(0, $config['secret_key']);
    }

    public function getAlias()
    {
        return 'stripe';
    }
}
