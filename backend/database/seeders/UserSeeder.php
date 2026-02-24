<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'dev@studytrack.local'],
            [
                'name' => 'Dev User',
                'password' => Hash::make('password'),
                'timezone' => 'America/Sao_Paulo',
            ]
        );
    }
}
