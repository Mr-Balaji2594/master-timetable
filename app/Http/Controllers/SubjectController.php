<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SubjectController extends Controller
{
    private function canModify(): bool
    {
        $user = Auth::user();
        return $user->isAdmin() || $user->isPrincipal() || $user->isVicePrincipal() || $user->isHOD();
    }

    public function index()
    {
        $user = Auth::user();

        $subjects = Subject::with('department')
            ->when(!$user->isAdmin() && !$user->isPrincipal() && !$user->isVicePrincipal(), fn($q) => $q->where('department_id', $user->department_id))
            ->orderBy('name')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'department_id' => $s->department_id,
                'credits' => $s->credits,
                'lecture_hours_per_week' => $s->lecture_hours_per_week,
                'year' => $s->year,
                'sem' => $s->sem,
                'sem_mode' => $s->sem_mode,
                'is_common' => $s->is_common,
                'department' => $s->department ? ['id' => $s->department->id, 'name' => $s->department->name] : null,
            ]);

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Subjects/Index', [
            'subjects' => $subjects,
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
            'code' => 'required|string|max:50|unique:subjects,code',
            'department_id' => 'required|integer|exists:departments,id',
            'credits' => 'nullable|integer|min:0',
            'lecture_hours_per_week' => 'nullable|integer|min:0',
            'year' => 'nullable|string|max:10',
            'sem' => 'nullable|string|max:10',
            'sem_mode' => 'nullable|string|max:50',
            'is_common' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        if ($user->isHOD()) {
            $data['department_id'] = $user->department_id;
        }

        Subject::create($data);
        audit_log('subject_create', "Created subject: {$data['name']} ({$data['code']})");

        return redirect()->back()->with('success', 'Subject created successfully');
    }

    public function update(Subject $subject)
    {
        if (!$this->canModify()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $user = Auth::user();
        if ($user->isHOD() && $subject->department_id !== $user->department_id) {
            return redirect()->back()->with('error', 'You can only update subjects in your own department');
        }

        $data = request()->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code,' . $subject->id,
            'department_id' => 'required|integer|exists:departments,id',
            'credits' => 'nullable|integer|min:0',
            'lecture_hours_per_week' => 'nullable|integer|min:0',
            'year' => 'nullable|string|max:10',
            'sem' => 'nullable|string|max:10',
            'sem_mode' => 'nullable|string|max:50',
            'is_common' => 'nullable|boolean',
        ]);

        if ($user->isHOD()) {
            $data['department_id'] = $user->department_id;
        }

        $subject->update($data);
        audit_log('subject_update', "Updated subject: {$data['name']} ({$data['code']})");

        return redirect()->back()->with('success', 'Subject updated successfully');
    }

    public function destroy(Subject $subject)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $subject->delete();
        audit_log('subject_delete', "Deleted subject: {$subject->name} ({$subject->code})");

        return redirect()->back()->with('success', 'Subject deleted successfully');
    }
}
