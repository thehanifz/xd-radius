<?php

namespace App\Http\Controllers;

use App\Models\Radacct;
use App\Models\Router;
use Illuminate\Http\Request;

class OnlineSessionController extends Controller
{
    public function index(Request $request)
    {
        $query = Radacct::whereNull('acctstoptime')
            ->orderByDesc('acctstarttime');

        // Filter tipe user
        if ($request->filled('type')) {
            if ($request->type === 'voucher') {
                $query->whereExists(fn($q) => $q->from('vouchers')->whereColumn('vouchers.username', 'radacct.username'));
            } elseif ($request->type === 'member') {
                $query->whereExists(fn($q) => $q->from('members')->whereColumn('members.username', 'radacct.username'));
            }
        }

        // Filter router/NAS
        if ($request->filled('nas')) {
            $query->where('nasipaddress', $request->nas);
        }

        // Filter stale
        if ($request->filter === 'stale') {
            $query->where('is_stale', true);
        } elseif ($request->filter === 'active') {
            $query->where(fn($q) => $q->where('is_stale', false)->orWhereNull('is_stale'));
        }

        $sessions  = $query->paginate(50)->withQueryString();
        $routers   = Router::active()->orderBy('name')->get();
        $nasIps    = Radacct::whereNull('acctstoptime')
                        ->select('nasipaddress')
                        ->distinct()
                        ->pluck('nasipaddress');

        $totalActive = Radacct::whereNull('acctstoptime')
            ->where(fn($q) => $q->where('is_stale', false)->orWhereNull('is_stale'))
            ->count();
        $totalStale  = Radacct::whereNull('acctstoptime')->where('is_stale', true)->count();

        return view('online.index', compact('sessions', 'routers', 'nasIps', 'totalActive', 'totalStale'));
    }
}
