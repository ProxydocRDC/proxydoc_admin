<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\ProxyPatient;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return UserResource::mutateFormDataBeforeSave($data);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        if (! empty($data['phone'])) {
            $phone = preg_replace('/\s+/', '', $data['phone']);
            if (preg_match('/^\+?(\d{2,3})(\d+)$/', $phone, $m)) {
                $data['phone_country_code'] = $m[1];
                $data['phone']              = $m[2];
            }
        }

        // Préremplir l'onglet Patient depuis la fiche patient existante
        $user = $this->record;
        $selfPatient = $user->patient()->where('relation', 'self')->first();
        if ($selfPatient) {
            $data['activate_as_patient'] = true;
            $data['patient_fullname'] = $selfPatient->fullname;
            $data['patient_birthdate'] = $selfPatient->birthdate?->format('Y-m-d');
            $data['patient_gender'] = $selfPatient->gender;
            $data['patient_blood_group'] = $selfPatient->blood_group;
            $data['patient_relation'] = $selfPatient->relation;
            $data['patient_phone'] = $selfPatient->phone;
            $data['patient_email'] = $selfPatient->email;
            $data['patient_allergies'] = $selfPatient->allergies;
            $data['patient_chronic_conditions'] = $selfPatient->chronic_conditions;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncPatientFromFormData($this->record);
    }

    protected function syncPatientFromFormData(User $user): void
    {
        $data = UserResource::$pendingPatientData ?? $this->form->getState();
        $patientKeys = array_filter(array_keys($data ?? []), fn ($k) => str_starts_with((string) $k, 'patient_') || $k === 'activate_as_patient');
        $data = $data ? array_intersect_key($data, array_flip($patientKeys)) : null;

        if (! $data || empty($data['activate_as_patient'])) {
            return;
        }

        $patient = $user->patient()->where('relation', 'self')->first();
        $payload = [
            'fullname'           => $data['patient_fullname'] ?? trim($user->firstname . ' ' . $user->lastname),
            'birthdate'          => $data['patient_birthdate'] ?? $user->birth_date,
            'gender'             => $data['patient_gender'] ?? ($user->gender === 'M' ? 'male' : ($user->gender === 'F' ? 'female' : 'other')),
            'blood_group'        => $data['patient_blood_group'] ?? null,
            'relation'           => $data['patient_relation'] ?? 'self',
            'phone'              => $data['patient_phone'] ?? $user->phone,
            'email'              => $data['patient_email'] ?? $user->email,
            'allergies'          => $data['patient_allergies'] ?? null,
            'chronic_conditions' => $data['patient_chronic_conditions'] ?? null,
            'status'             => 1,
            'updated_by'         => Auth::id() ?? $user->id,
        ];

        if ($patient) {
            $patient->update($payload);
        } else {
            ProxyPatient::create(array_merge($payload, [
                'user_id'   => $user->id,
                'created_by' => Auth::id() ?? $user->id,
            ]));
        }

        UserResource::$pendingPatientData = null;
    }
}
