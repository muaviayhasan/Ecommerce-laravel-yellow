<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates the owner super-admin accounts. Runs after RolePermissionSeeder so
     * the `super-admin` role exists. Change the passwords after first login.
     */
    public function run(): void
    {
        $owners = [
            'kingway736@gmail.com' => 'Kingway',
            'muaviayhasan@gmail.com' => 'Muaviay Hasan',
        ];

        $admins = [];
        foreach ($owners as $email => $name) {
            $admin = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            if (! $admin->hasRole('super-admin')) {
                $admin->assignRole('super-admin');
            }

            $admins[] = $admin;
        }

        // Retire the old test account. Its authored content would cascade away
        // on delete (reviews / blog posts), so hand it to the first owner first.
        if ($old = User::where('email', 'admin@gmail.com')->first()) {
            Review::where('user_id', $old->id)->update(['user_id' => $admins[0]->id]);
            BlogPost::where('author_id', $old->id)->update(['author_id' => $admins[0]->id]);
            $old->delete();
        }
    }
}
