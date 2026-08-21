<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $query = AuditLog::when(request('emp_id'), fn($q, $v) => $q->where('emp_id', 'like', "%{$v}%"))
            ->when(request('action'), fn($q, $v) => $q->where('action', 'like', "%{$v}%"))
            ->when(request('from_date'), fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when(request('to_date'), fn($q, $v) => $q->whereDate('created_at', '<=', $v));

        $logs = (clone $query)->orderByDesc('id')->get();

        $empNames = Employee::whereIn('emp_id', $logs->pluck('emp_id'))->pluck('name', 'emp_id');

        $logs = $logs->map(fn($log) => [
            'id' => $log->id,
            'user_id' => $log->user_id,
            'emp_id' => $log->emp_id,
            'action' => $log->action,
            'details' => $log->details,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'created_at' => Carbon::parse($log->created_at)->format('d/m/Y H:i:s'),
            'user' => $log->emp_id ? [
                'id' => $log->user_id,
                'emp_id' => $log->emp_id,
                'name' => $empNames[$log->emp_id] ?? null,
            ] : null,
        ]);

        return Inertia::render('AuditLog/Index', [
            'logs' => $logs,
            'filters' => request()->only(['emp_id', 'action', 'from_date', 'to_date']),
        ]);
    }
}
