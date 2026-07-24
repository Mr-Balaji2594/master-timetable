<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClassController extends Controller
{
    private function canModify(): bool
    {
        $user = Auth::user();
        return $user->isAdmin() || $user->isPrincipal() || $user->isVicePrincipal();
    }

    public function index()
    {
        $user = Auth::user();

        $classes = SchoolClass::with('department')
            ->when(!$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal(), fn($q) => $q->where('department_id', $user->department_id))
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'department_id' => $c->department_id,
                'batch_year' => $c->batch_year,
                'year' => $c->year,
                'department' => $c->department ? ['id' => $c->department->id, 'name' => $c->department->name] : null,
            ]);

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Classes/Index', [
            'classes' => $classes,
            'departments' => $departments,
        ]);
    }

    public function store()
    {
        if (!$this->canModify()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $data = request()->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|integer|exists:departments,id',
            'batch_year' => 'nullable',
            'year' => 'nullable',
        ]);

        SchoolClass::create($data);
        audit_log('class_create', "Created class: {$data['name']}");

        return redirect()->back()->with('success', 'Class created successfully');
    }

    public function update(SchoolClass $class)
    {
        if (!$this->canModify()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $data = request()->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|integer|exists:departments,id',
            'batch_year' => 'nullable',
            'year' => 'nullable',
        ]);

        $class->update($data);
        audit_log('class_update', "Updated class: {$data['name']}");

        return redirect()->back()->with('success', 'Class updated successfully');
    }

    public function destroy(SchoolClass $class)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $class->delete();
        audit_log('class_delete', "Deleted class: {$class->name}");

        return redirect()->back()->with('success', 'Class deleted successfully');
    }
}
