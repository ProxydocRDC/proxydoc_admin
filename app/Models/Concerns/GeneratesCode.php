<?php

namespace App\Models\Concerns;

use App\Support\Code;

trait GeneratesCode
{
    protected static function bootGeneratesCode(): void
    {
        static::creating(function ($model) {
            $column = method_exists($model, 'getCodeColumn') ? $model->getCodeColumn() : 'code';
            $prefix = method_exists($model, 'getCodePrefix') ? $model->getCodePrefix() : 'PROXY';

            if (blank($model->{$column})) {
                $name = method_exists($model, 'getCodeNameSource')
                    ? $model->getCodeNameSource()
                    : ($model->name ?? null);

                $model->{$column} = Code::unique($name, $prefix, $model::class, $column);
            }
        });
    }

    // Valeurs par défaut (la classe peut surcharger ces méthodes)
    public function getCodeColumn(): string { return 'code'; }
    public function getCodePrefix(): string { return 'PROXY'; }

    // Optionnel: d'où vient le "name" servant aux initiales
    public function getCodeNameSource(): ?string { return $this->name ?? null; }
}
