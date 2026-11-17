<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffRegistrationRequest;
use App\Models\User;
use App\Support\RegistrationRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class StaffRegistrationReviewController extends Controller
{
    public function index(Request $request)
    {
        $this->superAdmin($request);
        $status = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])]])['status'] ?? 'pending';
        $requests = StaffRegistrationRequest::where('status', $status)->latest()->paginate(25)->withQueryString();
        return view('admin.users.registration-requests', ['requests' => $requests, 'roles' => RegistrationRoles::available(), 'status' => $status]);
    }

    public function approve(Request $request, StaffRegistrationRequest $registration)
    {
        $this->superAdmin($request);
        $roles = RegistrationRoles::available();
        $data = $request->validate(['role_id' => ['required', 'integer', Rule::in($roles->modelKeys())]]);
        $role = $roles->firstWhere('id', (int) $data['role_id']);
        DB::transaction(function () use ($request, $registration, $role): void {
            $registration = StaffRegistrationRequest::whereKey($registration->id)->lockForUpdate()->firstOrFail();
            if ($registration->status !== 'pending' || ! $registration->password_hash) {
                throw ValidationException::withMessages(['registration' => 'This request is no longer pending.']);
            }
            if (User::where('email', $registration->email)->exists()) {
                throw ValidationException::withMessages(['registration' => 'An account already uses this email. Review it in Staff accounts; it was not linked or changed.']);
            }
            $role = Role::whereKey($role->id)->lockForUpdate()->first();
            if (! $role || $role->guard_name !== 'web' || in_array(mb_strtolower(trim($role->name)), ['super administrator', 'super-admin'], true)) {
                throw ValidationException::withMessages(['role_id' => 'This role is no longer available for approval.']);
            }
            if (! $role->hasPermissionTo('access-admin')) {
                throw ValidationException::withMessages(['role_id' => 'Give this role workspace access in Role permissions before approving the account.']);
            }
            $user = User::create(['name' => $registration->name, 'email' => $registration->email, 'password' => $registration->password_hash]);
            $user->syncRoles([$role]);
            $registration->update(['status' => 'approved', 'password_hash' => null, 'approved_user_id' => $user->id,
                'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        });
        return redirect()->route('admin.registration-requests.index')->with('status', 'Request approved and staff account created. The applicant can now sign in.');
    }

    public function reject(Request $request, StaffRegistrationRequest $registration)
    {
        $this->superAdmin($request);
        DB::transaction(function () use ($request, $registration): void {
            $registration = StaffRegistrationRequest::whereKey($registration->id)->lockForUpdate()->firstOrFail();
            if ($registration->status !== 'pending') throw ValidationException::withMessages(['registration' => 'This request is no longer pending.']);
            $registration->update(['status' => 'rejected', 'password_hash' => null, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        });
        return redirect()->route('admin.registration-requests.index')->with('status', 'Request rejected. The applicant may submit a new request later.');
    }

    private function superAdmin(Request $request): void { abort_unless($request->user()?->isSuperAdmin(), 403); }
}
