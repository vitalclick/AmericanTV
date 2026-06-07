<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\TestCase as AppTestCase;

/**
 * Audit: every external-client class bound via the container in a test —
 * via `->instance(...)`, `->bind(...)`, or `->singleton(...)` — must also
 * appear in TestCase::TEST_ONLY_BINDINGS, so the binding gets forgotten
 * on tearDown and can't leak into the next test.
 *
 * Why all three:
 *   - instance() binds an existing object (the most common shape; what
 *     ArchiveAnalyticsTest uses).
 *   - bind() / singleton() bind a factory closure; the resolved object
 *     still survives across tests under singleton(), and bind() leaves
 *     the factory itself behind. Both leak.
 *
 * The check greps the tests/ directory for any of those shapes, extracts
 * the FQCN, and asserts the FQCN is in the const list. New external
 * clients either get added to the const or the test author has to
 * refactor away from the container binding.
 */
class TestContainerBindingsAudit extends TestCase
{
    /**
     * Container binding methods that mark something for later resolution.
     * Adding `extend()` here would also catch class decorators if we ever
     * start using them in tests.
     */
    private const BINDING_METHODS = ['instance', 'bind', 'singleton'];

    public function test_every_container_bound_class_is_in_the_test_only_bindings_list(): void
    {
        $reflected = new ReflectionClass(AppTestCase::class);
        $registered = $reflected->getConstants()['TEST_ONLY_BINDINGS'] ?? null;
        $this->assertNotNull(
            $registered,
            'TestCase::TEST_ONLY_BINDINGS missing — was the const renamed?',
        );

        $testsDir = realpath(__DIR__ . '/..');
        $files = $this->phpFilesUnder($testsDir);

        $methodAlternation = implode('|', self::BINDING_METHODS);
        $pattern = '/->(' . $methodAlternation . ')\(\s*([A-Za-z0-9_\\\\]+)::class/m';

        $bound = [];
        foreach ($files as $file) {
            $source = file_get_contents($file);

            if (! preg_match_all($pattern, $source, $matches)) {
                continue;
            }

            // $matches[1] holds the method name, $matches[2] holds the FQCN.
            foreach ($matches[2] as $i => $fqcn) {
                $absolute = $this->resolveFqcn($source, $fqcn);
                if ($absolute === null) continue;

                $method = $matches[1][$i];
                $bound[$absolute] = ($bound[$absolute] ?? '') . " {$file}#{$method}";
            }
        }

        $missing = [];
        foreach ($bound as $fqcn => $where) {
            $normalized = ltrim($fqcn, '\\');
            $normalizedRegistered = array_map(
                fn ($c) => ltrim($c, '\\'),
                $registered,
            );
            if (! in_array($normalized, $normalizedRegistered, true)) {
                $missing[$fqcn] = $where;
            }
        }

        $this->assertEmpty(
            $missing,
            "The following classes are bound via the container in tests but\n"
            . "aren't in TestCase::TEST_ONLY_BINDINGS, so a mock from one test will\n"
            . "leak into the next:\n\n"
            . implode(
                "\n",
                array_map(
                    fn ($fqcn, $where) => "  - {$fqcn} (in:{$where})",
                    array_keys($missing),
                    array_values($missing),
                )
            ) . "\n\n"
            . "Either add the class to TestCase::TEST_ONLY_BINDINGS so it gets forgotten\n"
            . "on tearDown, or refactor the test to avoid the container binding."
        );
    }

    private function phpFilesUnder(string $dir): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

    private function resolveFqcn(string $source, string $reference): ?string
    {
        // Already fully-qualified — done.
        if (str_starts_with($reference, '\\')) {
            return ltrim($reference, '\\');
        }
        if (str_contains($reference, '\\')) {
            return $reference;
        }

        // Look for `use Foo\Bar\Reference;` in the source.
        if (preg_match("/^\s*use\s+([A-Za-z0-9_\\\\]+\\\\{$reference});/m", $source, $m)) {
            return $m[1];
        }
        // `use Foo\Bar as Reference;` aliasing.
        if (preg_match("/^\s*use\s+([A-Za-z0-9_\\\\]+)\s+as\s+{$reference};/m", $source, $m)) {
            return $m[1];
        }

        // Unqualified, no import — treat as relative-to-default namespace.
        // We don't try to resolve these (the audit would over-flag); skip.
        return null;
    }
}
