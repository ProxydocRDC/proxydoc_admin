<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class ShipmentsTraker extends BasePolicy { protected string $slug = 'ShipmentsTracker'; } // ou 'main::user'
