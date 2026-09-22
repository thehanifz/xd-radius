<?php

namespace App\Http\Controllers;

use App\Models\Radacct;
use App\Models\Router;
use App\Models\Voucher;
use Illuminate\Http\Request;

class OnlineSessionController extends Controller
{
    public function index(Request $request)
    {
        return view('online.index', $this->getIndexData($request));
    }

    public function live(Request $request)
    {
        $data = $this->getIndexData($request);

        return response()->json([
            'stats' => [
                'active' => $data['totalActive'],
                'stale' => $data['totalStale'],
                'upload' => Radacct::formatBytes($data['totalUpload']),
                'download' => Radacct::formatBytes($data['totalDownload']),
            ],
            'rows' => view('online.partials.rows', $data)->render(),
            'page' => $data['sessions']->currentPage(),
            'last_page' => $data['sessions']->lastPage(),
        ]);
    }

    private function getIndexData(Request $request): array
    {
        $query = Radacct::whereNull('acctstoptime')
            ->orderByDesc('acctstarttime');

        if ($request->filled('type')) {
            if ($request->type === 'voucher') {
                $query->whereExists(fn($q) => $q->from('vouchers')->whereColumn('vouchers.username', 'radacct.username'));
            } elseif ($request->type === 'member') {
                $query->whereExists(fn($q) => $q->from('members')->whereColumn('members.username', 'radacct.username'));
            }
        }

        if ($request->filled('nas')) {
            $query->where('nasipaddress', $request->nas);
        }

        if ($request->filter === 'stale') {
            $query->where('is_stale', true);
        } elseif ($request->filter === 'active') {
            $query->where(fn($q) => $q->where('is_stale', false)->orWhereNull('is_stale'));
        }

        $sessions = $query->paginate(50)->withQueryString();
        $voucherUsers = Voucher::whereIn('username', $sessions->pluck('username')->unique())
            ->pluck('username')->flip();
        $routers = Router::active()->orderBy('name')->get();
        $nasIps = Radacct::whereNull('acctstoptime')
            ->select('nasipaddress')->distinct()->orderBy('nasipaddress')->pluck('nasipaddress');

        $activeQuery = Radacct::whereNull('acctstoptime');
        $totalActive = (clone $activeQuery)->where(fn($q) => $q->where('is_stale', false)->orWhereNull('is_stale'))->count();
        $totalStale = (clone $activeQuery)->where('is_stale', true)->count();
        $totalUpload = (clone $activeQuery)->sum('acctoutputoctets');
        $totalDownload = (clone $activeQuery)->sum('acctinputoctets');

        return compact(
            'sessions', 'routers', 'nasIps', 'totalActive', 'totalStale',
            'totalUpload', 'totalDownload', 'voucherUsers'
        );
    }

    public function show(Radacct $session)
    {
        $voucher = Voucher::with('plan')->where('username', $session->username)->first();

        $history = Radacct::where('username', $session->username)
            ->orderByDesc('acctstarttime')
            ->limit(30)
            ->get();

        $summary = [
            'sessions' => Radacct::where('username', $session->username)->count(),
            'duration' => Radacct::where('username', $session->username)->sum('acctsessiontime'),
            'upload' => Radacct::where('username', $session->username)->sum('acctoutputoctets'),
            'download' => Radacct::where('username', $session->username)->sum('acctinputoctets'),
        ];

        return view('online.show', compact('session', 'voucher', 'history', 'summary'));
    }
}
