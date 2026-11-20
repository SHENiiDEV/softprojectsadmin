<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Guards against UI regressions that silently hide controls:
 *  - Tailwind shades that do not exist (e.g. bg-indigo-650) produce no CSS, so buttons lose their background.
 *  - Controls visible only on :hover are unreachable on touch devices.
 */
class ViewStyleGuardTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function bladeFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/resources/views'));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[substr($file->getPathname(), strlen(dirname(__DIR__, 2)) + 1)] = [$file->getPathname()];
            }
        }

        return $files;
    }

    #[DataProvider('bladeFiles')]
    public function test_view_does_not_use_nonexistent_tailwind_shades(string $path): void
    {
        $colors = 'slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose';
        $validShades = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];

        preg_match_all('/\b(?:bg|text|border|from|to|via|ring|divide|placeholder|fill|stroke)-(?:'.$colors.')-(\d{2,3})(?![0-9])/', (string) file_get_contents($path), $matches);

        $invalid = array_values(array_filter($matches[0], fn (string $class, int $i) => ! in_array($matches[1][$i], $validShades, true), ARRAY_FILTER_USE_BOTH));

        $this->assertSame([], $invalid, 'Nonexistent Tailwind shade(s) in '.$path);
    }

    #[DataProvider('bladeFiles')]
    public function test_view_has_no_hover_only_controls_on_touch_devices(string $path): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/(?<!\])opacity-0\s+group-hover:opacity-100/',
            (string) file_get_contents($path),
            'Hover-only control in '.$path.' (wrap it with [@media(hover:hover)]: variants)'
        );
    }
}
