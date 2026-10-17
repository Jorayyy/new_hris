<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'NBI Clearance', 'is_required' => true, 'requires_expiry' => true],
            ['name' => 'Police Clearance', 'is_required' => true, 'requires_expiry' => true],
            ['name' => 'Barangay Clearance', 'is_required' => true, 'requires_expiry' => true],
            ['name' => 'Medical Certificate', 'is_required' => true, 'requires_expiry' => true],
            ['name' => 'SSS ID', 'is_required' => true],
            ['name' => 'PhilHealth ID', 'is_required' => true],
            ['name' => 'Pag-IBIG ID', 'is_required' => true],
            ['name' => 'TIN ID', 'is_required' => true],
            ['name' => 'Birth Certificate', 'is_required' => true],
            ['name' => 'Diploma / Transcript of Records', 'is_required' => true],
            ['name' => 'Signed Employment Contract', 'is_required' => true],
            ['name' => 'Company ID'],
            ['name' => '2x2 Photo'],
            ['name' => 'Resume / CV'],
        ];

        foreach ($types as $sort => $type) {
            DocumentType::updateOrCreate(
                ['name' => $type['name']],
                $type + ['is_required' => false, 'requires_expiry' => false, 'is_active' => true, 'sort' => $sort],
            );
        }
    }
}
