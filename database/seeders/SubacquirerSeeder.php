<?php

namespace Database\Seeders;

use App\Enums\SubacquirerTypeEnum;
use App\Models\Subacquirer;
use Illuminate\Database\Seeder;

class SubacquirerSeeder extends Seeder
{
    public function run(): void
    {
        Subacquirer::updateOrCreate(
            ['id' => SubacquirerTypeEnum::SUBADQ_A->value],
            [
                'name' => 'SubadqA',
                'base_url' => 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io',
                'active' => true,
            ]
        );

        Subacquirer::updateOrCreate(
            ['id' => SubacquirerTypeEnum::SUBADQ_B->value],
            [
                'name' => 'SubadqB',
                'base_url' => 'https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io',
                'active' => true,
            ]
        );
    }
}

