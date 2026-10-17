<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentTypeController extends Controller
{
    public function index(): View
    {
        return view('documents.types', [
            'types' => DocumentType::withCount('documents')->orderBy('sort')->orderBy('name')->paginate(30),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        DocumentType::create($this->validated($request));

        return back()->with('ok', 'Document type created.');
    }

    public function update(Request $request, DocumentType $documentType): RedirectResponse
    {
        $documentType->update($this->validated($request));

        return back()->with('ok', 'Document type updated.');
    }

    public function destroy(DocumentType $documentType): RedirectResponse
    {
        if ($documentType->documents()->exists()) {
            return back()->with('error', 'Cannot delete a document type that is in use.');
        }

        $documentType->delete();

        return back()->with('ok', 'Document type deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]) + [
            'is_required' => $request->boolean('is_required'),
            'requires_expiry' => $request->boolean('requires_expiry'),
            'is_active' => $request->boolean('is_active'),
            'sort' => $request->integer('sort'),
        ];
    }
}
