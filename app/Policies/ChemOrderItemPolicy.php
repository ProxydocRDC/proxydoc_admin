<?php

namespace App\Policies;

use App\Models\ChemOrderItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChemOrderItemPolicy extends BasePolicy { protected string $slug = 'chem::order:item'; }
