<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadTest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_public'      => 'boolean',
        'multiple_files' => 'array',   // Filament FileUpload multiple -> array en PHP
    ];
}
