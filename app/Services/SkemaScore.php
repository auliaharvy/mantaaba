<?php

namespace App\Services;

use App\Models\Setting;

/** Perhitungan nilai akhir 4 dimensi (PRD 9.7). Murni, tanpa query: diuji unit test. */
class SkemaScore
{
    public const PREDICATES = [90 => 'Istimewa', 80 => 'Sangat Baik', 70 => 'Baik'];

    /**
     * @param  array{knowledge: float|int|string, skill: float|int|string, character: float|int|string, social_cohesion: float|int|string}  $scores
     * @param  array{knowledge: int, skill: int, character: int, social: int}  $weights
     * @return array{final_score: string, passed: bool, predicate: ?string}
     */
    public static function calculate(array $scores, array $weights, int $passScore, int $minCharacter): array
    {
        if (array_sum($weights) !== 100) {
            throw new \InvalidArgumentException('Total bobot penilaian harus 100.');
        }
        // dalam satuan seperseratus (sen) agar tidak ada galat float
        $hundredths = fn ($v) => (int) round(((float) $v) * 100);
        $sum = $hundredths($scores['knowledge']) * $weights['knowledge'] + $hundredths($scores['skill']) * $weights['skill']
            + $hundredths($scores['character']) * $weights['character'] + $hundredths($scores['social_cohesion']) * $weights['social'];
        $final = intdiv($sum + 50, 100); // /100 (bobot) dibulatkan setengah ke atas → seperseratus
        $finalScore = sprintf('%d.%02d', intdiv($final, 100), $final % 100);
        $passed = $final >= $passScore * 100 && $hundredths($scores['character']) >= $minCharacter * 100;
        $predicate = null;
        if ($passed) {
            foreach (self::PREDICATES as $min => $label) {
                if ($final >= $min * 100) {
                    $predicate = $label;
                    break;
                }
            }
        }

        return ['final_score' => $finalScore, 'passed' => $passed, 'predicate' => $predicate];
    }

    /** Bobot dari Setting (default Grand Design: K20 S25 C30 SC25). */
    public static function weights(): array
    {
        return Setting::current()->assessment_weights ?: Setting::DEFAULT_WEIGHTS;
    }
}
