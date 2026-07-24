<?php

namespace App\Http\Controllers;

use App\Models\CommonPaperAllocation;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CommonPaperController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isFullAccess = $user->isAdmin() || $user->isPrincipal() || $user->isVicePrincipal();

        $allocations = CommonPaperAllocation::with(['subject.department', 'class'])
            ->when(!$isFullAccess, fn($q) => $q->whereHas('subject', fn($q) => $q->where('department_id', $user->department_id)))
            ->get();

        $allCommonSubjects = Subject::with('department')
            ->where('is_common', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'department_id']);

        $allocatedGrouped = $allocations->groupBy('subject_id')
            ->map(fn($group) => [
                'id' => $group->first()->id,
                'slot_ids' => $group->pluck('id')->toArray(),
                'subject_id' => $group->first()->subject_id,
                'class_ids' => $group->pluck('class_id')->toArray(),
                'classes' => $group->map(fn($a) => $a->class ? ['name' => $a->class->name] : null)->filter()->values(),
            ])
            ->values();

        $allocatedSubjectIds = $allocatedGrouped->pluck('subject_id')->unique()->values()->toArray();

        $commonSubjects = $allCommonSubjects->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'code' => $s->code,
            'department' => $s->department ? ['name' => $s->department->name] : null,
            'is_allocated' => in_array($s->id, $allocatedSubjectIds),
            'allocations' => $allocatedGrouped->filter(fn($a) => $a['subject_id'] === $s->id)->values(),
        ]);

        $classes = SchoolClass::with('department')
            ->when(!$isFullAccess, fn($q) => $q->where('department_id', $user->department_id))
            ->orderBy('name')
            ->get(['id', 'name', 'department_id', 'year']);

        $commonSubjectOptions = Subject::where('is_common', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('CommonPapers/Index', [
            'commonSubjects' => $commonSubjects,
            'commonSubjectOptions' => $commonSubjectOptions,
            'classes' => $classes,
        ]);
    }

    public function allocate()
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $data = request()->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'class_ids' => 'required|array',
            'class_ids.*' => 'integer|exists:classes,id',
        ]);

        foreach ($data['class_ids'] as $classId) {
            $existing = CommonPaperAllocation::where('class_id', $classId)
                ->where('subject_id', $data['subject_id'])
                ->exists();

            if (!$existing) {
                CommonPaperAllocation::create([
                    'class_id' => $classId,
                    'subject_id' => $data['subject_id'],
                ]);
            }
        }

        audit_log('common_paper_allocate', "Allocated common paper #{$data['subject_id']} to " . count($data['class_ids']) . " classes");

        return redirect()->back()->with('success', 'Common paper allocated successfully');
    }

    public function update(CommonPaperAllocation $commonPaperAllocation)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        $data = request()->validate([
            'class_ids' => 'required|array',
            'class_ids.*' => 'integer|exists:classes,id',
            'subject_id' => 'required|integer|exists:subjects,id',
        ]);

        $existing = CommonPaperAllocation::where('subject_id', $data['subject_id'])->get();

        $existingClassIds = $existing->pluck('class_id')->toArray();
        $newClassIds = $data['class_ids'];

        CommonPaperAllocation::where('subject_id', $data['subject_id'])
            ->whereNotIn('class_id', $newClassIds)
            ->delete();

        foreach ($newClassIds as $classId) {
            if (!in_array($classId, $existingClassIds)) {
                CommonPaperAllocation::create([
                    'class_id' => $classId,
                    'subject_id' => $data['subject_id'],
                ]);
            }
        }

        audit_log('common_paper_update', "Updated common paper #{$data['subject_id']} allocation");

        return redirect()->back()->with('success', 'Common paper allocation updated successfully');
    }

    public function destroy(CommonPaperAllocation $commonPaperAllocation)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPrincipal()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        CommonPaperAllocation::where('subject_id', $commonPaperAllocation->subject_id)->delete();

        audit_log('common_paper_delete', "Deleted common paper allocation");

        return redirect()->back()->with('success', 'Common paper allocation deleted successfully');
    }
}
