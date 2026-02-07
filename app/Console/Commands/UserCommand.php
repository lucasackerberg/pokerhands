<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:manage {action} {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage user permissions and settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $email = $this->argument('email');

        switch ($action) {
            case 'grant-organize':
                $this->grantOrganize($email);
                break;

            case 'revoke-organize':
                $this->revokeOrganize($email);
                break;

            case 'list-organizers':
                $this->listOrganizers();
                break;

            case 'list':
                $this->listUsers();
                break;

            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions:");
                $this->info("  grant-organize {email}  - Grant organize permission to a user");
                $this->info("  revoke-organize {email} - Revoke organize permission from a user");
                $this->info("  list-organizers         - List all users with organize permission");
                $this->info("  list                    - List all users");
                return 1;
        }

        return 0;
    }

    protected function grantOrganize($email)
    {
        if (!$email) {
            $email = $this->ask('Enter user email');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return;
        }

        if ($user->can_organize) {
            $this->warn("{$user->name} already has organize permission");
            return;
        }

        $user->can_organize = true;
        $user->save();

        $this->info("✓ Granted organize permission to {$user->name} ({$user->email})");
    }

    protected function revokeOrganize($email)
    {
        if (!$email) {
            $email = $this->ask('Enter user email');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return;
        }

        if (!$user->can_organize) {
            $this->warn("{$user->name} doesn't have organize permission");
            return;
        }

        $user->can_organize = false;
        $user->save();

        $this->info("✓ Revoked organize permission from {$user->name} ({$user->email})");
    }

    protected function listOrganizers()
    {
        $organizers = User::where('can_organize', true)->get();

        if ($organizers->isEmpty()) {
            $this->warn("No users have organize permission");
            return;
        }

        $this->info("Users with organize permission:");
        $this->table(
            ['ID', 'Name', 'Email'],
            $organizers->map(fn($u) => [$u->id, $u->name, $u->email])
        );
    }

    protected function listUsers()
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->warn("No users found");
            return;
        }

        $this->info("All users:");
        $this->table(
            ['ID', 'Name', 'Email', 'Can Organize'],
            $users->map(fn($u) => [$u->id, $u->name, $u->email, $u->can_organize ? '✓' : '✗'])
        );
    }
}
