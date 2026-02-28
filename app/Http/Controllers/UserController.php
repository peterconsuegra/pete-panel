<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controller;
use App\Models\User; 
use Illuminate\Support\Facades\Hash; 

final class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            if (! Gate::allows('user.admin')) {
                return redirect()
                    ->route('sites.index')
                    ->with('error', true)
                    ->with('error_message', 'This area is restricted to administrators only. Please contact your admin if you need access..');
            }
            return $next($request);
        });
    }

    /** GET /users/my-account */
    public function myAccount(Request $request): View
    {
        $currentUser = Auth::user();
        $viewsw      = 'sites';

        return view('users.myaccount', compact('currentUser', 'viewsw'));
    }

    /** POST /users/my-account */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Validate request input
        $data = $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        // Reject if current password does not match
        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Your current password is incorrect.',
            ]);
        }

        // 🔄 Update password
        $user->password = Hash::make($data['password']);
        $user->save();

        return redirect()->to('/users')->with('success', 'User created successfully.');
    }

    /** GET /admin/users */
    public function index(Request $request): View
    {
        Gate::authorize('user.admin');

        // Per-page from request with sane bounds (1..50). Default: 10
        $perPage = (int) $request->integer('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $users = User::query()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $viewsw       = 'users';
        $currentUser  = Auth::user();

        return view('users.index', compact('users', 'viewsw', 'currentUser'));
    }

    /** GET /admin/users/create */
    public function create(): View
    {
        Gate::authorize('user.admin');

        $user         = new User();
        $viewsw       = 'users';
        $currentUser = Auth::user();

        return view('users.create', compact('user', 'viewsw', 'currentUser'));
    }

    /** POST /admin/users */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('user.admin');

        $data = $request->validate([
            'name'     => ['required', 'regex:/^[a-zA-Z0-9-_]+$/', 'unique:users,name'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'name.regex' => 'Only letters, numbers, dashes and underscores are allowed.',
        ]);

        $data['password'] = bcrypt($data['password']);
        User::create($data);

        return redirect()->to('/users')->with('success', 'User created successfully.');
    }

    /** GET /admin/users/{user}/edit */
    public function edit(User $user): View
    {
        Gate::authorize('user.admin');

        $viewsw       = 'users';
        $currentUser = Auth::user();

        return view('users.edit', compact('user', 'viewsw', 'currentUser'));
    }

    /** PUT/PATCH /admin/users/{user} */
    public function update(Request $request, User $user): RedirectResponse
    {
       Gate::authorize('user.admin');

        $data = $request->validate([
            'name'     => [
                'sometimes', 'required',
                'regex:/^[a-zA-Z0-9-_]+$/',
                Rule::unique('users', 'name')->ignore($user->id),
            ],
            'email'    => [
                'sometimes', 'required', 'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:6'],
        ], [
            'name.regex' => 'Only letters, numbers, dashes and underscores are allowed.',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->fill($data)->save();

        return redirect()->to('/users')->with('success', 'User updated successfully.');
    }

    /** DELETE /admin/users/{user} */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('user.admin');

        if ((int) $user->id === (int) Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
