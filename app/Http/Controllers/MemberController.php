<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Member;
use App\Models\Plan;
use App\Services\MemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function __construct(protected MemberService $service) {}

    public function index(Request $request)
    {
        $query = Member::with('plan')->withoutTrashed();

        if ($search = $request->search) {
            $query->where('username', 'ilike', "%{$search}%");
        }
        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($planId = $request->plan_id) {
            $query->where('plan_id', $planId);
        }

        $members = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
        $plans   = Plan::active()->orderBy('name')->get();

        return view('members.index', compact('members', 'plans'));
    }

    public function create()
    {
        $plans = Plan::active()->orderBy('name')->get();
        return view('members.create', compact('plans'));
    }

    public function store(StoreMemberRequest $request)
    {
        $member = $this->service->create($request->validated());

        return redirect()
            ->route('members.show', $member)
            ->with('success', "Member {$member->username} berhasil dibuat.");
    }

    public function show(Member $member)
    {
        $member->load('plan');
        $sessions = DB::table('radacct')
            ->where('username', $member->username)
            ->orderByDesc('acctstarttime')
            ->limit(20)
            ->get();
        return view('members.show', compact('member', 'sessions'));
    }

    public function edit(Member $member)
    {
        $plans = Plan::active()->orderBy('name')->get();
        return view('members.edit', compact('member', 'plans'));
    }

    public function update(UpdateMemberRequest $request, Member $member)
    {
        $this->service->update($member, $request->validated());

        return redirect()
            ->route('members.show', $member)
            ->with('success', "Member {$member->username} berhasil diperbarui.");
    }

    public function destroy(Member $member)
    {
        $username = $member->username;
        $this->service->delete($member);

        return redirect()
            ->route('members.index')
            ->with('success', "Member {$username} berhasil dihapus.");
    }

    public function toggleStatus(Member $member)
    {
        $updated = $this->service->toggleStatus($member);
        $label   = $updated->status_label;

        if (request()->expectsJson()) {
            return response()->json(['status' => $updated->status, 'label' => $label]);
        }

        return back()->with('success', "Status {$member->username} diubah ke {$label}.");
    }
}
