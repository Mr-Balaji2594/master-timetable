import { Head, usePage, router } from '@inertiajs/react'
import { Card, Table, Button, Modal, Form, Row, Col, Badge } from 'react-bootstrap'
import { useState, useMemo, useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import Select2 from '../../Components/Select2'
import Select2Field from '../../Components/Select2Field'
import FormErrors from '../../Components/FormErrors'
import FlashAlert from '../../Components/FlashAlert'
import { showConfirm, showConfirmCustom } from '../../Helpers/sweetAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'
import { pdf } from '@react-pdf/renderer'
import TimetablePDF from '../../Components/TimetablePDF'

const dayNames = ['I', 'II', 'III', 'IV', 'V', 'VI']
const PERIODS = [1, 2, 3, 4, 5]

const DEPT_COLORS = ['#4f46e5', '#0ea5e9', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#db2777', '#65a30d', '#9333ea']

const STATUS = {
    pending_hod: { label: 'Pending HOD', bg: 'warning' },
    pending_principal: { label: 'Pending Principal', bg: 'info' },
    approved: { label: 'Approved', bg: 'success' },
    rejected: { label: 'Rejected', bg: 'danger' },
}

function deptColor(code) {
    let hash = 0
    for (let i = 0; i < (code || '').length; i++) hash = (hash * 31 + code.charCodeAt(i)) >>> 0
    return DEPT_COLORS[hash % DEPT_COLORS.length]
}

const schema = z.object({
    employee_id: z.string().min(1, 'Employee is required'),
    class_id: z.string().min(1, 'Class is required'),
    subject_id: z.string().min(1, 'Subject is required'),
    day_of_week: z.string().min(1, 'Day is required'),
    period_no: z.string().min(1, 'Period is required'),
})

export default function Index({ slots, employees, classes, allClasses, subjects }) {
    const subjectOptions = useMemo(() => {
        if (!subjects) return []
        const common = []
        const byDept = {}
        subjects.forEach(s => {
            const opt = { value: s.id, label: `${s.name} (${s.code})` }
            if (s.is_common) {
                common.push(opt)
            } else {
                const deptName = s.department?.name || 'Unknown'
                if (!byDept[deptName]) byDept[deptName] = []
                byDept[deptName].push(opt)
            }
        })
        const groups = []
        if (common.length) groups.push({ label: 'Common Papers', options: common })
        Object.keys(byDept).sort().forEach(dept => {
            groups.push({ label: dept, options: byDept[dept] })
        })
        return groups
    }, [subjects])

    const { auth, flash } = usePage().props
    const user = auth?.user
    const isStaff = user?.role === 'staff'
    const isHod = user?.role === 'hod'
    const isPrincipalAdmin = ['principal', 'admin', 'super_admin'].includes(user?.role)
    const canManage = ['admin', 'super_admin', 'principal', 'hod'].includes(user?.role)
    const canEditSlot = (s) => canManage || (isStaff && s.employee_id === user?.id && s.status !== 'approved')
    const [show, setShow] = useState(false)
    const [edit, setEdit] = useState(null)
    const [editGroup, setEditGroup] = useState(null)
    const [combined, setCombined] = useState([])
    const [addPick, setAddPick] = useState('')
    const [filters, setFilters] = useState({
        employee_id: isStaff ? String(user?.id) : (employees?.length > 0 ? String(employees[0].id) : ''),
        class_id: '',
        day_of_week: ''
    })

    const defaults = { employee_id: isStaff ? String(user?.id ?? '') : '', class_id: '', subject_id: '', day_of_week: '', period_no: '' }
    const { control, handleSubmit, reset, setError, watch, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: defaults
    })

    const mainClassId = watch('class_id')
    const mainSubjectId = watch('subject_id')

    useEffect(() => {
        if (mainClassId) {
            setCombined(prev => prev.filter(c => String(c.class_id) !== mainClassId))
        }
    }, [mainClassId])

    const combinedClassIds = combined.map(c => String(c.class_id))

    const combinedClassOptions = useMemo(() => {
        return (allClasses || [])
            .filter(c => String(c.id) !== mainClassId && !combinedClassIds.includes(String(c.id)))
            .map(c => ({ value: c.id, label: c.label }))
    }, [allClasses, mainClassId, combinedClassIds])

    const getClassLabel = (id) => allClasses?.find(c => String(c.id) === String(id))?.label || `Class #${id}`

    const getSlotGroup = (s) => {
        const groupId = s.combined_group_id || s.id
        return (slots || []).filter(x => x.id === groupId || x.combined_group_id === groupId)
    }

    const isCombinedSlot = (s) => {
        if (s.combined_group_id) return true
        return (slots || []).some(x => x.combined_group_id === s.id)
    }

    const openCreate = (dow, pno) => {
        reset({ ...defaults, day_of_week: String(dow), period_no: String(pno) })
        setCombined([])
        setEdit(null)
        setEditGroup(null)
        setShow(true)
    }

    const openEdit = (s) => {
        const group = getSlotGroup(s)
        const groupId = s.combined_group_id || s.id
        const primary = group.find(x => x.id === groupId) || group[0]
        const others = group.filter(x => x.id !== primary.id)

        reset({
            employee_id: String(primary.employee_id),
            class_id: String(primary.class_id),
            subject_id: String(primary.subject_id),
            day_of_week: String(primary.day_of_week),
            period_no: String(primary.period_no),
        })
        setCombined(others.map(x => ({ class_id: x.class_id, subject_id: x.subject_id })))
        setEdit(s)
        setEditGroup(group)
        setShow(true)
    }

    const addCombinedClass = (classId) => {
        if (!classId) return
        const entry = { class_id: classId, subject_id: mainSubjectId ? Number(mainSubjectId) : '' }
        setCombined(prev => [...prev, entry])
    }

    const removeCombinedClass = (index) => {
        setCombined(prev => prev.filter((_, i) => i !== index))
    }

    const setCombinedSubject = (index, subjectId) => {
        setCombined(prev => prev.map((c, i) => i === index ? { ...c, subject_id: subjectId ? Number(subjectId) : '' } : c))
    }

    const submit = handleSubmit((formData) => {
        const combinedPayload = combined.map(c => ({
            class_id: Number(c.class_id),
            subject_id: Number(c.subject_id),
        }))
        const incomplete = combinedPayload.find(c => !c.class_id || !c.subject_id)
        if (incomplete) {
            setError('combined_classes', { message: 'Every combined class needs a subject selected' })
            return
        }
        const payload = {
            ...formData,
            day_of_week: Number(formData.day_of_week),
            period_no: Number(formData.period_no),
            combined_classes: combinedPayload.length ? combinedPayload : [],
        }
        const done = () => { setShow(false); setEdit(null); setEditGroup(null); setCombined([]); reset(defaults) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        const method = edit ? 'put' : 'post'
        const url = edit ? `/timetable/${edit.id}` : '/timetable'
        router[method](url, payload, { onSuccess: done, onError, preserveState: true })
    })

    const del = async (s) => {
        const label = isCombinedSlot(s) ? 'this combined class group' : 'this timetable slot'
        const result = await showConfirm('Delete Slot?', `${label} will be removed.`)
        if (result.isConfirmed) router.delete(`/timetable/${s.id}`)
    }

    const cellClick = (groups, dow, pno) => {
        const firstSlot = groups[0]?.slots[0]
        if (canManage) {
            if (firstSlot) openEdit(firstSlot)
            else openCreate(dow, pno)
            return
        }
        if (isStaff) {
            if (!firstSlot) { openCreate(dow, pno); return }
            if (canEditSlot(firstSlot)) openEdit(firstSlot)
        }
    }

    const forwardSlot = async (s) => {
        const result = await showConfirm('Forward to Principal?', 'This slot will move to the Principal for final approval.')
        if (result.isConfirmed) router.post(`/timetable/${s.id}/approve-hod`, {}, { preserveState: true })
    }

    const approveSlot = async (s) => {
        const result = await showConfirm('Approve Slot?', 'This slot will be marked as approved.')
        if (result.isConfirmed) router.post(`/timetable/${s.id}/approve-principal`, {}, { preserveState: true })
    }

    const rejectSlot = async (s) => {
        const result = await showConfirmCustom({
            title: 'Reject Slot?',
            text: 'The staff member will be notified that this slot was rejected.',
            confirmText: 'Reject',
        })
        if (result.isConfirmed) router.post(`/timetable/${s.id}/reject`, {}, { preserveState: true })
    }

    const filtered = (slots || []).filter(s =>
        (!filters.employee_id || s.employee_id == filters.employee_id) &&
        (!filters.class_id || s.class_id == filters.class_id) &&
        (!filters.day_of_week || s.day_of_week == filters.day_of_week)
    )

    const cellMap = useMemo(() => {
        const map = {}
        filtered.forEach(s => {
            const key = `${s.day_of_week}-${s.period_no}`
            if (!map[key]) map[key] = []
            map[key].push(s)
        })
        return map
    }, [filtered])

    const getCellGroups = (dow, pno) => {
        const cellSlots = cellMap[`${dow}-${pno}`] || []
        const groups = []
        const used = new Set()
        cellSlots.forEach(s => {
            if (used.has(s.id)) return
            const groupId = s.combined_group_id || s.id
            const isCombined = !!s.combined_group_id || cellSlots.some(x => x.combined_group_id === s.id)
            if (isCombined) {
                const members = cellSlots.filter(x => {
                    const xGroupId = x.combined_group_id || x.id
                    return xGroupId === groupId
                })
                members.forEach(m => used.add(m.id))
                groups.push({ combined: true, slots: members })
            } else {
                used.add(s.id)
                groups.push({ combined: false, slots: [s] })
            }
        })
        return groups
    }

    const handlePrint = async () => {
        const emp = employees?.find(e => e.id == filters.employee_id)
        const cls = classes?.find(c => c.id == filters.class_id)
        const blob = await pdf(
            <TimetablePDF
                slots={filtered}
                employee={emp}
                className={cls?.label}
            />
        ).toBlob()
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
    }

    const renderSlot = (s, showTeacher = true, compact = false) => (
        <div key={s.id} className={`slot-entry ${compact ? 'compact' : ''}`}>
            <span className="subject" style={{ borderColor: deptColor(s.class?.dept_code) }}>
                {s.subject?.name} <span className="code">({s.subject?.code})</span>
            </span>
            <span className="class-tag">
                {s.class?.dept_code ? <em className="dept-dot" style={{ background: deptColor(s.class?.dept_code) }} /> : null}
                {s.class?.dept_code} - {s.class?.name} - {s.class?.year}
            </span>
            {showTeacher && s.employee && <span className="teacher">{s.employee.name}</span>}
            {s.status && s.status !== 'approved' && (
                <Badge pill bg={STATUS[s.status]?.bg || 'secondary'} className="status-badge">{STATUS[s.status]?.label || s.status}</Badge>
            )}
        </div>
    )

    const renderGroup = (group, cellClick) => {
        if (!group.combined) {
            const s = group.slots[0]
            const editable = canEditSlot(s)
            return (
                <div key={s.id} className="slot-group" onClick={cellClick} title={editable ? 'Click to edit' : undefined}>
                    {renderSlot(s)}
                </div>
            )
        }
        const groupId = group.slots[0].combined_group_id || group.slots[0].id
        const primary = group.slots.find(x => x.id === groupId) || group.slots[0]
        const hasMixedSubjects = group.slots.some(x => x.subject_id !== primary.subject_id)
        const editable = canEditSlot(primary)
        return (
            <div key={primary.id} className={`slot-group combined-group ${editable ? 'clickable' : ''}`} onClick={cellClick} title={editable ? 'Click to edit combined class' : undefined}>
                <div className="combined-header">
                    <Badge bg="dark" className="combined-badge"><i className="bi bi-link-45deg me-1"></i>Combined</Badge>
                    {hasMixedSubjects && <span className="mixed-subject-note"><i className="bi bi-patch-exclamation me-1"></i>Multiple subjects</span>}
                </div>
                <div className="combined-slots">
                    {group.slots.map(x => renderSlot(x, false, true))}
                </div>
                {primary.employee && <span className="teacher combined-teacher"><i className="bi bi-person me-1"></i>{primary.employee.name}</span>}
                {primary.status && primary.status !== 'approved' && (
                    <Badge pill bg={STATUS[primary.status]?.bg || 'secondary'} className="status-badge mt-1">{STATUS[primary.status]?.label || primary.status}</Badge>
                )}
            </div>
        )
    }

    const emp = employees?.find(e => e.id == filters.employee_id)
    const cls = classes?.find(c => c.id == filters.class_id)

    return (
        <AuthenticatedLayout>
            <Head title="Timetable - Master Timetable" />
            <FlashAlert message={flash?.success} />
            <FlashAlert message={flash?.error} variant="danger" />

            <Card className="border-0 shadow-sm">
                <Card.Body>
                    <div className="d-flex flex-wrap justify-content-between align-items-center mb-3 no-print gap-2">
                        <div>
                            <h5 className="mb-1">
                                {emp ? 'Staff Timetable' : cls ? 'Class Timetable' : 'Master Timetable'}
                            </h5>
                            <span className="text-muted small">
                                {emp ? <><i className="bi bi-person-badge me-1"></i>{emp.name}</> : null}
                                {cls ? <><i className="bi bi-mortarboard me-1"></i>{cls.label}</> : null}
                                {!emp && !cls ? <><i className="bi bi-calendar3 me-1"></i>All departments, staff and classes</> : null}
                            </span>
                        </div>
                        <div className="d-flex gap-2">
                            <Button variant="outline-secondary" size="sm" onClick={handlePrint}>
                                <i className="bi bi-printer me-1"></i>Print / PDF
                            </Button>
                        </div>
                    </div>

                    <Row className="mb-3 g-2 no-print">
                        {!isStaff && (
                            <>
                                <Col md={3}>
                                    <Select2 value={filters.employee_id} onChange={v => setFilters(f => ({ ...f, employee_id: v }))}
                                        options={employees?.map(e => ({ value: e.id, label: e.name }))} placeholder="All Employees" />
                                </Col>
                                <Col md={3}>
                                    <Select2 value={filters.class_id} onChange={v => setFilters(f => ({ ...f, class_id: v }))}
                                        options={classes?.map(c => ({ value: c.id, label: c.label }))} placeholder="All Classes" />
                                </Col>
                                <Col md={3}>
                                    <Select2 value={filters.day_of_week} onChange={v => setFilters(f => ({ ...f, day_of_week: v }))}
                                        options={dayNames.map((d, i) => ({ value: String(i + 1), label: d }))} placeholder="All Days" />
                                </Col>
                            </>
                        )}
                    </Row>

                    <div className="timetable-desktop">
                    <div className="table-responsive print-area">
                        <style>{`
                            .timetable-grid td, .timetable-grid th { vertical-align: middle; }
                            .timetable-grid .day-header { background: #f8fafc; font-weight: 700; min-width: 60px; color: #334155; }
                            .timetable-grid .period-header { background: #eef2ff; font-weight: 700; font-size: 0.8125rem; text-align: center; min-width: 150px; color: #3730a3; }
                            .timetable-grid .slot-cell { min-width: 150px; min-height: 88px; padding: 6px !important; height: auto; }
                            .timetable-grid .slot-cell.can-manage { cursor: pointer; transition: background 0.15s; }
                            .timetable-grid .slot-cell.can-manage:hover { background: #f0f7ff; }
                            .slot-group { display: flex; flex-direction: column; gap: 4px; align-items: flex-start; }
                            .slot-entry { display: flex; flex-direction: column; align-items: flex-start; gap: 1px; line-height: 1.25; }
                            .slot-entry.compact { flex-direction: row; flex-wrap: wrap; align-items: baseline; gap: 0 4px; }
                            .slot-entry.compact .subject { border-left: none; padding-left: 0; }
                            .slot-entry .subject { font-weight: 600; font-size: 0.75rem; color: #0f172a; border-left: 3px solid #4f46e5; padding-left: 5px; text-align: left; }
                            .slot-entry .subject .code { color: #64748b; font-weight: 500; }
                            .slot-entry .class-tag { font-size: 0.6875rem; color: #475569; font-weight: 500; display: flex; align-items: center; gap: 4px; text-align: left; }
                            .slot-entry .dept-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
                            .slot-entry .teacher, .combined-teacher { font-size: 0.7rem; color: #64748b; text-align: left; }
                            .combined-group { background: linear-gradient(135deg, #f5f3ff 0%, #eef2ff 100%); border: 1px solid #c7d2fe; border-radius: 6px; padding: 5px 7px; width: 100%; }
                            .combined-group.clickable:hover { border-color: #4f46e5; }
                            .combined-header { display: flex; align-items: center; gap: 6px; width: 100%; margin-bottom: 2px; }
                            .combined-badge { font-size: 0.6rem; padding: 1px 6px; letter-spacing: 0.4px; text-transform: uppercase; }
                            .mixed-subject-note { font-size: 0.62rem; color: #7c3aed; font-weight: 500; }
                            .combined-slots { display: flex; flex-direction: column; gap: 4px; width: 100%; }
                            .add-placeholder { font-size: 0.75rem; color: #a5b4fc; font-weight: 500; }
                            .slot-cell .add-placeholder { display: block; text-align: center; margin-top: 26px; }
                            .legend { display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.72rem; color: #64748b; margin-top: 12px; }
                            .legend .legend-item { display: flex; align-items: center; gap: 5px; }
                            .slot-entry .status-badge, .combined-group .status-badge { font-size: 0.6rem; padding: 1px 6px; font-weight: 600; letter-spacing: 0.3px; }
                        `}</style>
                        <Table bordered className="timetable-grid mb-0">
                            <thead>
                                <tr>
                                    <th className="day-header text-center">Day</th>
                                    {PERIODS.map(p => (
                                        <th key={p} className="period-header text-center">Period {p}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {dayNames.map((d, i) => {
                                    const dow = i + 1
                                    return (
                                        <tr key={dow}>
                                            <td className="day-header text-center">{d}</td>
                                            {PERIODS.map(pno => {
                                                const groups = getCellGroups(dow, pno)
                                                const canInteract = canManage || isStaff
                                                const handleClick = canInteract ? () => cellClick(groups, dow, pno) : undefined
                                                return (
                                                    <td key={pno} className={`slot-cell text-center p-1 ${canInteract ? 'can-manage' : ''}`}>
                                                        {groups.length > 0 ? (
                                                            groups.map(g => renderGroup(g, handleClick))
                                                        ) : canInteract ? (
                                                            <span className="add-placeholder" onClick={() => openCreate(dow, pno)}>+ Add</span>
                                                        ) : null}
                                                    </td>
                                                )
                                            })}
                                        </tr>
                                    )
                                })}
                            </tbody>
                        </Table>
                    </div>
                    </div>

                    <div className="timetable-mobile no-print">
                        {dayNames.map((d, i) => {
                            const dow = i + 1
                            return (
                                <div key={dow} className="timetable-day-card">
                                    <div className="timetable-day-title">
                                        <i className="bi bi-calendar-week me-2"></i>Day {d}
                                    </div>
                                    {PERIODS.map(pno => {
                                        const groups = getCellGroups(dow, pno)
                                        const canInteract = canManage || isStaff
                                        const handleClick = canInteract ? () => cellClick(groups, dow, pno) : undefined
                                        return (
                                            <div key={pno} className="timetable-period-row">
                                                <div className="timetable-period-num">P{pno}</div>
                                                <div className="timetable-period-body">
                                                    {groups.length > 0 ? (
                                                        groups.map(g => renderGroup(g, handleClick))
                                                    ) : canInteract ? (
                                                        <span className="add-placeholder" onClick={() => openCreate(dow, pno)}>+ Add</span>
                                                    ) : (
                                                        <span className="timetable-empty">No class</span>
                                                    )}
                                                </div>
                                            </div>
                                        )
                                    })}
                                </div>
                            )
                        })}
                    </div>

                    <div className="legend no-print">
                        <span className="legend-item"><Badge bg="dark"><i className="bi bi-link-45deg"></i></Badge> Combined class (same teacher, multiple classes)</span>
                        <span className="legend-item"><span className="dept-dot" style={{ background: '#4f46e5' }} /> Class colour indicates department</span>
                        {isStaff && <span className="legend-item"><Badge bg="warning" pill>Pending HOD</Badge> Awaiting HOD approval</span>}
                        {isHod && <span className="legend-item"><Badge bg="warning" pill>Pending HOD</Badge> Click to forward to Principal</span>}
                        {isPrincipalAdmin && <span className="legend-item"><Badge bg="info" pill>Pending Principal</Badge> Click to approve or reject</span>}
                        {(canManage || isStaff) && <span className="legend-item"><i className="bi bi-plus-circle text-muted me-1"></i>Click a cell to add or edit</span>}
                    </div>
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        {edit ? (editGroup?.length > 1 ? 'Edit Combined Class' : 'Edit Timetable Slot') : 'Add Timetable Slot'}
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        <Row>
                            <Col md={6}><Select2Field name="day_of_week" label="Day" control={control} errors={errors}
                                options={dayNames.map((d, i) => ({ value: String(i + 1), label: d }))} isClearable={false} /></Col>
                            <Col md={6}><Select2Field name="period_no" label="Period" control={control} errors={errors}
                                options={PERIODS.map(p => ({ value: String(p), label: String(p) }))} isClearable={false} /></Col>
                        </Row>
                        {isStaff ? (
                            <div className="mb-3">
                                <Form.Label>Employee</Form.Label>
                                <Form.Control type="text" value={employees?.find(e => e.id == user?.id)?.name || 'You'} readOnly />
                            </div>
                        ) : (
                            <Select2Field name="employee_id" label="Employee" control={control} errors={errors}
                                options={employees?.map(e => ({ value: e.id, label: e.name }))} isClearable={false} />
                        )}
                        <Row>
                            <Col md={6}>
                                <Select2Field name="class_id" label="Primary Class" control={control} errors={errors}
                                    options={allClasses?.map(c => ({ value: c.id, label: c.label }))} isClearable={false} />
                            </Col>
                            <Col md={6}>
                                <Select2Field name="subject_id" label="Subject" control={control} errors={errors}
                                    options={subjectOptions} isClearable={false} />
                            </Col>
                        </Row>

                        <hr />
                        <div className="d-flex justify-content-between align-items-center mb-2">
                            <h6 className="mb-0"><i className="bi bi-link-45deg me-1"></i>Combined Classes</h6>
                            <span className="text-muted small">Combine this slot with other classes taught together by the same teacher</span>
                        </div>
                        <Row className="g-2 mb-2">
                            <Col md={8}>
                                <Select2
                                    value={addPick}
                                    onChange={v => setAddPick(String(v ?? ''))}
                                    options={combinedClassOptions}
                                    placeholder="Select a class to combine..."
                                    isClearable
                                />
                            </Col>
                            <Col md={4} className="d-grid">
                                <Button variant="outline-primary" disabled={!addPick} onClick={() => { addCombinedClass(Number(addPick)); setAddPick('') }}>
                                    <i className="bi bi-plus-lg me-1"></i>Add Class
                                </Button>
                            </Col>
                        </Row>
                        {errors.combined_classes && <div className="text-danger small mb-2"><i className="bi bi-exclamation-circle me-1"></i>{errors.combined_classes.message}</div>}
                        {combined.length === 0 ? (
                            <div className="text-muted small p-2 border rounded bg-light">No combined classes. Add a class to teach two or more classes together in this slot.</div>
                        ) : (
                            <>
                                <Row className="px-2 mb-1 small text-muted fw-semibold">
                                    <Col md={6}>Class</Col>
                                    <Col md={5}>Subject</Col>
                                </Row>
                                {combined.map((c, idx) => (
                                    <div key={idx} className="border rounded p-2 mb-2">
                                        <Row className="g-2 align-items-center">
                                            <Col md={6}>
                                                <div className="fw-semibold small text-secondary text-truncate" title={getClassLabel(c.class_id)}>
                                                    <i className="bi bi-mortarboard me-1"></i>{getClassLabel(c.class_id)}
                                                </div>
                                            </Col>
                                            <Col md={5}>
                                                <Select2
                                                    value={c.subject_id}
                                                    onChange={v => setCombinedSubject(idx, v)}
                                                    options={subjectOptions}
                                                    placeholder="Select subject (may differ from primary)"
                                                    isClearable
                                                />
                                            </Col>
                                            <Col md={1} className="text-end">
                                                <Button size="sm" variant="outline-danger" onClick={() => removeCombinedClass(idx)}><i className="bi bi-x-lg"></i></Button>
                                            </Col>
                                        </Row>
                                    </div>
                                ))}
                            </>
                        )}
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShow(false)}>Cancel</Button>
                        {edit && (isStaff || canManage) && (isStaff ? edit.status !== 'approved' : true) && <Button variant="outline-danger" onClick={() => { del(edit); setShow(false) }}>Delete</Button>}
                        {edit && edit.status === 'pending_hod' && isHod && (
                            <Button variant="success" onClick={() => { forwardSlot(edit); setShow(false) }}>
                                <i className="bi bi-arrow-right-circle me-1"></i>Forward to Principal
                            </Button>
                        )}
                        {edit && ['pending_hod', 'pending_principal'].includes(edit.status) && isPrincipalAdmin && (
                            <Button variant="success" onClick={() => { approveSlot(edit); setShow(false) }}>
                                <i className="bi bi-check-circle me-1"></i>Approve
                            </Button>
                        )}
                        {edit && ['pending_hod', 'pending_principal'].includes(edit.status) && (isHod || isPrincipalAdmin) && (
                            <Button variant="outline-danger" onClick={() => { rejectSlot(edit); setShow(false) }}>
                                <i className="bi bi-x-circle me-1"></i>Reject
                            </Button>
                        )}
                        {edit && edit.status === 'approved' && !canManage && <Form.Text className="text-muted">Approved slot - cannot be edited</Form.Text>}
                        <Button type="submit" variant="primary" disabled={edit && !canEditSlot(edit)}>{edit ? 'Update' : 'Save'}</Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    )
}
