<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

trait HasImageScopes
{
    public function scopeWithoutImages(EloquentBuilder $query): EloquentBuilder
    {
        $driver = $query->getModel()->getConnection()->getDriverName();

        return $query->where(function ($qq) use ($driver) {
            $qq->whereNull('images')
               ->orWhere('images', '=', '')
               ->orWhere('images', '=', '[]');

            if ($driver === 'mysql') {
                $qq->orWhereRaw('JSON_LENGTH(images) = 0');
            } elseif (in_array($driver, ['pgsql','postgres','postgresql'], true)) {
                $qq->orWhereRaw('jsonb_array_length(images::jsonb) = 0');
            }
        });
    }
}
