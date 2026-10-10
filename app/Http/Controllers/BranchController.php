<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $branches = Branch::withCount('users')
            ->withSum('stocks as stock_units', 'quantity')
            ->orderBy('name')
            ->paginate(20);

        return view('branches.index', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        Branch::create($this->validated($request));

        return back()->with('success', 'Branch added.');
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $data = $this->validated($request, $branch);
        $data['is_active'] = $request->boolean('is_active');

        if (! $data['is_active'] && Branch::active()->whereKeyNot($branch->id)->doesntExist()) {
            return back()->with('error', 'At least one branch must stay active.');
        }

        $branch->update($data);

        return back()->with('success', 'Branch updated.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->stocks()->where('quantity', '!=', 0)->exists()) {
            return back()->with('error', 'Cannot delete a branch that still holds stock. Transfer it out first or mark the branch inactive.');
        }

        if ($branch->sales()->exists()) {
            return back()->with('error', 'Cannot delete a branch with sales history. Mark it inactive instead.');
        }

        if (Branch::whereKeyNot($branch->id)->doesntExist()) {
            return back()->with('error', 'Cannot delete the only branch.');
        }

        $branch->delete();

        return back()->with('success', 'Branch deleted.');
    }

    /**
     * Change the branch the signed-in user is working in (sidebar picker).
     */
    public function switch(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canSwitchBranch(), 403, 'You are assigned to a single branch.');

        $data = $request->validate([
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)],
        ]);

        $request->session()->put(Branch::SESSION_KEY, (int) $data['branch_id']);

        return back()->with('success', 'Now working in '.Branch::find($data['branch_id'])->name.'.');
    }

    private function validated(Request $request, ?Branch $branch = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($branch?->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in([Branch::TYPE_STORE, Branch::TYPE_WAREHOUSE])],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
