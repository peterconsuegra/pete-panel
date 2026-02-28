<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Option;                              // Eloquent model for the "options" table
use App\Services\PeteService;                // Domain service (injected for future actions / cache busting, etc.)
use Illuminate\View\View;                      // View for return types
use Illuminate\Http\RedirectResponse;        // Redirect response for POST/PUT/DELETE actions
use Illuminate\Http\Request;                 // HTTP request wrapper
use Illuminate\Support\Facades\Auth;         // Auth facade to access the current user
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

/**
 * Controller responsible for CRUD of "system variables" (options) that fine-tune Pete’s behavior.
 *
 * Notes about design decisions:
 * - PSR-12: file has strict_types, imports grouped and sorted, one class per file, brace style, etc.
 * - Type safety: explicit parameter/return types where feasible.
 * - Validation: basic server-side validation added to store/update.
 * - Route Model Binding: used for show/edit/update/destroy (keeps code concise and safer).
 * - Views: passes $viewsw to highlight the proper sidebar entry.
 * - Auth: guarded via middleware in constructor.
 */
class OptionController extends Controller
{
    /**
     * Domain service (kept for future use: cache invalidation, side effects, etc.).
     * Currently not used inside methods but injected for consistency with the rest of the app.
     */
    private PeteService $pete;

    /**
     * Register middleware and inject domain service.
     *
     * @param  PeteService  $pete  Domain service used across Pete features.
     */
    public function __construct(PeteService $pete)
    {
        // Require the user to be authenticated for every action in this controller.
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

        // Save dependency for potential future use (e.g., clearing options cache after writes).
        $this->pete = $pete;

    }

    /**
     * Display a listing of system options (newest first).
     *
     * @return View
     */
    public function index(): View
    {
        // Fetch all options ordered by id desc (mirrors previous behavior).
        $options = Option::query()
            ->orderByDesc('id')
            ->get();

        // Current authenticated user, required by the layout for sidebar/admin visibility.
        $currentUser = Auth::user();

        // Sidebar state flag to highlight the "system variables" entry.
        $viewsw = 'options';

        // Render the index view with compact, explicit variables.
        return view('options.index', compact('options', 'currentUser', 'viewsw'));
    }

    /**
     * Show the form for creating a new option.
     *
     * @return View
     */
    public function create(): View
    {
        // Empty model instance for the form.
        $option = new Option();

        // Auth user and sidebar view switch as used by the layout.
        $currentUser = Auth::user();
        $viewsw = 'options';

        // Render the create form.
        return view('options.create', compact('option', 'currentUser', 'viewsw'));
    }

    /**
     * Persist a newly created option to storage.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // 1) Validate incoming data (keeps constraints close to the write operation).
        //    You can promote this to a FormRequest if you prefer.
        $data = $request->validate([
            'option_name'  => ['required', 'string', 'max:255'],
            'option_value' => ['nullable', 'string'],
            'order'        => ['nullable', 'integer'],
            'category'     => ['nullable', 'string', 'max:255'],
            'title'        => ['nullable', 'string', 'max:255'],
            'version'      => ['nullable', 'string', 'max:255'],
        ]);

        // 2) Create the record. "visible" defaults to true as per previous behavior.
        $option = new Option();
        $option->option_name  = $data['option_name'];
        $option->option_value = $data['option_value'] ?? null;
        $option->category     = $data['category']     ?? null;
        $option->title        = $data['title']        ?? null;
        $option->version      = $data['version']      ?? null;
        $option->visible      = true;
        $option->save();

        // 3) (Optional) Invalidate cached options map to reflect the new value immediately.
        // $this->pete->forgetOptionsCache();

        // 4) Redirect back to index with a success flash message.
        return redirect()
            ->route('options.index')
            ->with('message', 'Item created successfully.');
    }

    /**
     * Display the specified option (kept for completeness; not linked in UI).
     *
     * @param  Option  $option
     * @return View
     */
    public function show(Option $option): View
    {
        // Sidebar/view state and auth user are needed by the base layout.
        $currentUser = Auth::user();
        $viewsw = 'options';

        return view('options.show', compact('option', 'currentUser', 'viewsw'));
    }

    /**
     * Show the form for editing the specified option.
     *
     * @param  Option  $option
     * @return View
     */
    public function edit(Option $option): View
    {
        // Auth user and sidebar view switch as used by the layout.
        $currentUser = Auth::user();
        $viewsw = 'options';

        // Render the edit form with the bound model instance.
        return view('options.edit', compact('option', 'currentUser', 'viewsw'));
    }

    /**
     * Update the specified option in storage.
     *
     * @param  Request  $request
     * @param  Option   $option
     * @return RedirectResponse
     */
    public function update(Request $request, Option $option): RedirectResponse
    {
        // 1) Validate incoming data (same rules as store).
        $data = $request->validate([
            'option_name'  => ['required', 'string', 'max:255'],
            'option_value' => ['nullable', 'string'],
            'order'        => ['nullable', 'integer'],
            'category'     => ['nullable', 'string', 'max:255'],
            'title'        => ['nullable', 'string', 'max:255'],
            'version'      => ['nullable', 'string', 'max:255'],
        ]);

        // 2) Mass-assign fields safely.
        $option->option_name  = $data['option_name'];
        $option->option_value = $data['option_value'] ?? null;
        $option->category     = $data['category']     ?? null;
        $option->title        = $data['title']        ?? null;
        $option->version      = $data['version']      ?? null;
        $option->save();

        // 3) (Optional) Invalidate cached options map to reflect the update immediately.
        // $this->pete->forgetOptionsCache();

        // 4) Redirect back to index with a success flash message.
        return redirect()
            ->route('options.index')
            ->with('message', 'Item updated successfully.');
    }

    /**
     * Remove the specified option from storage.
     *
     * @param  Option  $option
     * @return RedirectResponse
     */
    public function destroy(Option $option): RedirectResponse
    {
        // 1) Delete the record.
        $option->delete();

        // 2) (Optional) Invalidate cached options map to reflect the deletion immediately.
        // $this->pete->forgetOptionsCache();

        // 3) Redirect back to index with a success flash message.
        return redirect()
            ->route('options.index')
            ->with('message', 'Item deleted successfully.');
    }
}
