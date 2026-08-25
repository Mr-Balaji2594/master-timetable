import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Modal, Form, Row, Col, Badge } from 'react-bootstrap'
import { useState, useMemo, useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import Select2 from '../../Components/Select2'
import Select2Field from '../../Components/Select2Field'
import FormField from '../../Components/FormField'
import DataTable, { formatDate } from '../../Components/DataTable'
import FormErrors from '../../Components/FormErrors'
import FlashAlert from '../../Components/FlashAlert'
import { showConfirmCustom } from '../../Helpers/sweetAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const romanNumerals = ['I', 'II', 'III', 'IV', 'V', 'VI']

const today = () => {
    const d = new Date()
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const dayOptions = romanNumerals.map((r, i) => ({ value: String(i + 1), label: r }))

const periodOptions = romanNumerals.map((r, i) => ({ value: String(i + 1), label: r }))

const unitOptions = ['I', 'II', 'III', 'IV', 'V'].map(r => ({ value: r, label: r }))

const semesterOptions = [
    { value: 'Odd', label: 'Odd' },
    { value: 'Even', label: 'Even' },
]

const statusColors = { pending_hod: 'warning', pending_principal: 'info', approved: 'success', rejected: 'danger' }

const schema = z.object({
    plan_date: z.string().min(1, 'Date is required'),
    class_id: z.string().min(1, 'Class is required'),
    subject_id: z.string().min(1, 'Subject is required'),
    day: z.string().optional(),
    period: z.string().optional(),
    period_to: z.string().optional(),
    semester: z.string().optional(),
    topic: z.string().min(1, 'Topic is required'),
    unit: z.string().optional(),
    description: z.string().optional(),
    employee_id: z.union([z.string(), z.number()])
}).superRefine((data, ctx) => {
    if (data.period_to) {
        if (!data.period) {
            ctx.addIssue({ code: 'custom', message: 'Start period is required', path: ['period'] })
        } else if (Number(data.period_to) <= Number(data.period)) {
            ctx.addIssue({ code: 'custom', message: 'End period must be after start period', path: ['period_to'] })
        }
    }
})

const periodLabel = (p) => {
    const start = p?.period ? romanNumerals[p.period - 1] : null
    if (start && p.period_to) return `${start} – ${romanNumerals[p.period_to - 1]}`
    return start || '-'
}

export default function Index({ plans, classes, subjects, employees }) {
    const { auth, flash } = usePage().props
    const user = auth?.user
    const isHod = user?.role === 'hod'
    const isPrincipalAdmin = ['principal', 'admin', 'super_admin'].includes(user?.role)
    const isApprover = isHod || isPrincipalAdmin
    const [show, setShow] = useState(false)
    const [showDetail, setShowDetail] = useState(false)
    const [detail, setDetail] = useState(null)
    const [filter, setFilter] = useState({ class_id: '', subject_id: '', status: '' })
    const [selected, setSelected] = useState(() => new Set())
    const [edit, setEdit] = useState(null)
    const [combined, setCombined] = useState([])
    const [addPick, setAddPick] = useState('')

    const defaults = { plan_date: today(), class_id: '', subject_id: '', day: '', period: '', period_to: '', semester: '', topic: '', unit: '', description: '', employee_id: String(user.id) }
    const { control, handleSubmit, reset, setError, setValue, watch, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: defaults
    })

    const watchPeriod = watch('period')
    const watchPeriodTo = watch('period_to')
    const combinedPeriod = !!watchPeriodTo
    const periodToOptions = romanNumerals
        .map((r, i) => ({ value: String(i + 1), label: r }))
        .filter(o => Number(o.value) > (Number(watchPeriod) || 0))

    const watchClass = watch('class_id')
    const combinedClassIds = combined.map(c => String(c))

    useEffect(() => {
        if (watchClass) {
            setCombined(prev => prev.filter(c => String(c) !== String(watchClass)))
        }
    }, [watchClass])

    const combinedClassOptions = useMemo(() => {
        return (classes || [])
            .filter(c => String(c.id) !== String(watchClass) && !combinedClassIds.includes(String(c.id)))
            .map(c => ({ value: c.id, label: `${c.name} - ${c.department?.name || ''} - ${c.year || ''}` }))
    }, [classes, watchClass, combinedClassIds])

    const getClassLabel = (id) => {
        const c = classes?.find(c => String(c.id) === String(id))
        return c ? `${c.name} - ${c.department?.name || ''} - ${c.year || ''}` : `Class #${id}`
    }

    const addCombinedClass = (classId) => {
        if (!classId) return
        setCombined(prev => [...prev, classId])
        setAddPick('')
    }

    const removeCombinedClass = (index) => {
        setCombined(prev => prev.filter((_, i) => i !== index))
    }

    const toggleCombined = (checked) => {
        setValue('period_to', checked ? String((Number(watchPeriod) || 0) + 1) : '', { shouldValidate: true })
    }

    const openCreate = () => { reset(defaults); setCombined([]); setShow(true); setEdit(null) }
    const openEdit = (lp) => {
        reset({
            ...lp,
            plan_date: lp.plan_date || '',
            class_id: String(lp.class_id ?? ''),
            subject_id: String(lp.subject_id ?? ''),
            day: String(lp.day ?? ''),
            period: String(lp.period ?? ''),
            period_to: lp.period_to ? String(lp.period_to) : '',
            semester: lp.semester || '',
            employee_id: String(lp.employee_id ?? ''),
        })
        setCombined((lp.combined_classes || []).map(c => c.id))
        setEdit(lp)
        setShow(true)
    }
    const submit = handleSubmit((formData) => {
        const cleaned = Object.fromEntries(
            Object.entries(formData).map(([k, v]) => [k, v === '' ? null : v])
        )
        const combinedPayload = combined.map(c => Number(c))
        if (combinedPayload.length !== new Set(combinedPayload).size) {
            setError('combined_classes', { message: 'A class cannot be combined with itself' })
            return
        }
        const payload = { ...cleaned, combined_classes: combinedPayload.length ? combinedPayload : [] }
        const done = () => { setShow(false); setEdit(null); reset(defaults); setCombined([]) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        if (edit) {
            router.put(`/lesson-plans/${edit.id}`, payload, { onSuccess: done, onError, preserveState: true })
        } else {
            router.post('/lesson-plans', payload, { onSuccess: done, onError, preserveState: true })
        }
    })

    const filtered = plans.filter(lp =>
        (!filter.class_id || lp.class_id == filter.class_id) &&
        (!filter.subject_id || lp.subject_id == filter.subject_id) &&
        (!filter.status || (lp.status || 'pending_hod') === filter.status)
    )

    const canBulkSelect = (lp) => {
        const s = lp.status || 'pending_hod'
        if (isHod) return s === 'pending_hod' && lp.employee_id !== user.id
        if (isPrincipalAdmin) return ['pending_hod', 'pending_principal'].includes(s)
        return false
    }

    const isPlanManager = ['admin', 'super_admin', 'principal', 'vice_principal'].includes(user?.role)

    const canEditPlan = (lp) => {
        const s = lp.status || 'pending_hod'
        if (['approved', 'rejected'].includes(s)) return false
        if (isPlanManager) return true
        const isOwner = user?.id === lp.employee_id
        const isDeptHod = user?.role === 'hod' && lp.employee_id !== user.id
        if (!isOwner && !isDeptHod) return false
        return user?.role === 'staff' ? s === 'pending_hod' : true
    }

    const canDeletePlan = (lp) => {
        const s = lp.status || 'pending_hod'
        const isOwner = user?.id === lp.employee_id
        const isDeptHod = user?.role === 'hod' && lp.employee_id !== user.id
        const isManagement = ['admin', 'super_admin', 'principal'].includes(user?.role)
        if (!(isOwner || isManagement || isDeptHod)) return false
        if (['approved', 'rejected'].includes(s)) return isManagement
        return user?.role === 'staff' ? s === 'pending_hod' : true
    }

    const selectable = filtered.filter(canBulkSelect)
    const allSelected = selectable.length > 0 && selectable.every(lp => selected.has(lp.id))

    const toggleSelect = (id) => {
        setSelected(prev => {
            const next = new Set(prev)
            next.has(id) ? next.delete(id) : next.add(id)
            return next
        })
    }

    const toggleSelectAll = () => {
        if (allSelected) setSelected(new Set())
        else setSelected(new Set(selectable.map(lp => lp.id)))
    }

    const clearSelection = () => setSelected(new Set())

    const bulkConfirm = (title, text, url, confirmColor = '#198754', confirmText = 'Yes') => {
        showConfirmCustom({ title, text, icon: 'question', confirmText, confirmColor }).then(r => {
            if (r.isConfirmed) {
                router.post(url, { ids: [...selected] }, { preserveState: true, onSuccess: clearSelection })
            }
        })
    }

    const openDetail = (lp) => { setDetail(lp); setShowDetail(true) }

    const DetailRow = ({ label, value }) => (
        <Col md={4} className="pb-2">
            <div className="text-muted small">{label}</div>
            <div className="fw-semibold">{value || '-'}</div>
        </Col>
    )

    return (
        <AuthenticatedLayout>
            <Head title="Lesson Plans - Master Timetable" />
            <FlashAlert message={flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">Lesson Plans</h5>
                        <Button onClick={openCreate}><i className="bi bi-plus-lg me-1"></i>Add</Button>
                    </div>
                    <Row className="mb-3 g-2">
                        {user?.role !== 'staff' && (
                            <>
                                <Col md={3}><Select2 value={filter.class_id} onChange={v => setFilter(f => ({ ...f, class_id: v }))}
                                    options={classes?.map(c => ({ value: c.id, label: `${c.name} - ${c.department?.name} - ${c.year}` }))} placeholder="All Classes" /></Col>
                                <Col md={3}><Select2 value={filter.subject_id} onChange={v => setFilter(f => ({ ...f, subject_id: v }))}
                                    options={subjects?.map(s => ({ value: s.id, label: s.name }))} placeholder="All Subjects" /></Col>
                            </>
                        )}
                        <Col md={user?.role === 'staff' ? 3 : 3}><Select2 value={filter.status} onChange={v => setFilter(f => ({ ...f, status: v }))}
                            options={[
                                { value: 'pending_hod', label: 'Pending HOD' },
                                { value: 'pending_principal', label: 'Pending Principal' },
                                { value: 'approved', label: 'Approved' },
                                { value: 'rejected', label: 'Rejected' },
                            ].filter(o => !(user?.role === 'principal' && o.value === 'pending_hod'))} placeholder="All Status" /></Col>
                    </Row>
                    {isApprover && selected.size > 0 && (
                        <Row className="mb-3 g-2 align-items-center p-2 bg-light rounded border">
                            <Col md={4}>
                                <strong>{selected.size}</strong> selected
                                <Button size="sm" variant="link" className="ms-2 p-0" onClick={clearSelection}>Clear</Button>
                            </Col>
                            <Col md={8} className="text-md-end">
                                {isHod && (
                                    <Button size="sm" variant="success" className="me-2" onClick={() => bulkConfirm('Forward Selected?', `Forward ${selected.size} lesson plan(s) to the principal?`, '/lesson-plans/bulk-approve-hod', '#198754', 'Forward')}>
                                        <i className="bi bi-check2-all me-1"></i>Forward Selected
                                    </Button>
                                )}
                                {isPrincipalAdmin && (
                                    <Button size="sm" variant="success" className="me-2" onClick={() => bulkConfirm('Approve Selected?', `Approve ${selected.size} lesson plan(s)?`, '/lesson-plans/bulk-approve-principal', '#198754', 'Approve')}>
                                        <i className="bi bi-check2-all me-1"></i>Approve Selected
                                    </Button>
                                )}
                                <Button size="sm" variant="danger" onClick={() => bulkConfirm('Reject Selected?', `Reject ${selected.size} lesson plan(s)?`, '/lesson-plans/bulk-reject', '#dc3545', 'Reject')}>
                                    <i className="bi bi-x-lg me-1"></i>Reject Selected
                                </Button>
                            </Col>
                        </Row>
                    )}
                    <DataTable data={filtered} columns={[
                        ...(isApprover ? [{
                            id: 'select',
                            enableSorting: false,
                            enableColumnFilter: false,
                            header: () => <Form.Check type="checkbox" aria-label="Select all" checked={allSelected} onChange={toggleSelectAll} />,
                            cell: ({ row }) => {
                                const lp = row.original
                                const eligible = canBulkSelect(lp)
                                return <Form.Check type="checkbox" aria-label="Select" disabled={!eligible} checked={eligible && selected.has(lp.id)} onChange={() => toggleSelect(lp.id)} />
                            }
                        }] : []),
                        { header: 'View', id: 'view', enableSorting: false, cell: ({ row }) => (
                            <Button size="sm" variant="outline-secondary" onClick={() => openDetail(row.original)}>
                                <i className="bi bi-eye"></i>
                            </Button>
                        ) },
                        { header: 'Date', accessorKey: 'plan_date' },
                        { header: 'Day', id: 'day_display', cell: ({ row }) => row.original.day ? romanNumerals[row.original.day - 1] || '-' : '-' },
                        { header: 'Period', id: 'period_display', cell: ({ row }) => periodLabel(row.original) },
                        { header: 'Employee', accessorKey: 'employee.name' },
                        { header: 'Class', id: 'class_display', cell: ({ row }) => {
                            const lp = row.original
                            const c = lp.class
                            return (
                                <>
                                    {c ? `${c.name} - ${c.department?.name || ''} - ${c.year || ''}` : '-'}
                                    {lp.combined_classes?.length > 0 && (
                                        <Badge bg="dark" className="ms-2" style={{ fontSize: '10px', verticalAlign: 'middle' }}>
                                            <i className="bi bi-link-45deg me-1"></i>Combined
                                        </Badge>
                                    )}
                                </>
                            )
                        } },
                        { header: 'Subject', accessorKey: 'subject.name' },
                        { header: 'Semester', accessorKey: 'semester', cell: ({ getValue }) => getValue() || '-' },
                        { header: 'Topic', accessorKey: 'topic' },
                        { header: 'Unit', accessorKey: 'unit' },
                        { header: 'Status', accessorKey: 'status', cell: ({ getValue }) => <Badge bg={statusColors[getValue()] || 'secondary'}>{getValue()}</Badge> },
                        { header: 'Actions', id: 'actions', enableSorting: false, cell: ({ row }) => (
                            <>
                                {canEditPlan(row.original) && (
                                    <Button size="sm" variant="outline-primary" className="me-1" onClick={() => openEdit(row.original)}>
                                        <i className="bi bi-pencil-square"></i>
                                    </Button>
                                )}
                                {user?.role === 'hod' && row.original.employee_id !== user.id && row.original.status === 'pending_hod' && (
                                    <Button size="sm" variant="outline-success" className="me-1" onClick={async () => {
                                        const r = await showConfirmCustom({ title: 'Forward Plan?', text: 'Forward this lesson plan to principal for approval?', confirmText: 'Forward', confirmColor: '#198754' })
                                        if (r.isConfirmed) router.post(`/lesson-plans/${row.original.id}/approve-hod`, {}, { preserveState: true })
                                    }}>
                                        <i className="bi bi-check"></i> Forward
                                    </Button>
                                )}
                                {user?.role === 'principal' && row.original.status === 'pending_principal' && (
                                    <Button size="sm" variant="outline-success" className="me-1" onClick={async () => {
                                        const r = await showConfirmCustom({ title: 'Approve Plan?', text: 'Approve this lesson plan?', confirmText: 'Approve', confirmColor: '#198754' })
                                        if (r.isConfirmed) router.post(`/lesson-plans/${row.original.id}/approve-principal`, {}, { preserveState: true })
                                    }}>
                                        <i className="bi bi-check"></i> Approve
                                    </Button>
                                )}
                                {(['principal', 'admin', 'super_admin'].includes(user?.role) || (user?.role === 'hod' && row.original.employee_id !== user.id)) && !['approved', 'rejected'].includes(row.original.status) && (
                                    <Button size="sm" variant="outline-danger" className="me-1" onClick={async () => {
                                        const r = await showConfirmCustom({ title: 'Reject Plan?', text: 'Reject this lesson plan?', confirmText: 'Reject', confirmColor: '#dc3545' })
                                        if (r.isConfirmed) router.post(`/lesson-plans/${row.original.id}/reject`, {}, { preserveState: true })
                                    }}>
                                        <i className="bi bi-x"></i> Reject
                                    </Button>
                                )}
                                {canDeletePlan(row.original) && (
                                    <Button size="sm" variant="outline-danger" onClick={async () => {
                                        const r = await showConfirmCustom({ title: 'Delete Plan?', text: 'Delete this lesson plan permanently?', confirmText: 'Delete', confirmColor: '#dc3545' })
                                        if (r.isConfirmed) router.delete(`/lesson-plans/${row.original.id}`)
                                    }}>
                                        <i className="bi bi-trash"></i>
                                    </Button>
                                )}
                            </>
                        )},
                    ]} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)} size="lg">
                <Modal.Header closeButton><Modal.Title>{edit ? 'Edit' : 'Add'} Lesson Plan</Modal.Title></Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        <Row>
                            <Col md={4}><FormField name="plan_date" label="Date" type="date" control={control} errors={errors} /></Col>
                            <Col md={4}><Select2Field name="day" label="Day" control={control} errors={errors}
                                options={dayOptions} isClearable={true} /></Col>
                            <Col md={4}><Select2Field name="period" label="Period" control={control} errors={errors}
                                options={periodOptions} isClearable={true} /></Col>
                        </Row>
                        {combinedPeriod && (
                            <Row>
                                <Col md={4}><Select2Field name="period_to" label="Period To" control={control} errors={errors}
                                    options={periodToOptions} isClearable={true} /></Col>
                            </Row>
                        )}
                        <div className="mb-3">
                            <Form.Check
                                type="switch"
                                id="combined_period"
                                label="Combined Periods (e.g. labs, practicals — spans two consecutive periods)"
                                checked={combinedPeriod}
                                onChange={e => toggleCombined(e.target.checked)}
                            />
                        </div>
                        <Row>
                            <Col md={4}><Select2Field name="class_id" label="Class" control={control} errors={errors}
                                options={classes?.map(c => ({ value: c.id, label: `${c.name} - ${c.department?.name} - ${c.year}` }))} isClearable={false} /></Col>
                            <Col md={4}><Select2Field name="subject_id" label="Subject" control={control} errors={errors}
                                options={(() => {
                                    const groups = {}
                                    subjects?.forEach(s => {
                                        const dept = s.department?.name || 'Other'
                                        if (!groups[dept]) groups[dept] = { label: dept, options: [] }
                                        groups[dept].options.push({ value: s.id, label: s.name })
                                    })
                                    return Object.values(groups)
                                })()} isClearable={false} /></Col>
                            <Col md={4}><Select2Field name="semester" label="Semester" control={control} errors={errors}
                                options={semesterOptions} isClearable={true} /></Col>
                        </Row>
                        <hr />
                        <div className="d-flex justify-content-between align-items-center mb-2">
                            <h6 className="mb-0"><i className="bi bi-link-45deg me-1"></i>Combined Classes</h6>
                            <span className="text-muted small">Add classes taught together with this lesson plan</span>
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
                                <Button variant="outline-primary" disabled={!addPick} onClick={() => addCombinedClass(Number(addPick))}>
                                    <i className="bi bi-plus-lg me-1"></i>Add Class
                                </Button>
                            </Col>
                        </Row>
                        {errors.combined_classes && <div className="text-danger small mb-2"><i className="bi bi-exclamation-circle me-1"></i>{errors.combined_classes.message}</div>}
                        {combined.length === 0 ? (
                            <div className="text-muted small p-2 border rounded bg-light">No combined classes. Add a class to teach this lesson to two or more classes together.</div>
                        ) : (
                            <>
                                <Row className="px-2 mb-1 small text-muted fw-semibold">
                                    <Col md={11}>Class</Col>
                                </Row>
                                {combined.map((classId, idx) => (
                                    <div key={idx} className="border rounded p-2 mb-2">
                                        <Row className="g-2 align-items-center">
                                            <Col md={11}>
                                                <div className="fw-semibold small text-secondary text-truncate" title={getClassLabel(classId)}>
                                                    <i className="bi bi-mortarboard me-1"></i>{getClassLabel(classId)}
                                                </div>
                                            </Col>
                                            <Col md={1} className="text-end">
                                                <Button size="sm" variant="outline-danger" onClick={() => removeCombinedClass(idx)}><i className="bi bi-x-lg"></i></Button>
                                            </Col>
                                        </Row>
                                    </div>
                                ))}
                            </>
                        )}
                        <Row>
                            <Col md={6}><FormField name="topic" label="Topic" control={control} errors={errors} /></Col>
                            <Col md={6}><Select2Field name="unit" label="Unit" control={control} errors={errors}
                                options={unitOptions} isClearable={true} /></Col>
                        </Row>
                        <FormField name="description" label="Description" as="textarea" rows={3} control={control} errors={errors} />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShow(false)}>Cancel</Button>
                        <Button type="submit" variant="primary">Save</Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            <Modal show={showDetail} onHide={() => setShowDetail(false)} size="lg">
                <Modal.Header closeButton><Modal.Title>Lesson Plan Detail</Modal.Title></Modal.Header>
                <Modal.Body>
                    {detail && (
                        <>
                            <div className="d-flex justify-content-between align-items-center mb-3">
                                <Badge bg={statusColors[detail.status] || 'secondary'}>{detail.status}</Badge>
                                <div className="text-muted">
                                    {detail.plan_date && <span>Date: {formatDate(detail.plan_date)}</span>}
                                </div>
                            </div>
                            <Row>
                                <DetailRow label="Employee" value={detail.employee?.emp_id ? `${detail.employee.name} (${detail.employee.emp_id})` : detail.employee?.name} />
                                <DetailRow label="Subject" value={detail.subject?.code ? `${detail.subject.name} (${detail.subject.code})` : detail.subject?.name} />
                                <DetailRow label="Class" value={(() => {
                                    const c = detail.class
                                    return c ? `${c.name} - ${c.department?.name || ''} - ${c.year || ''}` : '-'
                                })()} />
                                {detail.combined_classes?.length > 0 && (
                                    <DetailRow label="Combined Classes" value={detail.combined_classes.map(c => `${c.name} - ${c.department?.name || ''} - ${c.year || ''}`).join(', ')} />
                                )}
                                <DetailRow label="Day" value={detail.day ? romanNumerals[detail.day - 1] || '-' : '-'} />
                                <DetailRow label="Period" value={periodLabel(detail)} />
                                <DetailRow label="Semester" value={detail.semester} />
                                <DetailRow label="Date" value={formatDate(detail.plan_date)} />
                                <DetailRow label="Unit" value={detail.unit} />
                                <DetailRow label="Created By" value={detail.employee?.name} />
                            </Row>
                            <hr />
                            <div className="mb-2"><strong>Topic</strong></div>
                            <p>{detail.topic || '-'}</p>
                            <div className="mb-2"><strong>Description</strong></div>
                            <p className="text-muted">{detail.description || 'No description provided.'}</p>
                            <hr />
                            <Row>
                                <DetailRow label="HOD" value={detail.hod_approver?.name} />
                                <DetailRow label="HOD Approved At" value={formatDate(detail.hod_approved_at)} />
                                <DetailRow label="Principal" value={detail.principal_approver?.name} />
                                <DetailRow label="Principal Approved At" value={formatDate(detail.principal_approved_at)} />
                            </Row>
                        </>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    {detail && user?.role === 'hod' && detail.employee_id !== user.id && detail.status === 'pending_hod' && (
                        <Button variant="success" onClick={async () => {
                            const r = await showConfirmCustom({ title: 'Forward Plan?', text: 'Forward this lesson plan to principal for approval?', confirmText: 'Forward', confirmColor: '#198754' })
                            if (r.isConfirmed) router.post(`/lesson-plans/${detail.id}/approve-hod`, {}, { onSuccess: () => setShowDetail(false), preserveState: true })
                        }}>
                            <i className="bi bi-check me-1"></i>Forward
                        </Button>
                    )}
                    {detail && user?.role === 'principal' && detail.status === 'pending_principal' && (
                        <Button variant="success" onClick={async () => {
                            const r = await showConfirmCustom({ title: 'Approve Plan?', text: 'Approve this lesson plan?', confirmText: 'Approve', confirmColor: '#198754' })
                            if (r.isConfirmed) router.post(`/lesson-plans/${detail.id}/approve-principal`, {}, { onSuccess: () => setShowDetail(false), preserveState: true })
                        }}>
                            <i className="bi bi-check me-1"></i>Approve
                        </Button>
                    )}
                    <Button variant="secondary" onClick={() => setShowDetail(false)}>Close</Button>
                    {detail && canDeletePlan(detail) && (
                        <Button variant="danger" onClick={async () => {
                            const r = await showConfirmCustom({ title: 'Delete Plan?', text: 'Delete this lesson plan permanently?', confirmText: 'Delete', confirmColor: '#dc3545' })
                            if (r.isConfirmed) router.delete(`/lesson-plans/${detail.id}`, {}, { onSuccess: () => setShowDetail(false) })
                        }}>
                            <i className="bi bi-trash me-1"></i>Delete
                        </Button>
                    )}
                </Modal.Footer>
            </Modal>
        </AuthenticatedLayout>
    )
}
