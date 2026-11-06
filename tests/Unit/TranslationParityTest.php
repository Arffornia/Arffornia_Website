<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every lang/en/*.php file must have a lang/fr counterpart with exactly the same keys (and vice versa),
 * and the same :placeholders in each line.
 */
class TranslationParityTest extends TestCase
{
    private const LOCALES = ['en', 'fr'];

    private static function langPath(string ...$parts): string
    {
        return dirname(__DIR__, 2) . '/lang/' . implode('/', $parts);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function langFiles(): array
    {
        $files = [];
        foreach (self::LOCALES as $locale) {
            foreach (glob(self::langPath($locale, '*.php')) as $path) {
                $files[basename($path)] = [basename($path)];
            }
        }
        ksort($files);

        return $files;
    }

    public function test_lang_files_exist_for_every_locale(): void
    {
        $this->assertNotEmpty(self::langFiles());
        foreach (array_keys(self::langFiles()) as $file) {
            foreach (self::LOCALES as $locale) {
                $this->assertFileExists(self::langPath($locale, $file), "lang/$locale/$file is missing");
            }
        }
    }

    #[DataProvider('langFiles')]
    public function test_keys_and_placeholders_match(string $file): void
    {
        $lines = [];
        foreach (self::LOCALES as $locale) {
            $path = self::langPath($locale, $file);
            $this->assertFileExists($path);
            $lines[$locale] = self::flatten(require $path);
        }

        [$reference, $other] = self::LOCALES;
        $this->assertSame(
            [],
            array_values(array_diff(array_keys($lines[$reference]), array_keys($lines[$other]))),
            "Keys in lang/$reference/$file missing from lang/$other/$file"
        );
        $this->assertSame(
            [],
            array_values(array_diff(array_keys($lines[$other]), array_keys($lines[$reference]))),
            "Keys in lang/$other/$file missing from lang/$reference/$file"
        );

        foreach ($lines[$reference] as $key => $line) {
            $this->assertNotSame('', trim($lines[$other][$key]), "Empty translation for $file:$key in $other");
            $this->assertSame(
                self::placeholders($line),
                self::placeholders($lines[$other][$key]),
                "Placeholders differ for $file:$key"
            );
        }
    }

    /**
     * @param array<mixed> $array
     * @return array<string, string>
     */
    private static function flatten(array $array, string $prefix = ''): array
    {
        $out = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : "$prefix.$key";
            if (is_array($value)) {
                $out += self::flatten($value, $fullKey);
            } else {
                $out[$fullKey] = (string) $value;
            }
        }

        return $out;
    }

    /**
     * @return array<int, string>
     */
    private static function placeholders(string $line): array
    {
        preg_match_all('/:([a-z_]+)/i', $line, $matches);
        $names = array_unique($matches[1]);
        sort($names);

        return $names;
    }
}
