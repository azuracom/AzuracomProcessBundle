<?php

namespace Azuracom\ProcessBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that boot the bundle through the {@see TestKernel}. Takes care of shutting
 * the kernel down and restoring PHP error/exception handlers the kernel may have registered.
 */
abstract class FunctionalTestCase extends TestCase
{
    protected ?TestKernel $kernel = null;

    /**
     * @param array{user_class?: ?string} $processConfig
     */
    protected function bootKernel(array $processConfig = []): TestKernel
    {
        $this->kernel = new TestKernel($processConfig);
        $this->kernel->boot();

        return $this->kernel;
    }

    protected function tearDown(): void
    {
        $this->kernel?->shutdown();
        $this->kernel = null;

        // Booting the kernel registers an exception handler that is not cleaned up on shutdown.
        restore_exception_handler();
    }
}
