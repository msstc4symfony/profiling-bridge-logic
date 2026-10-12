<?php

declare(strict_types=1);

namespace Msstc4Symfony\ProfilingBridgeLogic\Test\Fixtures;

use Msstc4Symfony\ProfilingBundle\Framework\Processor\EndSpan\EndSpanProcessorInterface;
use Msstc4Symfony\ProfilingBundle\Framework\Span\SpanInterface;
use Override;

/**
 * One processed span: its message, its parent's message and its end context.
 *
 * @phpstan-type EndedSpanShape array{message: string, parent: ?string, context: array<string, mixed>}
 */
final class RecordingEndProcessor implements EndSpanProcessorInterface
{
    /** @var list<EndedSpanShape> */
    private array $ended = [];

    #[Override]
    public function process(SpanInterface $span, array $context): void
    {
        $this->ended[] = [
            'message' => $span->getMessage(),
            'parent' => $span->getParentSpan()?->getMessage(),
            'context' => $context,
        ];
    }

    /**
     * The expected record of a top-level Action span.
     *
     * @param class-string $actionClass
     * @param array<string, mixed> $context
     *
     * @return EndedSpanShape
     */
    public static function topLevelSpan(string $actionClass, array $context): array
    {
        return ['message' => 'action ' . $actionClass, 'parent' => null, 'context' => $context];
    }

    /**
     * @return list<EndedSpanShape>
     */
    public function ended(): array
    {
        return $this->ended;
    }
}
