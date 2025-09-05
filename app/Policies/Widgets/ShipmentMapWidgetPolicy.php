<?php

namespace App\Policies\Widgets;

use App\Policies\BaseNamedPermissionPolicy;

class ShipmentMapWidgetPolicy extends BaseNamedPermissionPolicy
{
    // 🔁 adapte au nom de ta permission en BD
    protected string $viewPermission = 'widget_ShipmentMapWidget';
}
