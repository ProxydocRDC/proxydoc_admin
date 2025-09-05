<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class MainUserPolicy extends BasePolicy { protected string $slug = 'user'; } // ou 'main::user'
