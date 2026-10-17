<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BiometricDevice;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BiometricDeviceController extends Controller
{
    public function index(): View
    {
        return view('biometric-devices.index', [
            'devices' => BiometricDevice::with('branch')->orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'punches' => Attendance::where('source', 'biometric')
                ->with(['employee', 'branch'])
                ->latest('updated_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        $token = BiometricDevice::generateToken();

        BiometricDevice::create($data + ['token_hash' => BiometricDevice::hashToken($token)]);

        return back()->with('ok', 'Device registered.')->with('new_device_token', $token);
    }

    public function regenerate(BiometricDevice $device): RedirectResponse
    {
        $token = $device->issueToken();

        return back()->with('ok', 'Token regenerated.')->with('new_device_token', $token);
    }

    public function update(Request $request, BiometricDevice $device): RedirectResponse
    {
        $device->update(['is_active' => $request->boolean('is_active')]);

        return back()->with('ok', 'Device updated.');
    }

    public function destroy(BiometricDevice $device): RedirectResponse
    {
        $device->delete();

        return back()->with('ok', 'Device removed.');
    }
}
