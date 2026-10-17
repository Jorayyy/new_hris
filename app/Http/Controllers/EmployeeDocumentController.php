<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $view = $request->string('view')->value() === 'missing' ? 'missing' : 'expiring';

        return view('documents.index', [
            'view' => $view,
            'issues' => $view === 'missing' ? $this->missingDocuments($request) : $this->expiringDocuments($request),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'document_type_id' => ['required', 'exists:document_types,id'],
            'title' => ['nullable', 'string', 'max:150'],
            'expires_at' => ['nullable', 'date'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $type = DocumentType::findOrFail($data['document_type_id']);

        if ($type->requires_expiry && blank($data['expires_at'] ?? null)) {
            throw ValidationException::withMessages(['expires_at' => 'This document type needs an expiry date.']);
        }

        $employee->documents()->create([
            'document_type_id' => $type->id,
            'type' => $type->name,
            'title' => $data['title'] ?? $type->name,
            'expires_at' => $data['expires_at'] ?? null,
            'file_path' => $request->file('file')->store('documents', 'public'),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('ok', 'Document uploaded.');
    }

    public function destroy(EmployeeDocument $document): RedirectResponse
    {
        $document->delete();

        return back()->with('ok', 'Document removed.');
    }

    private function expiringDocuments(Request $request): LengthAwarePaginator
    {
        return EmployeeDocument::query()
            ->with(['employee.branch', 'documentType'])
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays(EmployeeDocument::EXPIRY_WARN_DAYS))
            ->whereHas('employee', fn (Builder $q) => $this->employeeFilter($q, $request))
            ->orderBy('expires_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (EmployeeDocument $document) => [
                'employee' => $document->employee,
                'type' => $document->documentType,
                'document' => $document,
                'state' => $document->expiryState(),
            ]);
    }

    private function missingDocuments(Request $request): LengthAwarePaginator
    {
        $rows = new Collection;

        foreach (DocumentType::query()->required()->get() as $type) {
            $employees = $this->employeeFilter(Employee::query()->with('branch'), $request)
                ->whereDoesntHave('documents', fn (Builder $q) => $q->where('document_type_id', $type->id))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            foreach ($employees as $employee) {
                $rows->push([
                    'employee' => $employee,
                    'type' => $type,
                    'document' => null,
                    'state' => 'missing',
                ]);
            }
        }

        $rows = $rows->sortBy(fn (array $row) => $row['employee']->last_name.$row['employee']->first_name)->values();
        $page = max(1, $request->integer('page', 1));

        return (new Paginator($rows->forPage($page, 25), $rows->count(), 25, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]))->appends($request->query());
    }

    private function employeeFilter(Builder $query, Request $request): Builder
    {
        return $query
            ->where('is_active', true)
            ->when($request->filled('q'), function (Builder $q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn (Builder $w) => $w->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('employee_no', 'like', $term));
            })
            ->when($request->filled('branch'), fn (Builder $q) => $q->where('branch_id', $request->integer('branch')));
    }
}
