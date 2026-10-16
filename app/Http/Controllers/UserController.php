<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with(['branch', 'employee'])->orderBy('name')->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.form', [
            'user' => new User(['role' => 'employee', 'is_active' => true]),
            'branches' => Branch::orderBy('name')->get(),
            'employees' => Employee::whereNull('user_id')->orderBy('last_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in($this->roles())],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'is_active' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'branch_id' => $data['branch_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['employee_id'])) {
            Employee::whereKey($data['employee_id'])->update(['user_id' => $user->id]);
        }

        return redirect()->route('users.index')->with('ok', 'User account created.');
    }

    public function edit(User $user): View
    {
        return view('users.form', [
            'user' => $user,
            'branches' => Branch::orderBy('name')->get(),
            'employees' => Employee::where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))->orderBy('last_name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in($this->roles())],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'is_active' => ['boolean'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'branch_id' => $data['branch_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        Employee::where('user_id', $user->id)->update(['user_id' => null]);
        if (! empty($data['employee_id'])) {
            Employee::whereKey($data['employee_id'])->update(['user_id' => $user->id]);
        }

        return redirect()->route('users.index')->with('ok', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('ok', 'You cannot delete your own account.');
        }
        $user->delete();

        return back()->with('ok', 'User deleted.');
    }

    private function roles(): array
    {
        return [
            User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_HR,
            User::ROLE_PAYROLL, User::ROLE_MANAGER, User::ROLE_EMPLOYEE,
        ];
    }
}
