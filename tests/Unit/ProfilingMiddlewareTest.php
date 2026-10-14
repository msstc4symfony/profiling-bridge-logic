<?php

declare(strict_types=1);

namespace Msstc4Symfony\ProfilingBridgeLogic\Test\Unit;

use DomainException;
use Msstc4Symfony\LogicBundle\Application\Action\ActionNameResolver;
use Msstc4Symfony\LogicBundle\Application\Action\ExpectedFailureInterface;
use Msstc4Symfony\LogicBundle\Application\Action\InputInterface;
use Msstc4Symfony\LogicBundle\Application\Action\OutputInterface;
use Msstc4Symfony\LogicBundle\Application\Logging\Builder\ActionNameBuilderInterface;
use Msstc4Symfony\LogicBundle\Application\Pipeline\ActionContext;
use Msstc4Symfony\LogicBundle\Application\Pipeline\Attribute\AsActionMiddleware;
use Msstc4Symfony\ProfilingBridgeLogic\Infrastructure\Profiling\ProfilingMiddleware;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\EchoAction;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\EchoInput;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\EchoOutput;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\FailingAction;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\InnerAction;
use Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures\RecordingEndProcessor;
use Msstc4Symfony\ProfilingBundle\Framework\Assembler\SpanAssembler;
use Msstc4Symfony\ProfilingBundle\Framework\ProfilingFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use RuntimeException;

#[CoversClass(ProfilingMiddleware::class)]
final class ProfilingMiddlewareTest extends TestCase
{
    private RecordingEndProcessor $recorder;

    private ProfilingMiddleware $middleware;

    protected function setUp(): void
    {
        $this->recorder = new RecordingEndProcessor();
        $this->middleware = $this->middleware(new ActionNameResolver());
    }

    public function testEndsASuccessSpanAndReturnsTheSameOutput(): void
    {
        $output = new EchoOutput('ok');

        self::assertSame($output, $this->middleware->handle(new EchoInput(), ActionContext::of(EchoAction::class), static fn (InputInterface $input): OutputInterface => $output));

        self::assertSame([RecordingEndProcessor::topLevelSpan(EchoAction::class, ['outcome' => 'success'])], $this->recorder->ended());
    }

    public function testEndsTheSpanWithTheFailure(): void
    {
        $failure = new RuntimeException('boom');

        try {
            $this->middleware->handle(new EchoInput(), ActionContext::of(FailingAction::class), static fn (InputInterface $input): never => throw $failure);
            self::fail('The exception must be rethrown.');
        } catch (RuntimeException $caught) {
            self::assertSame($failure, $caught);
        }

        self::assertSame([RecordingEndProcessor::topLevelSpan(FailingAction::class, ['outcome' => 'error', 'exception' => RuntimeException::class])], $this->recorder->ended());
    }

    public function testAnExpectedFailureIsMarkedSo(): void
    {
        $failure = new class('taken') extends DomainException implements ExpectedFailureInterface {
        };

        try {
            $this->middleware->handle(new EchoInput(), ActionContext::of(EchoAction::class), static fn (InputInterface $input): never => throw $failure);
            self::fail('The exception must be rethrown.');
        } catch (DomainException $caught) {
            self::assertSame($failure, $caught);
        }

        self::assertSame(['outcome' => 'expected_failure', 'exception' => 'DomainException@anonymous'], $this->recorder->ended()[0]['context'] ?? null);
    }

    public function testUsesTheResolvedActionName(): void
    {
        $names = new ActionNameResolver([new class implements ActionNameBuilderInterface {
            public function build(string $actionClass): string
            {
                return 'echo';
            }

            public function supports(string $actionClass): bool
            {
                return true;
            }
        }]);

        $this->middleware($names)->handle(new EchoInput(), ActionContext::of(EchoAction::class), static fn (InputInterface $input): OutputInterface => new EchoOutput('ok'));

        self::assertSame('action echo', $this->recorder->ended()[0]['message'] ?? null);
    }

    public function testABrokenNameBuilderFallsBackToTheActionClass(): void
    {
        $names = new ActionNameResolver([new class implements ActionNameBuilderInterface {
            public function build(string $actionClass): string
            {
                throw new RuntimeException('broken builder');
            }

            public function supports(string $actionClass): bool
            {
                return true;
            }
        }]);
        $output = new EchoOutput('ok');

        self::assertSame($output, $this->middleware($names)->handle(new EchoInput(), ActionContext::of(EchoAction::class), static fn (InputInterface $input): OutputInterface => $output));

        self::assertSame([RecordingEndProcessor::topLevelSpan(EchoAction::class, ['outcome' => 'success'])], $this->recorder->ended());
    }

    public function testANestedActionGetsAChildSpan(): void
    {
        $inner = fn (InputInterface $input): OutputInterface => $this->middleware->handle(
            $input,
            ActionContext::of(InnerAction::class),
            static fn (InputInterface $input): OutputInterface => new EchoOutput('inner'),
        );

        $this->middleware->handle(new EchoInput(), ActionContext::of(EchoAction::class), $inner);

        self::assertSame(
            [['action ' . InnerAction::class, 'action ' . EchoAction::class], ['action ' . EchoAction::class, null]],
            array_map(static fn (array $span): array => [$span['message'], $span['parent']], $this->recorder->ended()),
        );
    }

    public function testSupportsEveryActionAtPriority500(): void
    {
        self::assertTrue($this->middleware->supports(ActionContext::of(EchoAction::class)));
        self::assertSame(500, ProfilingMiddleware::PRIORITY);
        $attribute = (new ReflectionClass(ProfilingMiddleware::class))->getAttributes(AsActionMiddleware::class)[0] ?? null;
        self::assertNotNull($attribute);
        self::assertSame(500, $attribute->newInstance()->priority);
    }

    private function middleware(ActionNameResolver $names): ProfilingMiddleware
    {
        return new ProfilingMiddleware(new ProfilingFactory([new SpanAssembler([])], [], [$this->recorder], new NullLogger()), $names);
    }
}
