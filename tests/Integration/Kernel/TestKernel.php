<?php

declare(strict_types=1);

namespace Msstc4Symfony\ProfilingBridgeLogic\Test\Integration\Kernel;

use Msstc4Symfony\LogicBundle\LogicBundle;
use Msstc4Symfony\ProfilingBridgeLogic\ProfilingBridgeLogicBundle;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\EchoAction;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\FailingAction;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\RecordingEndProcessor;
use Msstc4Symfony\ProfilingBundle\ProfilingBundle;
use Override;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    // Per process: parallel PHPUnit runs must not wipe each other's container.
    public static function cacheRoot(): string
    {
        return sys_get_temp_dir() . '/msstc4symfony-profiling-bridge-logic-test-' . getmypid();
    }

    #[Override]
    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new MonologBundle(), new ProfilingBundle(), new LogicBundle(), new ProfilingBridgeLogicBundle()];
    }

    #[Override]
    public function getCacheDir(): string
    {
        return self::cacheRoot() . '/cache/' . $this->environment;
    }

    #[Override]
    public function getLogDir(): string
    {
        return self::cacheRoot() . '/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'http_method_override' => false,
            'test' => true,
            // The php_errors logger installs a global handler that outlives the kernel and trips failOnRisky.
            'php_errors' => ['log' => false],
            'validation' => ['enabled' => true],
        ]);
        $container->extension('monolog', ['handlers' => ['main' => ['type' => 'test']]]);
        $container->services()
            ->set(EchoAction::class)->autowire()->autoconfigure()->public()
            ->set(FailingAction::class)->autowire()->autoconfigure()->public()
            ->set(RecordingEndProcessor::class)->autoconfigure()->public()
        ;
    }
}
