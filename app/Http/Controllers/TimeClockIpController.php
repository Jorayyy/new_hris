<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TimeClockIp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimeClockIpController extends Controller
{
    public function index(Request $request): View
    {
        return view('settings.time-clock', [
            'ips' => TimeClockIp::orderBy('cidr')->get(),
            'currentIp' => $request->ip(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cidr' => ['required', 'string', 'max:64', function (string $attribute, mixed $value, \Closure $fail) {
                if (! TimeClockIp::isValid((string) $value)) {
                    $fail('Enter a valid IP address or CIDR range, e.g. 203.0.113.10 or 192.168.1.0/24.');
                }
            }],
            'label' => ['nullable', 'string', 'max:80'],
        ]);

        $cidr = trim($data['cidr']);
        TimeClockIp::updateOrCreate(['cidr' => $cidr], ['label' => $data['label'] ?? null, 'is_active' => true]);
        AuditLog::record('time-clock', 'Allowed punch IP added: '.$cidr);

        return back()->with('ok', 'Allowed IP saved.');
    }

    public function update(Request $request, TimeClockIp $ip): RedirectResponse
    {
        $ip->update(['is_active' => $request->boolean('is_active')]);
        AuditLog::record('time-clock', 'Punch IP '.$ip->cidr.' '.($ip->is_active ? 'enabled' : 'disabled'), $ip);

        return back()->with('ok', 'IP updated.');
    }

    public function destroy(TimeClockIp $ip): RedirectResponse
    {
        AuditLog::record('time-clock', 'Allowed punch IP removed: '.$ip->cidr);
        $ip->delete();

        return back()->with('ok', 'IP removed.');
    }
}
