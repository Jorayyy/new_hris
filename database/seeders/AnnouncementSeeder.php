<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email', 'hr@hris.test')->first();
        $hq = Branch::where('code', 'TAC')->first();

        $items = [
            [null, 'Payroll cut-off moved to Friday', 'This week\'s payroll cut-off moves to Friday 6PM. File all DTR corrections before then so they land on this run.', 1],
            [null, 'Holiday pay reminder', 'Regular holidays are paid at 200% for hours worked. Unworked regular holidays are still paid at 100%.', 4],
            [null, 'Keep your time-clock PIN private', 'Never share your PIN. If it is not working, visit HR to have it reset — do not borrow a teammate\'s.', 9],
            [$hq?->id, 'Tacloban HQ graveyard shift', 'All Tacloban HQ staff are on the graveyard shift (8PM-6AM or 9PM-6AM), Mondays to Fridays only.', 14],
            [null, 'Free flu vaccination', 'Free flu shots at the Tacloban clinic this Saturday. Sign up at the HR desk.', 21],
        ];

        foreach ($items as [$branchId, $title, $body, $daysAgo]) {
            Announcement::create([
                'branch_id' => $branchId,
                'title' => $title,
                'body' => $body,
                'published_at' => now()->subDays($daysAgo),
                'created_by' => $author?->id,
            ]);
        }
    }
}
