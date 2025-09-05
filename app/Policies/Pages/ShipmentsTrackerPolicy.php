<?php

namespace App\Policies\Pages;

use App\Policies\BaseNamedPermissionPolicy;

class ShipmentsTrackerPolicy extends BaseNamedPermissionPolicy
{
    // 🟢 Mets exactement la permission que tu as en BD :
    protected string $viewPermission = 'page_ShipmentsTracker';
}
