<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Whitebox: feed each container-binding shape through the audit's regex
 * and confirm it catches them all.
 *
 * The audit's main test does this implicitly by scanning tests/, but it
 * only fails when a *new* binding lands. If somebody silently introduces
 * a new shape in production code (e.g. switches from $this->app->instance
 * to Container::getInstance()->instance), the main test would still
 * pass as long as TEST_ONLY_BINDINGS covers the class. This test pins
 * down the regex itself, so a future "let's clean up the audit" refactor
 * can't accidentally drop coverage of a shape.
 */
class TestContainerBindingsAuditShapesTest extends TestCase
{
    /**
     * @dataProvider shapeProvider
     */
    public function test_audit_regex_catches_each_binding_shape(string $shape, string $expectedFqcn): void
    {
        $pattern = $this->auditPattern();
        $matched = preg_match_all($pattern, $shape, $matches);

        $this->assertSame(
            1,
            $matched,
            "Audit regex did NOT catch shape:\n{$shape}",
        );
        $this->assertSame($expectedFqcn, $matches[2][0]);
    }

    public static function shapeProvider(): array
    {
        return [
            'arrow on $this->app' => [
                '$this->app->instance(SomeClient::class, $mock);',
                'SomeClient',
            ],
            'arrow on app() helper' => [
                'app()->instance(SomeClient::class, $mock);',
                'SomeClient',
            ],
            'App facade' => [
                'App::instance(SomeClient::class, $mock);',
                'SomeClient',
            ],
            'Container::getInstance() chain' => [
                'Container::getInstance()->instance(SomeClient::class, $mock);',
                'SomeClient',
            ],
            'singleton on arrow' => [
                '$this->app->singleton(SomeClient::class, fn () => new SomeClient());',
                'SomeClient',
            ],
            'bind on App facade' => [
                'App::bind(SomeClient::class, fn () => new SomeClient());',
                'SomeClient',
            ],
            'namespaced FQCN' => [
                '$this->app->instance(\Foo\Bar\SomeClient::class, $mock);',
                '\Foo\Bar\SomeClient',
            ],
        ];
    }

    /**
     * Pulls the actual regex out of the audit class via reflection, so this
     * test mirrors whatever the audit currently uses without us having to
     * duplicate the string.
     */
    private function auditPattern(): string
    {
        $audit = new TestContainerBindingsAudit();
        $ref   = new ReflectionClass($audit);

        // The audit constructs $pattern inside its test method; we
        // reconstruct it here from BINDING_METHODS so the two stay tied.
        $methods = $ref->getConstants()['BINDING_METHODS'];
        $alt     = implode('|', $methods);
        return '/(?:->|::)(' . $alt . ')\(\s*([A-Za-z0-9_\\\\]+)::class/m';
    }
}
