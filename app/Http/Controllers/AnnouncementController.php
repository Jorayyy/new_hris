<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('announcements.index', ['announcements' => Announcement::with(['branch', 'author'])->latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('announcements.form', [
            'announcement' => new Announcement(['published_at' => now()]),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Announcement::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('announcements.index')->with('ok', 'Announcement posted.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('announcements.form', [
            'announcement' => $announcement,
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));

        return redirect()->route('announcements.index')->with('ok', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('ok', 'Announcement deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
        ]);
    }
}
