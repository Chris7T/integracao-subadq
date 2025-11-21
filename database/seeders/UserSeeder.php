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
        User::updateOrCreate(
            ['email' => 'usuario.a@example.com'],
            [
                'name' => 'Usuário A',
                'password' => Hash::make('password'),
                'cpf' => '12345678900',
                'subacquirer_id' => SubacquirerTypeEnum::SUBADQ_A->value,
            ]
        );

        User::updateOrCreate(
            ['email' => 'usuario.b@example.com'],
            [
                'name' => 'Usuário B',
                'password' => Hash::make('password'),
                'cpf' => '98765432100',
                'subacquirer_id' => SubacquirerTypeEnum::SUBADQ_A->value,
            ]
        );

        User::updateOrCreate(
            ['email' => 'usuario.c@example.com'],
            [
                'name' => 'Usuário C',
                'password' => Hash::make('password'),
                'cpf' => '11122233344',
                'subacquirer_id' => SubacquirerTypeEnum::SUBADQ_B->value,
            ]
        );
    }
}

