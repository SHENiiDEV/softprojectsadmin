<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use ParseError;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * A template that does not compile to valid PHP only fails when someone opens that page, e.g. an @if glued to the
 * preceding word ("Providers@if(...)") is not a directive, but its @endif is: "unexpected token endif".
 */
class BladeCompilationTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function bladeFiles(): array
    {
        $root = dirname(__DIR__, 2);
        $files = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/resources/views')) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[substr($file->getPathname(), strlen($root) + 1)] = [$file->getPathname()];
            }
        }

        return $files;
    }

    #[DataProvider('bladeFiles')]
    public function test_template_compiles_to_valid_php(string $path): void
    {
        $compiled = Blade::compileString((string) file_get_contents($path));

        try {
            token_get_all($compiled, TOKEN_PARSE);
        } catch (ParseError $error) {
            $this->fail("{$path} does not compile to valid PHP: {$error->getMessage()} (compiled line {$error->getLine()})");
        }

        $this->addToAssertionCount(1);
    }

    /**
     * Blade cuts every "@php ... @endphp" block out first with a lazy regex, so an inline "@php(...)" in the same
     * template swallows everything up to the next "@endphp" and later variables end up undefined.
     */
    #[DataProvider('bladeFiles')]
    public function test_template_does_not_use_inline_php_directive(string $path): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/(?<!@)@php\s*\(/',
            (string) file_get_contents($path),
            "{$path} uses the inline @php(...); use an @php ... @endphp block instead."
        );
    }
}
