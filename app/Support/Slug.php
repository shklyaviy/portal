<?php

namespace App\Support;

class Slug
{
    /** @var array<string, string> */
    private const MAP = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
        'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];

    public static function make(string $input): string
    {
        $input = mb_strtolower(trim($input), 'UTF-8');
        $out = '';

        $len = mb_strlen($input, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($input, $i, 1, 'UTF-8');
            if (isset(self::MAP[$ch])) {
                $out .= self::MAP[$ch];
            } elseif (preg_match('/[a-z0-9]/u', $ch)) {
                $out .= $ch;
            } elseif ($ch === '/' || $ch === '-' || preg_match('/[\s_]/u', $ch)) {
                $out .= '-';
            }
        }

        $out = preg_replace('/-+/', '-', $out) ?? '';
        $out = trim($out, '-');

        return $out !== '' ? $out : 'item';
    }
}
