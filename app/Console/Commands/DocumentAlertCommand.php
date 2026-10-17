<?php

namespace App\Console\Commands;

use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Support\Notify;
use Illuminate\Console\Command;

class DocumentAlertCommand extends Command
{
    protected $signature = 'documents:alerts';

    protected $description = 'Notify HR about missing, expired and expiring 201-file documents.';

    public function handle(): int
    {
        $expiring = EmployeeDocument::query()
            ->with('employee')
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays(EmployeeDocument::EXPIRY_WARN_DAYS))
            ->whereHas('employee', fn ($q) => $q->where('is_active', true))
            ->orderBy('expires_at')
            ->get();

        $missing = 0;

        foreach (DocumentType::query()->required()->get() as $type) {
            $missing += Employee::query()
                ->where('is_active', true)
                ->whereDoesntHave('documents', fn ($q) => $q->where('document_type_id', $type->id))
                ->count();
        }

        if ($expiring->isEmpty() && $missing === 0) {
            $this->info('No document issues found.');

            return self::SUCCESS;
        }

        $body = $expiring->count().' document(s) expiring or expired, '.$missing.' missing required document(s).';

        $sample = $expiring->take(10)->map(function (EmployeeDocument $document) {
            $state = $document->expiryState() === 'expired' ? 'expired' : 'expires';

            return $document->employee->full_name.' — '.$document->type.' '.$state.' '.$document->expires_at->format('M d, Y');
        })->implode("\n");

        if ($sample !== '') {
            $body .= "\n".$sample;
        }

        Notify::hr('201-file document compliance', $body, route('documents.index'), '▦');

        $this->info("Notified HR: {$expiring->count()} expiring/expired, {$missing} missing.");

        return self::SUCCESS;
    }
}
