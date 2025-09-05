<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AssignRoleToUser extends Command
{
    // $user = User::find(1);
    // $user->roles()->attach(2); // Role_id 2 par ex.

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-role-to-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
