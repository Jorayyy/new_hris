<?php

namespace App\Http\Controllers;

use App\Models\ClearanceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClearanceItemController extends Controller
{
    public function update(Request $request, ClearanceItem $clearanceItem): RedirectResponse
    {
        $request->validate(['is_done' => ['required', 'boolean']]);

        $done = $request->boolean('is_done');

        $clearanceItem->update([
            'is_done' => $done,
            'done_by' => $done ? $request->user()->id : null,
            'done_at' => $done ? now() : null,
        ]);

        return back()->with('ok', 'Clearance updated.');
    }
}
