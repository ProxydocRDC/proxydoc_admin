<?php

namespace App\Policies\Widgets;

use App\Policies\BaseNamedPermissionPolicy;

class PatientStatsWidgetPolicy extends BaseNamedPermissionPolicy
{
    protected string $viewPermission = 'widget_PatientStatsWidget';
}
