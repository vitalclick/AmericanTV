<?php

namespace Tests\Unit;

use Aws\S3\S3Client;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Exercises the @audit-ignore opt-out path on TestContainerBindingsAudit.
 *
 * We can't reach into the audit's private hasAuditIgnoreAbove() helper
 * without bending reflection; instead we feed synthetic source through
 * the same logic by reflecting into the helper directly. The synthetic
 * input is cheaper than spinning up real container bindings in a fake
 * test file (which itself would be subject to the audit and create a
 * chicken-and-egg loop).
 *
 * One way to keep the audit honest going forward: when this test fails,
 * it's because someone changed the heuristic in TestContainerBindingsAudit
 * and didn't update either side of the contract. Read the diff before
 * "fixing" the test.
 */
class TestContainerBindingsAuditIgnoreTest extends TestCase
{
    public function test_marker_directly_above_the_binding_exempts_it(): void
    {
        $source = <<<'PHP'
<?php
// @audit-ignore: one-shot binding for deprecation test.
$this->app->instance(SomeRetiredClient::class, $mock);
PHP;
        $this->assertTrue($this->checkExempt($source));
    }

    public function test_marker_in_a_docblock_continuation_also_exempts(): void
    {
        $source = <<<'PHP'
<?php
/**
 * Replaces production S3 with a recording mock for one assertion only.
 * @audit-ignore: scope is intentionally narrower than TEST_ONLY_BINDINGS.
 */
$this->app->instance(SomeRetiredClient::class, $mock);
PHP;
        $this->assertTrue($this->checkExempt($source));
    }

    public function test_marker_separated_by_a_code_line_does_not_exempt(): void
    {
        $source = <<<'PHP'
<?php
// @audit-ignore: this comment is decorative, not exempting the binding below.
$something = 1;
$this->app->instance(SomeRetiredClient::class, $mock);
PHP;
        $this->assertFalse($this->checkExempt($source));
    }

    public function test_no_marker_means_no_exemption(): void
    {
        $source = <<<'PHP'
<?php
$this->app->instance(SomeRetiredClient::class, $mock);
PHP;
        $this->assertFalse($this->checkExempt($source));
    }

    /**
     * Reflect into hasAuditIgnoreAbove() with the offset of the binding's
     * FQCN match. The helper is private but reflection bypasses the
     * visibility check.
     */
    private function checkExempt(string $source): bool
    {
        // Find where the SomeRetiredClient::class token starts.
        $pos = strpos($source, 'SomeRetiredClient');
        $this->assertNotFalse($pos, 'test fixture must reference SomeRetiredClient');

        $audit = new TestContainerBindingsAudit();
        $ref = new ReflectionClass($audit);
        $method = $ref->getMethod('hasAuditIgnoreAbove');
        $method->setAccessible(true);
        return (bool) $method->invoke($audit, $source, $pos);
    }

    // The audit is itself a PHPUnit test. We need it loaded for reflection;
    // a touch of ceremony makes sure the file gets included by autoload.
    public static function setUpBeforeClass(): void
    {
        class_exists(TestContainerBindingsAudit::class);
        class_exists(S3Client::class); // touch a TEST_ONLY_BINDINGS entry.
    }
}
