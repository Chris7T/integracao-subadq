<?php

namespace Database\Seeders;

use App\Enums\SubacquirerTypeEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Usuário A',
            'email' => 'usuario.a@example.com',
            'password' => Hash::make('password'),
            'cpf' => '12345678900',
            'subacquirer_id' => SubacquirerTypeEnum::SUBADQ_A->value,
        ]);

        User::create([
            'name' => 'Usuário B',
            'email' => 'usuario.b@example.com',
            'password' => Hash::make('password'),
            'cpf' => '98765432100',
            'subacquirer_id' => SubacquirerTypeEnum::SUBADQ_A->value,
        ]);

        User::create([
            'name' => 'Usuário C',
            'email' => 'usuario.c@example.com',
            'password' => Hash::make('password'),
            'cpf' => '11122233344',
            'subacquirer_id' => SubacquirerTypeEnum::SUBADQ_B->value,
        ]);
    }
}

