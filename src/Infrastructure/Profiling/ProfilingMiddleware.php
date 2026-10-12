<?php

declare(strict_types=1);

namespace Msstc4Symfony\ProfilingBridgeLogic\Infrastructure\Profiling;

use Closure;
use Msstc4Symfony\LogicBundle\Application\Action\ActionNameResolver;
use Msstc4Symfony\LogicBundle\Application\Action\ActionOutcome;
use Msstc4Symfony\LogicBundle\Application\Action\InputInterface;
use Msstc4Symfony\LogicBundle\Application\Action\OutputInterface;
use Msstc4Symfony\LogicBundle\Application\Pipeline\ActionContext;
use Msstc4Symfony\LogicBundle\Application\Pipeline\ActionMiddlewareInterface;
use Msstc4Symfony\LogicBundle\Application\Pipeline\Attribute\AsActionMiddleware;
use Msstc4Symfony\ProfilingBundle\Framework\ProfilingFactoryInterface;
use Override;
use Throwable;

/**
 * Outermost, so the span also covers the metrics bridge and everything inside it.
 */
#[AsActionMiddleware(priority: self::PRIORITY)]
final readonly class ProfilingMiddleware implements ActionMiddlewareInterface
{
    public const int PRIORITY = 500;

    // A stable prefix lets msstc4symfony_profiling.spans.whitelist|blacklist select Action spans.
    public const string SPAN_PREFIX = 'action ';

    public function __construct(
        private ProfilingFactoryInterface $profiling,
        private ActionNameResolver $names,
    ) {
    }

    #[Override]
    public function supports(ActionContext $context): bool
    {
        return true;
    }

    #[Override]
    public function handle(InputInterface $input, ActionContext $context, Closure $next): OutputInterface
    {
        $span = $this->profiling->createSpan(self::SPAN_PREFIX . $this->spanName($context));
        $failure = null;

        try {
            return $next($input);
        } catch (Throwable $exception) {
            $failure = $exception;

            throw $exception;
        } finally {
            $endContext = ['outcome' => ActionOutcome::of($failure)->value];
            if ($failure instanceof Throwable) {
                // get_debug_type(): an anonymous class name carries a NUL byte and the server path.
                $endContext['exception'] = get_debug_type($failure);
            }

            $this->profiling->endSpan($span, $endContext);
        }
    }

    private function spanName(ActionContext $context): string
    {
        try {
            return $this->names->resolve($context);
        } catch (Throwable) {
            // A broken name builder must not fail the Action because of profiling.
            return $context->actionClass;
        }
    }
}
