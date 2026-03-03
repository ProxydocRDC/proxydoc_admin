<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

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
}
