<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Code
{
    /** Initiales propres d’un nom (sans articles) */
    public static function initials(?string $name): string
    {
        $name = Str::of((string) $name)->squish();
        if ($name->isEmpty()) {
            return 'XXX';
        }

        $words = preg_split('/[\s\-]+/u', $name);
        $stop  = ['de','du','des','la','le','les',"l'","d'",'et','en','à','au','aux','pour','sur','sous','chez'];

        $init = collect($words)
            ->filter()
            ->reject(fn($w) => in_array(mb_strtolower($w), $stop, true))
            ->map(fn($w) => Str::upper(Str::substr(Str::of($w)->replace(["l'","d'"], ''), 0, 1)))
            ->implode('');

        return $init ?: Str::upper(Str::substr(Str::slug((string) $name), 0, 3));
    }

    /** PROXY-ABC-12345 (sans vérif d’unicité) */
    public static function make(?string $name, string $prefix = 'PROXY', int $digits = 5): string
    {
        $n = random_int(10 ** ($digits - 1), (10 ** $digits) - 1);
        return sprintf('%s-%s-%s', $prefix, self::initials($name), $n);
    }

    /** Même chose mais garanti unique en base (colonne par défaut: code) */
    public static function unique(?string $name, string $prefix, Model|string $modelClass, string $column = 'code', int $digits = 5, int $tries = 8): string
    {
        $class = is_string($modelClass) ? $modelClass : $modelClass::class;

        do {
            $code = self::make($name, $prefix, $digits);
            $exists = $class::query()->where($column, $code)->exists();
        } while ($exists && --$tries > 0);

        if ($exists) {
            throw new \RuntimeException('Impossible de générer un code unique après plusieurs tentatives.');
        }

        return $code;
    }
}
