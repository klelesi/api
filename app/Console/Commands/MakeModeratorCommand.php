<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class MakeModeratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-moderator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a user a moderator';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = null;
        do {
            $email = $this->ask('User\'s email?');
            $user = User::where('email', $email)->first();

            if (!$user) {
                $this->error('User not found. Try a different email.');
            }

        } while (!$user);

        $roles = Role::all('name')->map(function ($role) {return $role->name;})->toArray();
        $role = $this->choice("Which role would you like to add to $user->email?", $roles);

        $user->assignRole($role);

        $this->info('Success!');

    }
}
