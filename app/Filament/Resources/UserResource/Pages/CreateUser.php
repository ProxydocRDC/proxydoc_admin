<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);

        $data['account_id'] = null;
        $data['created_by'] = Auth::id() ?? 0;
        $data['otp']        = $this->generateUniqueOtp();

        return $data;
    }

    protected function generateUniqueOtp(): string
    {
        do {
            $otp = Str::random(6);
        } while (User::where('otp', $otp)->exists());

        return $otp;
    }
}
