<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $query = Plan::query();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                  ->orWhere('radius_group_name', 'ilike', "%{$request->search}%");
            });
        }

        $plans = $query->orderBy('type')->orderBy('price')->paginate(15)->withQueryString();

        $stats = [
            'total'   => Plan::count(),
            'active'  => Plan::where('is_active', true)->count(),
            'voucher' => Plan::where('type', 'voucher')->count(),
            'member'  => Plan::where('type', 'member')->count(),
        ];

        return view('plans.index', compact('plans', 'stats'));
    }

    public function create()
    {
        return view('plans.create');
    }

    public function store(Request $request)
    {
        $this->normalizeDurationInput($request);
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:100'],
            'type'                => ['required', Rule::in(['voucher', 'member'])],
            'mikrotik_rate_limit' => ['required', 'string', 'max:255', 'regex:/^[^\x00-\x1F\x7F]+$/'],
            'price'               => ['required', 'integer', 'min:0'],
            'duration_value'      => ['required', 'integer', 'min:1'],
            'duration_unit'       => ['required', Rule::in(['minutes', 'hours', 'days', 'months'])],
            'data_quota_mb'       => ['nullable', 'integer', 'min:1'],
            'radius_group_name'   => ['required', 'string', 'max:100', 'unique:plans,radius_group_name'],
            'description'         => ['nullable', 'string', 'max:500'],
            'is_active'           => ['boolean'],
        ], $this->messages());

        $data['is_active'] = $request->boolean('is_active', true);
        $data['duration_days'] = $this->legacyDurationDays($data['duration_value'], $data['duration_unit']);

        Plan::create($data);

        return redirect()->route('plans.index')
            ->with('success', "Paket '{$data['name']}' berhasil ditambahkan.");
    }

    public function edit(Plan $plan)
    {
        return view('plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $this->normalizeDurationInput($request);
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:100'],
            'type'                => ['required', Rule::in(['voucher', 'member'])],
            'mikrotik_rate_limit' => ['required', 'string', 'max:255', 'regex:/^[^\x00-\x1F\x7F]+$/'],
            'price'               => ['required', 'integer', 'min:0'],
            'duration_value'      => ['required', 'integer', 'min:1'],
            'duration_unit'       => ['required', Rule::in(['minutes', 'hours', 'days', 'months'])],
            'data_quota_mb'       => ['nullable', 'integer', 'min:1'],
            'radius_group_name'   => ['required', 'string', 'max:100', Rule::unique('plans', 'radius_group_name')->ignore($plan->id)],
            'description'         => ['nullable', 'string', 'max:500'],
            'is_active'           => ['boolean'],
        ], $this->messages());

        $data['is_active'] = $request->boolean('is_active', true);
        $data['duration_days'] = $this->legacyDurationDays($data['duration_value'], $data['duration_unit']);

        $plan->update($data);

        return redirect()->route('plans.index')
            ->with('success', "Paket '{$plan->name}' berhasil diperbarui.");
    }

    public function destroy(Plan $plan)
    {
        $name = $plan->name;
        $plan->delete();

        return redirect()->route('plans.index')
            ->with('success', "Paket '{$name}' berhasil dihapus.");
    }

    public function toggleActive(Plan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        $status = $plan->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Paket '{$plan->name}' berhasil {$status}.");
    }

    private function normalizeDurationInput(Request $request): void
    {
        if ($request->input('type') === 'member') {
            $request->merge(['duration_value' => 1, 'duration_unit' => 'months']);
        }
    }

    private function legacyDurationDays(int $value, string $unit): int
    {
        return match ($unit) {
            'months' => $value * 30,
            'days' => $value,
            'hours' => max(1, (int) ceil($value / 24)),
            'minutes' => max(1, (int) ceil($value / 1440)),
            default => 30,
        };
    }

    private function messages(): array
    {
        return [
            'name.required'                => 'Nama paket wajib diisi.',
            'type.required'                => 'Tipe paket wajib dipilih.',
            'price.required'               => 'Harga wajib diisi.',
            'mikrotik_rate_limit.required' => 'MikroTik Rate Limit wajib diisi.',
            'duration_value.required'      => 'Durasi wajib diisi.',
            'radius_group_name.required'   => 'Nama group RADIUS wajib diisi.',
            'radius_group_name.unique'     => 'Nama group RADIUS sudah digunakan paket lain.',
        ];
    }
}
