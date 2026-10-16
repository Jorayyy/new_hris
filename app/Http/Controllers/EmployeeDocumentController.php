<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeDocumentController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:150'],
            'expires_at' => ['nullable', 'date'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $path = $request->file('file')->store('documents', 'public');

        $employee->documents()->create([
            'type' => $data['type'],
            'title' => $data['title'],
            'expires_at' => $data['expires_at'] ?? null,
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('ok', 'Document uploaded.');
    }

    public function destroy(EmployeeDocument $document): RedirectResponse
    {
        $document->delete();

        return back()->with('ok', 'Document removed.');
    }
}
