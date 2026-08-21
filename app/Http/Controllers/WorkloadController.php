<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Subject;
use App\Models\Workload;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkloadController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $workloads = Workload::with('department')
            ->whereHas('department', fn($q) => $q->where('code', '!=', '001'))
            ->when($user->isStaff() || $user->isHOD(), fn($q) => $q->where('department_id', $user->department_id))
            ->orderBy('year')->orderBy('subject_name')->get();

        $years = Workload::select('year')->distinct()->orderBy('year')->pluck('year');

        $departments = Department::where('code', '!=', '001')->orderBy('name')->get(['id', 'name']);

        $subjects = Subject::whereNotNull('name')
            ->when(!$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal(), fn($q) => $q->where('department_id', $user->department_id))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'department_id']);

        return Inertia::render('Workload/Index', [
            'workloads' => $workloads,
            'years' => $years,
            'departments' => $departments,
            'subjects' => $subjects,
        ]);
    }

    public function store()
    {
        $user = Auth::user();

        $data = request()->validate([
            'year' => 'required|string',
            'sem_mode' => 'required|string|in:odd,even',
            'department_id' => 'required|integer|exists:departments,id',
            'subject_name' => 'required|string',
            'total_hours' => 'required|numeric|min:0',
        ]);

        if ($user->isStaff() || $user->isHOD()) {
            if ($data['department_id'] != $user->department_id) {
                return redirect()->back()->with('error', 'You can only add workload for your own department');
            }
        }

        Workload::create($data);
        audit_log('workload_create', "Created workload: {$data['subject_name']} - {$data['year']} - {$data['total_hours']}h");

        return redirect()->back()->with('success', 'Workload entry created successfully');
    }

    public function update(Workload $workload)
    {
        $user = Auth::user();

        $data = request()->validate([
            'year' => 'required|string',
            'sem_mode' => 'required|string|in:odd,even',
            'department_id' => 'required|integer|exists:departments,id',
            'subject_name' => 'required|string',
            'total_hours' => 'required|numeric|min:0',
        ]);

        if ($user->isStaff() || $user->isHOD()) {
            if ($data['department_id'] != $user->department_id) {
                return redirect()->back()->with('error', 'You can only edit workload for your own department');
            }
        }

        $workload->update($data);
        audit_log('workload_update', "Updated workload #{$workload->id}: {$data['subject_name']} - {$data['year']} - {$data['total_hours']}h");

        return redirect()->back()->with('success', 'Workload entry updated successfully');
    }
}
