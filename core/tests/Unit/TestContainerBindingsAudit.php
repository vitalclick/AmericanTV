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

        $normalizedRegistered = array_map(
            fn ($c) => ltrim($c, '\\'),
            $registered,
        );

        $testsDir = realpath(__DIR__ . '/..');
        $files = $this->phpFilesUnder($testsDir);

        $methodAlternation = implode('|', self::BINDING_METHODS);
        // Catches three shapes — all hit the same root container, all leak
        // the same way unless registered:
        //   $this->app->instance(X::class, ...)          (arrow)
        //   app()->instance(X::class, ...)               (helper -> arrow)
        //   App::instance(X::class, ...)                 (facade)
        //   Container::getInstance()->instance(X::class) (raw container)
        // The (?:->|::) alternation handles arrow and facade together;
        // raw-container hits via the arrow alternative because the chain
        // resolves to an instance method call.
        $pattern = '/(?:->|::)(' . $methodAlternation . ')\(\s*([A-Za-z0-9_\\\\]+)::class/m';

        $bound = [];
        // Bindings that ARE in TEST_ONLY_BINDINGS but were also marked
        // @audit-ignore — the marker is doing no work and should be
        // removed so it doesn't bit-rot.
        $staleIgnoreMarkers = [];

        foreach ($files as $file) {
            $source = file_get_contents($file);

            if (! preg_match_all($pattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[2] as $i => $fqcnMatch) {
                [$fqcn, $offset] = $fqcnMatch;
                $absolute = $this->resolveFqcn($source, $fqcn);
                if ($absolute === null) continue;

                $method = $matches[1][$i][0];
                $isIgnored = $this->hasAuditIgnoreAbove($source, $offset);
                $isRegistered = in_array(ltrim($absolute, '\\'), $normalizedRegistered, true);

                if ($isIgnored && $isRegistered) {
                    $staleIgnoreMarkers[$absolute] =
                        ($staleIgnoreMarkers[$absolute] ?? '') . " {$file}#{$method}";
                    continue;
                }
                if ($isIgnored) continue;

                if (! $isRegistered) {
                    $bound[$absolute] = ($bound[$absolute] ?? '') . " {$file}#{$method}";
                }
            }
        }

        $this->assertEmpty(
            $bound,
            "The following classes are bound via the container in tests but\n"
            . "aren't in TestCase::TEST_ONLY_BINDINGS, so a mock from one test will\n"
            . "leak into the next:\n\n"
            . implode(
                "\n",
                array_map(
                    fn ($fqcn, $where) => "  - {$fqcn} (in:{$where})",
                    array_keys($bound),
                    array_values($bound),
                )
            ) . "\n\n"
            . "Either add the class to TestCase::TEST_ONLY_BINDINGS so it gets forgotten\n"
            . "on tearDown, OR mark the binding with a `// @audit-ignore: reason`\n"
            . "comment on the preceding line."
        );

        $this->assertEmpty(
            $staleIgnoreMarkers,
            "The following bindings carry an @audit-ignore comment but are ALSO in\n"
            . "TestCase::TEST_ONLY_BINDINGS — the marker is doing no work and should\n"
            . "be removed so it doesn't sit around as future code-review noise:\n\n"
            . implode(
                "\n",
                array_map(
                    fn ($fqcn, $where) => "  - {$fqcn} (in:{$where})",
                    array_keys($staleIgnoreMarkers),
                    array_values($staleIgnoreMarkers),
                )
            )
        );
    }

    /**
     * Look at the lines immediately before $offset; if any of the (up to 3)
     * preceding non-blank lines is a comment containing `@audit-ignore:`
     * followed by a non-empty reason, the binding is exempted.
     *
     * The non-empty reason requirement is the bit that distinguishes a
     * deliberate exemption ("we're testing the deprecation path") from
     * a casual one ("don't audit this"). Bare `@audit-ignore` without
     * justification doesn't count.
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
            $isComment = str_starts_with($line, '//') ||
                str_starts_with($line, '*') ||
                str_starts_with($line, '#');
            if ($isComment && preg_match('/@audit-ignore:\s*\S/', $line) === 1) {
                return true;
            }
            // First non-blank, non-comment line we hit — stop walking.
            if (! $isComment) {
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
