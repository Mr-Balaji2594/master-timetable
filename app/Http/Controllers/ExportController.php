<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    public function export()
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $type = request('type', 'report');

        audit_log('export', "Exported {$type}");

        return redirect()->back()->with('success', 'Export feature coming soon');
    }
}
