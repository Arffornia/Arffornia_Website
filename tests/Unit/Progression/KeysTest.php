<?php

namespace Tests\Unit\Progression;

use App\Progression\Keys;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Keys must match docs/scripts/progression.py, or the graph tool and the website name milestones differently. */
class KeysTest extends TestCase
{
    public static function names(): array
    {
        return [
            'apostrophe becomes a separator' => ["Where there's a will", 'where_there_s_a_will'],
            'curly apostrophe is dropped' => ['Where there’s a will', 'where_theres_a_will'],
            'accent keeps its base letter' => ['Énergie électrique', 'energie_electrique'],
            'superscript digit is kept' => ['2³ Spacial Storage', '23_spacial_storage'],
            'nothing left' => ['!!!', 'unnamed'],
        ];
    }

    #[DataProvider('names')]
    public function test_slug_matches_the_python_converter(string $name, string $expected): void
    {
        $this->assertSame($expected, Keys::slug($name));
    }
}
