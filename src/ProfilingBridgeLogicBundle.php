<?php

declare(strict_types=1);

namespace Msstc4Symfony\ProfilingBridgeLogic;

use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class ProfilingBridgeLogicBundle extends AbstractBundle
{
    protected string $extensionAlias = 'msstc4symfony_profiling_bridge_logic';

    /**
     * @param array<array-key, mixed> $config
     */
    #[Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/Resources/config/services.php');
    }
}
