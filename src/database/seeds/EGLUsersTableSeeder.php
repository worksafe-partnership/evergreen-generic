<?php

use Illuminate\Database\Seeder;

class EGLUsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $hash = new Bhash();

        $suffix = env("EGC_PROJECT_NAME", "egl");
        $prefix = "egHappy";
        $password = $prefix.$suffix;

        DB::table('users')->insert([
            'name' => "Evergreen",
            'email' => "info@evergreen.co",
            'password' => $hash->make($password),
            'created_at' => Date("Y-m-d H:i:s")
        ]);
    }
}
