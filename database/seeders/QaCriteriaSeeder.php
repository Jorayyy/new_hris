<?php

namespace Database\Seeders;

use App\Models\QaCriterion;
use Illuminate\Database\Seeder;

class QaCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $criteria = [
            ['name' => 'Greeting and introduction', 'weight' => 10],
            ['name' => 'Verification and compliance statements', 'weight' => 15],
            ['name' => 'Product knowledge and accuracy', 'weight' => 25],
            ['name' => 'Resolution and ownership', 'weight' => 20],
            ['name' => 'Communication and soft skills', 'weight' => 15],
            ['name' => 'Documentation and notes', 'weight' => 15],
        ];

        foreach ($criteria as $sort => $criterion) {
            QaCriterion::updateOrCreate(
                ['name' => $criterion['name']],
                $criterion + ['is_active' => true, 'sort' => $sort],
            );
        }
    }
}
