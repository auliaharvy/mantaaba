<?php

namespace Tests\Unit;

use App\Services\SkemaScore;
use PHPUnit\Framework\TestCase;

/** Perhitungan nilai akhir 4 dimensi (PRD 9.7). */
class SkemaScoreTest extends TestCase
{
    private const WEIGHTS = ['knowledge' => 20, 'skill' => 25, 'character' => 30, 'social' => 25];

    private function calc(array $s, int $pass = 70, int $minC = 70, array $w = self::WEIGHTS): array
    {
        return SkemaScore::calculate(array_combine(['knowledge', 'skill', 'character', 'social_cohesion'], $s), $w, $pass, $minC);
    }

    public function test_weighted_final_score_from_prd_example()
    {
        // contoh wireframe PRD: 82, 85, 88, 90 → 16,4 + 21,25 + 26,4 + 22,5 = 86,55
        $this->assertSame(['final_score' => '86.55', 'passed' => true, 'predicate' => 'Sangat Baik'], $this->calc([82, 85, 88, 90]));
    }

    public function test_character_below_minimum_fails_even_with_high_final()
    {
        $result = $this->calc([95, 95, 65, 95]);
        $this->assertSame('86.00', $result['final_score']);
        $this->assertFalse($result['passed']);
        $this->assertNull($result['predicate']);
    }

    public function test_predicate_boundaries_and_rounding()
    {
        $this->assertSame('Istimewa', $this->calc([90, 90, 90, 90])['predicate']);
        $this->assertSame('Baik', $this->calc([70, 70, 70, 70])['predicate']);
        // 69,998 dibulatkan 2 desimal = 70,00 → lulus (nilai akhir dibandingkan setelah pembulatan, PRD 9.7)
        $this->assertSame(['final_score' => '70.00', 'passed' => true, 'predicate' => 'Baik'], $this->calc([69.99, 70, 70, 70]));
        $this->assertFalse($this->calc([69.97, 70, 70, 70])['passed']); // 69,994 → 69,99
        $this->assertSame('79.99', $this->calc([79.97, 80, 80, 79.99])['final_score']);
        $this->assertSame('Baik', $this->calc([79.97, 80, 80, 79.99])['predicate']);
    }

    public function test_custom_pass_score_and_weights()
    {
        $this->assertTrue($this->calc([60, 60, 70, 60], 60)['passed']);
        $this->assertSame('100.00', $this->calc([100, 100, 100, 100], 70, 70, ['knowledge' => 25, 'skill' => 25, 'character' => 25, 'social' => 25])['final_score']);
    }

    public function test_weights_must_total_one_hundred()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->calc([80, 80, 80, 80], 70, 70, ['knowledge' => 20, 'skill' => 25, 'character' => 30, 'social' => 30]);
    }
}
