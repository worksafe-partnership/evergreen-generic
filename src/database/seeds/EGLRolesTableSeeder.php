<?php

use App\User;
use Evergreen\Generic\App\Role;
use Illuminate\Database\Seeder;

class EGLRolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $perms = [];
        foreach (Gate::abilities() as $ability => $closure) {
            $perms[$ability] = 1;
        }

        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'permissions' => $perms
        ]);

        $users = User::all();
        foreach ($users as $user) {
            $user->roles()->attach([$role->id]);
        }
    }
}
