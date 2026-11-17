<?php

namespace App\Http\Controllers;

use App\Models\StaffRegistrationRequest;
use App\Models\User;
use App\Support\RegistrationRoles;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.staff-registration', ['roles' => RegistrationRoles::available()]);
    }

    public function store(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $roles = RegistrationRoles::available();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:8', 'confirmed'],
            'role_id' => ['required', 'integer', Rule::in($roles->modelKeys())],
        ]);
        $role = $roles->firstWhere('id', (int) $data['role_id']);

        try {
            DB::transaction(function () use ($data, $role): void {
                if (User::whereRaw('LOWER(email) = ?', [$data['email']])->exists()) {
                    throw ValidationException::withMessages(['email' => 'An account already uses this email. Contact your administrator.']);
                }
                $existing = StaffRegistrationRequest::where('email', $data['email'])->lockForUpdate()->first();
                if ($existing && $existing->status !== 'rejected') {
                    throw ValidationException::withMessages(['email' => 'A request for this email already exists. Contact your administrator.']);
                }
                $values = ['name' => $data['name'], 'email' => $data['email'], 'password_hash' => Hash::make($data['password']),
                    'requested_role_id' => $role->id, 'requested_role_name' => $role->name, 'status' => 'pending',
                    'reviewed_by' => null, 'reviewed_at' => null, 'approved_user_id' => null];
                if ($existing) $existing->update($values);
                else StaffRegistrationRequest::create($values);
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505', '19'], true)) {
                throw ValidationException::withMessages(['email' => 'A request for this email already exists. Contact your administrator.']);
            }
            throw $exception;
        }

        return redirect()->route('login')->with('status', 'Your registration request is awaiting administrator approval. You can sign in after it is approved.');
    }
}
