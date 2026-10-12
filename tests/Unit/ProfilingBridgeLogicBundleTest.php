<?php

declare(strict_types=1);

namespace Msstc4Symfony\ProfilingBridgeLogic\Test\Unit;

use Msstc4Symfony\ProfilingBridgeLogic\ProfilingBridgeLogicBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfilingBridgeLogicBundle::class)]
final class ProfilingBridgeLogicBundleTest extends TestCase
{
    public function testExtensionAliasFollowsTheFamilyNaming(): void
    {
        self::assertSame('msstc4symfony_profiling_bridge_logic', new ProfilingBridgeLogicBundle()->getContainerExtension()?->getAlias());
    }
}
