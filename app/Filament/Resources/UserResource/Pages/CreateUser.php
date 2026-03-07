<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\ProxyPatient;
use App\Models\User;
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

    protected function afterCreate(): void
    {
        $this->syncPatientFromFormData($this->record);
    }

    protected function syncPatientFromFormData(User $user): void
    {
        $data = UserResource::$pendingPatientData;
        if (! $data || empty($data['activate_as_patient'])) {
            return;
        }

        $patient = $user->selfPatient ?? $user->patient()->where('relation', 'self')->first();
        $payload = [
            'fullname'          => $data['patient_fullname'] ?? trim($user->firstname . ' ' . $user->lastname),
            'birthdate'         => $data['patient_birthdate'] ?? $user->birth_date,
            'gender'            => $data['patient_gender'] ?? ($user->gender === 'M' ? 'male' : ($user->gender === 'F' ? 'female' : 'other')),
            'blood_group'       => $data['patient_blood_group'] ?? null,
            'relation'          => $data['patient_relation'] ?? 'self',
            'phone'             => $data['patient_phone'] ?? $user->phone,
            'email'             => $data['patient_email'] ?? $user->email,
            'allergies'         => $data['patient_allergies'] ?? null,
            'chronic_conditions' => $data['patient_chronic_conditions'] ?? null,
            'status'            => 1,
            'created_by'        => $user->id, // user parent pour les patients
            'updated_by'        => Auth::id() ?? $user->id,
        ];

        if ($patient) {
            $patient->update($payload);
        } else {
            ProxyPatient::create(array_merge($payload, [
                'user_id' => $user->id,
            ]));
        }

        UserResource::$pendingPatientData = null;
    }

    protected function generateUniqueOtp(): string
    {
        do {
            $otp = Str::random(6);
        } while (User::where('otp', $otp)->exists());

        return $otp;
    }
}
