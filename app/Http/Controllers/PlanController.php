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
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:100'],
            'type'                => ['required', Rule::in(['voucher', 'member'])],
            'price'               => ['required', 'integer', 'min:0'],
            'download_speed_kbps' => ['required', 'integer', 'min:1'],
            'upload_speed_kbps'   => ['required', 'integer', 'min:1'],
            'duration_value'      => ['required', 'integer', 'min:1'],
            'duration_unit'       => ['required', Rule::in(['minutes', 'hours', 'days'])],
            'data_quota_mb'       => ['nullable', 'integer', 'min:1'],
            'qos_limit_at_down_kbps' => ['nullable', 'integer', 'min:1'],
            'qos_limit_at_up_kbps' => ['nullable', 'integer', 'min:1'],
            'qos_burst_limit_down_kbps' => ['nullable', 'integer', 'min:1'],
            'qos_burst_limit_up_kbps' => ['nullable', 'integer', 'min:1'],
            'qos_burst_threshold_down_kbps' => ['nullable', 'integer', 'min:1'],
            'qos_burst_threshold_up_kbps' => ['nullable', 'integer', 'min:1'],
            'qos_burst_time_down_sec' => ['nullable', 'integer', 'min:1'],
            'qos_burst_time_up_sec' => ['nullable', 'integer', 'min:1'],
            'qos_priority' => ['nullable', 'integer', 'min:1', 'max:8'],
            'qos_queue_type' => ['nullable', 'string', 'max:100'],
            'radius_group_name'   => ['required', 'string', 'max:100', 'unique:plans,radius_group_name'],
            'description'         => ['nullable', 'string', 'max:500'],
            'is_active'           => ['boolean'],
        ], $this->messages());

        $this->validateQosConsistency($data);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['duration_days'] = $data['duration_unit'] === 'days' ? $data['duration_value'] : max(1, (int) ceil(($data['duration_unit'] === 'hours' ? $data['duration_value'] / 24 : $data['duration_value'] / 1440)));

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
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:100'],
            'type'                => ['required', Rule::in(['voucher', 'member'])],
            'price'               => ['required', 'integer', 'min:0'],
            'download_speed_kbps' => ['required', 'integer', 'min:1'],
            'upload_speed_kbps'   => ['required', 'integer', 'min:1'],
            'duration_value'      => ['required', 'integer', 'min:1'],
            'duration_unit'       => ['required', Rule::in(['minutes', 'hours', 'days'])],
            'data_quota_mb'       => ['nullable', 'integer', 'min:1'],
            'qos_limit_at_down_kbps' => ['nullable', 'integer', 'min:1'],
            'qos_limit_at_up_kbps' => ['nullable', 'integer', 'min:1'],
            'qos_burst_limit_down_kbps' => ['nullable', 'integer', 'min:1'],
            'qos_burst_limit_up_kbps' => ['nullable', 'integer', 'min:1'],
            'qos_burst_threshold_down_kbps' => ['nullable', 'integer', 'min:1'],
            'qos_burst_threshold_up_kbps' => ['nullable', 'integer', 'min:1'],
            'qos_burst_time_down_sec' => ['nullable', 'integer', 'min:1'],
            'qos_burst_time_up_sec' => ['nullable', 'integer', 'min:1'],
            'qos_priority' => ['nullable', 'integer', 'min:1', 'max:8'],
            'qos_queue_type' => ['nullable', 'string', 'max:100'],
            'radius_group_name'   => ['required', 'string', 'max:100', Rule::unique('plans', 'radius_group_name')->ignore($plan->id)],
            'description'         => ['nullable', 'string', 'max:500'],
            'is_active'           => ['boolean'],
        ], $this->messages());

        $this->validateQosConsistency($data);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['duration_days'] = $data['duration_unit'] === 'days' ? $data['duration_value'] : max(1, (int) ceil(($data['duration_unit'] === 'hours' ? $data['duration_value'] / 24 : $data['duration_value'] / 1440)));

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

    private function validateQosConsistency(array $data): void
    {
        $pairs = [
            ['qos_limit_at_down_kbps', 'download_speed_kbps', 'Limit At download tidak boleh melebihi Max Limit download.'],
            ['qos_limit_at_up_kbps', 'upload_speed_kbps', 'Limit At upload tidak boleh melebihi Max Limit upload.'],
            ['qos_burst_threshold_down_kbps', 'qos_burst_limit_down_kbps', 'Burst Threshold download tidak boleh melebihi Burst Limit download.'],
            ['qos_burst_threshold_up_kbps', 'qos_burst_limit_up_kbps', 'Burst Threshold upload tidak boleh melebihi Burst Limit upload.'],
            ['qos_burst_limit_down_kbps', 'download_speed_kbps', 'Burst Limit download tidak boleh lebih kecil dari Max Limit download.'],
            ['qos_burst_limit_up_kbps', 'upload_speed_kbps', 'Burst Limit upload tidak boleh lebih kecil dari Max Limit upload.'],
            ['qos_burst_threshold_down_kbps', 'download_speed_kbps', 'Burst Threshold download tidak boleh melebihi Max Limit download.'],
            ['qos_burst_threshold_up_kbps', 'upload_speed_kbps', 'Burst Threshold upload tidak boleh melebihi Max Limit upload.'],
        ];

        foreach ($pairs as [$left, $right, $message]) {
            if ($data[$left] !== null && $data[$right] !== null) {
                $mustBeLessOrEqual = str_contains($left, 'limit_at') || str_contains($left, 'threshold');
                $invalid = $mustBeLessOrEqual
                    ? (int) $data[$left] > (int) $data[$right]
                    : (int) $data[$left] < (int) $data[$right];

                if ($invalid) {
                    throw \Illuminate\Validation\ValidationException::withMessages([$left => $message]);
                }
            }
        }

        if ($data['qos_burst_threshold_down_kbps'] !== null && $data['qos_limit_at_down_kbps'] !== null
            && $data['qos_burst_threshold_down_kbps'] < $data['qos_limit_at_down_kbps']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qos_burst_threshold_down_kbps' => 'Burst Threshold download sebaiknya >= Limit At download untuk perilaku burst yang valid.',
            ]);
        }

        if ($data['qos_burst_threshold_up_kbps'] !== null && $data['qos_limit_at_up_kbps'] !== null
            && $data['qos_burst_threshold_up_kbps'] < $data['qos_limit_at_up_kbps']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qos_burst_threshold_up_kbps' => 'Burst Threshold upload sebaiknya >= Limit At upload untuk perilaku burst yang valid.',
            ]);
        }
    }

    private function messages(): array
    {
        return [
            'name.required'                => 'Nama paket wajib diisi.',
            'type.required'                => 'Tipe paket wajib dipilih.',
            'price.required'               => 'Harga wajib diisi.',
            'download_speed_kbps.required' => 'Kecepatan download wajib diisi.',
            'upload_speed_kbps.required'   => 'Kecepatan upload wajib diisi.',
            'duration_value.required'      => 'Durasi wajib diisi.',
            'radius_group_name.required'   => 'Nama group RADIUS wajib diisi.',
            'radius_group_name.unique'     => 'Nama group RADIUS sudah digunakan paket lain.',
        ];
    }
}
