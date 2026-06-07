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
 * Detected shapes:
 *   $this->app->instance(Foo::class, $mock)
 *   $this->app->bind(Foo::class, fn () => ...)
 *   $this->app->singleton(Foo::class, ...)
 *   app()->instance(Foo::class, ...)
 *   App::instance(Foo::class, ...)
 *   App::bind / App::singleton  (Illuminate\Support\Facades\App)
 *
 * Exempting a single binding (rare — usually a sign the test needs a
 * different shape) is possible via a `@audit-ignore` comment on the
 * preceding line:
 *
 *   // @audit-ignore: binding RetiredFooClient is a one-shot test for the
 *   //                deprecation path; not worth adding to the const.
 *   $this->app->instance(RetiredFooClient::class, $mock);
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
        // Catches both arrow ($this->app->) and facade (App::) forms.
        $pattern = '/(?:->|::)(' . $methodAlternation . ')\(\s*([A-Za-z0-9_\\\\]+)::class/m';

        $bound = [];
        foreach ($files as $file) {
            $source = file_get_contents($file);

            if (! preg_match_all($pattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[2] as $i => $fqcnMatch) {
                [$fqcn, $offset] = $fqcnMatch;
                if ($this->hasAuditIgnoreAbove($source, $offset)) {
                    continue;
                }

                $absolute = $this->resolveFqcn($source, $fqcn);
                if ($absolute === null) continue;

                $method = $matches[1][$i][0];
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
            . "on tearDown, OR mark the binding with a `// @audit-ignore: reason`\n"
            . "comment on the preceding line."
        );
    }

    /**
     * Look at the lines immediately before $offset; if any of the (up to 3)
     * preceding non-blank lines is a comment containing `@audit-ignore`,
     * the binding is exempted.
     */
    private function hasAuditIgnoreAbove(string $source, int $offset): bool
    {
        $upTo = substr($source, 0, $offset);
        $lines = explode("\n", $upTo);
        // Walk backwards through up to 3 non-blank lines.
        $checked = 0;
        for ($i = count($lines) - 1; $i >= 0 && $checked < 3; $i--) {
            $line = trim($lines[$i]);
            if ($line === '') continue;
            $checked++;
            if (str_contains($line, '@audit-ignore') &&
                (str_starts_with($line, '//') || str_starts_with($line, '*') || str_starts_with($line, '#'))) {
                return true;
            }
            // First non-blank, non-comment line we hit — stop walking.
            if (! str_starts_with($line, '//') &&
                ! str_starts_with($line, '*') &&
                ! str_starts_with($line, '#')) {
                return false;
            }
        }
        return false;
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
