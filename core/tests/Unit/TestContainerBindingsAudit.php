<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\TestCase as AppTestCase;

/**
 * Audit: every external-client class bound via `$this->app->instance(...)`
 * in a test must also appear in TestCase::TEST_ONLY_BINDINGS, so the
 * binding gets forgotten on tearDown and can't leak into the next test.
 *
 * The check greps the tests/ directory for `app->instance(SomeClass::class
 * ...)` shapes (an admittedly small regex), extracts the FQCN, and asserts
 * the FQCN is in the const list. Either-or: the new external client gets
 * added to the const, or the test author has to argue for an exemption
 * via a comment marker.
 */
class TestContainerBindingsAudit extends TestCase
{
    public function test_every_app_instance_bound_class_is_in_the_test_only_bindings_list(): void
    {
        $reflected = new ReflectionClass(AppTestCase::class);
        $registered = $reflected->getConstants()['TEST_ONLY_BINDINGS'] ?? null;
        $this->assertNotNull(
            $registered,
            'TestCase::TEST_ONLY_BINDINGS missing — was the const renamed?',
        );

        $testsDir = realpath(__DIR__ . '/..');
        $files = $this->phpFilesUnder($testsDir);

        $bound = [];
        foreach ($files as $file) {
            $source = file_get_contents($file);

            // Match `->instance(SomeClass::class, ...)` and capture the FQCN.
            if (! preg_match_all(
                '/->instance\(\s*([A-Za-z0-9_\\\\]+)::class/m',
                $source,
                $matches,
            )) {
                continue;
            }

            foreach ($matches[1] as $fqcn) {
                // Carry over the source file's use-imports so the FQCN can
                // be made absolute. The simplest version: if the FQCN
                // doesn't start with a backslash and the source has
                // `use FQCN;`, qualify it.
                $absolute = $this->resolveFqcn($source, $fqcn);
                if ($absolute === null) continue;

                $bound[$absolute] = ($bound[$absolute] ?? '') . " {$file}";
            }
        }

        $missing = [];
        foreach ($bound as $fqcn => $where) {
            if (! in_array(ltrim($fqcn, '\\'), array_map(fn ($c) => ltrim($c, '\\'), $registered), true)) {
                $missing[$fqcn] = $where;
            }
        }

        $this->assertEmpty(
            $missing,
            "The following classes are bound via \$this->app->instance(...) in tests but\n"
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
