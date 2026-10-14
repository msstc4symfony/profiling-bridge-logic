<?php

declare(strict_types=1);

namespace Msstc4Symfony\ProfilingBridgeLogic\Test\Integration;

use Msstc4Symfony\LogicBundle\Application\Logging\LoggingMiddleware;
use Msstc4Symfony\LogicBundle\Application\Pipeline\ActionPipeline;
use Msstc4Symfony\ProfilingBridgeLogic\Infrastructure\Profiling\ProfilingMiddleware;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\EchoAction;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\EchoInput;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\ExceptionHandlerSnapshot;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\FailingAction;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\RecordingEndProcessor;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Integration\Kernel\TestKernel;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

#[CoversNothing]
final class KernelBridgeTest extends TestCase
{
    private TestKernel $kernel;

    /** @var list<string> */
    private array $bundleDeprecations = [];

    private ExceptionHandlerSnapshot $exceptionHandlers;

    protected function setUp(): void
    {
        // trigger_deprecation() silences its notice with @, which PHPUnit ignores; record it directly.
        set_error_handler(function (int $level, string $message): bool {
            if (str_contains($message, 'msstc4symfony/')) {
                $this->bundleDeprecations[] = $message;
            }

            return false;
        }, E_USER_DEPRECATED);
        (new Filesystem())->remove(TestKernel::cacheRoot());
        $this->exceptionHandlers = ExceptionHandlerSnapshot::take();
        $this->kernel = new TestKernel('test', false);
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        // Guarded so that a failing setUp() is reported instead of an uninitialised property.
        if (isset($this->kernel)) {
            $this->kernel->shutdown();
        }

        // FrameworkBundle::boot() on symfony/error-handler before 7.4.17 leaves its exception handler
        // installed when another error handler is active, which PHPUnit reports as risky.
        if (isset($this->exceptionHandlers)) {
            $this->exceptionHandlers->restore();
        }

        (new Filesystem())->remove(TestKernel::cacheRoot());
    }

    public function testAnActionCallEndsOneSpan(): void
    {
        $action = $this->container()->get(EchoAction::class);
        self::assertIsCallable($action);
        $action(new EchoInput());

        self::assertSame([RecordingEndProcessor::topLevelSpan(EchoAction::class, ['outcome' => 'success'])], $this->recorder()->ended());
        self::assertSame([], $this->bundleDeprecations);
    }

    public function testAFailingActionEndsItsSpanWithTheException(): void
    {
        $action = $this->container()->get(FailingAction::class);
        self::assertIsCallable($action);

        try {
            $action(new EchoInput());
            self::fail('The Action must throw.');
        } catch (RuntimeException) {
        }

        self::assertSame([RecordingEndProcessor::topLevelSpan(FailingAction::class, ['outcome' => 'error', 'exception' => RuntimeException::class])], $this->recorder()->ended());
    }

    public function testTheMiddlewareRunsOutsideLogging(): void
    {
        // Reads ActionPipeline's private middleware list: logic-bundle exposes no public order; see known-issues.md.
        $pipeline = $this->container()->get(EchoAction::class);
        self::assertInstanceOf(ActionPipeline::class, $pipeline);
        $middlewares = (new ReflectionProperty($pipeline, 'middlewares'))->getValue($pipeline);
        self::assertIsIterable($middlewares);
        $classes = [];
        foreach ($middlewares as $middleware) {
            self::assertIsObject($middleware);
            $classes[] = $middleware::class;
        }

        $profiling = array_search(ProfilingMiddleware::class, $classes, true);
        $logging = array_search(LoggingMiddleware::class, $classes, true);
        self::assertIsInt($profiling);
        self::assertIsInt($logging);
        self::assertLessThan($logging, $profiling);
    }

    private function recorder(): RecordingEndProcessor
    {
        $recorder = $this->container()->get(RecordingEndProcessor::class);
        self::assertInstanceOf(RecordingEndProcessor::class, $recorder);

        return $recorder;
    }

    private function container(): ContainerInterface
    {
        $container = $this->kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $container);

        return $container;
    }
}
