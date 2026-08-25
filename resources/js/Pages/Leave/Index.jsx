import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Modal, Form, Row, Col, Badge, Alert } from 'react-bootstrap'
import { useState, useEffect } from 'react'
import { useForm, useWatch } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import Select2 from '../../Components/Select2'
import Select2Field from '../../Components/Select2Field'
import FormField from '../../Components/FormField'
import DataTable from '../../Components/DataTable'
import FormErrors from '../../Components/FormErrors'
import FlashAlert from '../../Components/FlashAlert'
import Swal from 'sweetalert2'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const statusColors = { pending_hod: 'warning', pending_principal: 'info', approved: 'success', rejected: 'danger' }

const natureOptions = [
    { value: 'casual', label: 'Casual' }, { value: 'medical', label: 'Medical' },
    { value: 'onduty', label: 'On Duty' }, { value: 'early_permission', label: 'Early Permission' },
    { value: 'late_permission', label: 'Late Permission' }, { value: 'deputation', label: 'Deputation' },
    { value: 'earned', label: 'Earned (EL)' }
]

const balanceKeys = {
    casual: { limitKey: 'casual_leave_limit', availedKey: 'casual_leave_availed' },
    medical: { limitKey: 'medical_leave_limit', availedKey: 'medical_leave_availed' },
    onduty: { limitKey: 'onduty_leave_limit', availedKey: 'onduty_leave_availed' },
    early_permission: { limitKey: 'early_permission_limit', availedKey: 'early_permission_availed' },
    late_permission: { limitKey: 'late_permission_limit', availedKey: 'late_permission_availed' },
    deputation: { limitKey: 'deputation_limit', availedKey: 'deputation_availed' },
    earned: { limitKey: 'earned_leave_limit', availedKey: 'earned_leave_availed' },
}

const PERMISSION_NATURES = ['early_permission', 'late_permission']
const HALF_DAY_NATURES = ['casual', 'medical', 'earned']

const schema = z.object({
    employee_id: z.string().min(1),
    leave_date: z.string().min(1, 'Leave date is required'),
    due_date: z.string().optional(),
    nature: z.string().min(1, 'Nature is required'),
    days: z.string().min(1, 'Duration is required'),
    reason: z.string().min(1, 'Reason is required'),
    half_day: z.boolean().optional(),
    start_time: z.string().optional(),
    due_time: z.string().optional(),
}).superRefine((data, ctx) => {
    if (PERMISSION_NATURES.includes(data.nature)) {
        if (!data.start_time) ctx.addIssue({ code: 'custom', message: 'Start time is required', path: ['start_time'] })
        if (!data.due_time) ctx.addIssue({ code: 'custom', message: 'Due time is required', path: ['due_time'] })
        if (data.start_time && data.due_time && data.due_time <= data.start_time) {
            ctx.addIssue({ code: 'custom', message: 'Due time must be after start time', path: ['due_time'] })
        }
    }
})

function calcDays(start, end) {
    if (!start || !end) return '1'
    const s = new Date(start), e = new Date(end)
    if (e < s) return '1'
    const diff = Math.floor((e - s) / (1000 * 60 * 60 * 24)) + 1
    return String(diff)
}

export default function Index({ leaves, employees, leaveBalance }) {
    const { auth, flash } = usePage().props
    const user = auth?.user
    const [show, setShow] = useState(false)
    const [edit, setEdit] = useState(null)
    const [filter, setFilter] = useState({ status: '', employee_id: '' })
    const [selected, setSelected] = useState(() => new Set())

    const isStaff = user?.role === 'staff'
    const isHod = user?.role === 'hod'
    const isPrincipalAdmin = ['principal', 'admin', 'super_admin'].includes(user?.role)
    const isApprover = isHod || isPrincipalAdmin

    const defaults = { employee_id: String(user?.id ?? ''), leave_date: '', due_date: '', start_time: '', due_time: '', nature: 'casual', days: '1', reason: '', half_day: false }
    const { control, handleSubmit, reset, setError, setValue, register, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: defaults
    })

    const leaveDate = useWatch({ control, name: 'leave_date' })
    const dueDate = useWatch({ control, name: 'due_date' })
    const nature = useWatch({ control, name: 'nature' })
    const halfDay = useWatch({ control, name: 'half_day' })
    const startTime = useWatch({ control, name: 'start_time' })
    const dueTime = useWatch({ control, name: 'due_time' })

    const isPermission = PERMISSION_NATURES.includes(nature)

    function calcHours(start, end) {
        if (!start || !end) return '0'
        const [sh, sm] = start.split(':').map(Number)
        const [eh, em] = end.split(':').map(Number)
        const mins = (eh * 60 + em) - (sh * 60 + sm)
        return mins > 0 ? String(mins / 60) : '0'
    }

    useEffect(() => {
        if (isPermission) {
            setValue('days', calcHours(startTime, dueTime))
        } else if (halfDay) {
            setValue('days', '0.5')
        } else {
            setValue('days', calcDays(leaveDate, dueDate))
        }
    }, [isPermission, startTime, dueTime, leaveDate, dueDate, halfDay, setValue])

    useEffect(() => {
        if (!HALF_DAY_NATURES.includes(nature) && halfDay) {
            setValue('half_day', false)
        }
    }, [nature, halfDay, setValue])

    const bk = balanceKeys[nature] || {}
    const limit = bk.limitKey && leaveBalance ? (leaveBalance[bk.limitKey] ?? 0) : 0
    const availed = bk.availedKey && leaveBalance ? (leaveBalance[bk.availedKey] ?? 0) : 0
    const availableNum = Number(limit) - Number(availed)
    const available = availableNum.toFixed(1).replace(/\.0$/, '')
    const natureLabel = natureOptions.find(o => o.value === nature)?.label || nature
    const noBalance = availableNum <= 0

    const openApply = () => { reset(defaults); setEdit(null); setShow(true) }

    const openEdit = (l) => {
        reset({
            ...defaults,
            employee_id: String(l.employee_id ?? user.id),
            leave_date: l.leave_date ? String(l.leave_date).slice(0, 10) : '',
            due_date: l.due_date ? String(l.due_date).slice(0, 10) : '',
            start_time: l.start_time ? l.start_time.slice(0, 5) : '',
            due_time: l.due_time ? l.due_time.slice(0, 5) : '',
            nature: l.nature || 'casual',
            days: l.days || '1',
            half_day: HALF_DAY_NATURES.includes(l.nature) && Number(l.days) === 0.5,
            reason: l.reason || '',
        })
        setEdit(l)
        setShow(true)
    }

    const canManageRequest = (l) => {
        const s = l.status || 'pending_hod'
        if (['approved', 'rejected'].includes(s)) return false
        if (['admin', 'super_admin', 'principal'].includes(user?.role)) return true
        if (user?.id != l.employee_id) return false
        return user?.role === 'staff' ? s === 'pending_hod' : true
    }

    const submit = handleSubmit((formData) => {
        if (noBalance) return
        const done = () => { setShow(false); setEdit(null); reset(defaults) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        const method = edit ? 'put' : 'post'
        const url = edit ? `/leave/${edit.id}` : '/leave'
        router[method](url, formData, { onSuccess: done, onError, preserveState: true })
    })

    const handleDelete = (l) => {
        const r = Swal.fire({
            title: 'Delete Request?',
            text: 'This leave request will be permanently removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
        })
        r.then(res => { if (res.isConfirmed) router.delete(`/leave/${l.id}`, { preserveState: true }) })
    }

    const filtered = leaves.filter(l =>
        (!filter.status || l.status === filter.status) &&
        (!filter.employee_id || l.employee_id == filter.employee_id)
    )

    const canBulkSelect = (l) => {
        const s = l.status || 'pending_hod'
        if (isPrincipalAdmin) return ['pending_hod', 'pending_principal'].includes(s)
        if (isHod) return s === 'pending_hod'
        return false
    }

    const selectable = filtered.filter(canBulkSelect)
    const allSelected = selectable.length > 0 && selectable.every(l => selected.has(l.id))

    const toggleSelect = (id) => {
        setSelected(prev => {
            const next = new Set(prev)
            next.has(id) ? next.delete(id) : next.add(id)
            return next
        })
    }

    const toggleSelectAll = () => {
        if (allSelected) setSelected(new Set())
        else setSelected(new Set(selectable.map(l => l.id)))
    }

    const clearSelection = () => setSelected(new Set())

    const bulkConfirm = (title, text, url, color = '#198754', confirmText = 'Yes') => {
        Swal.fire({ title, text, icon: 'question', showCancelButton: true, confirmButtonColor: color, cancelButtonColor: '#6c757d', confirmButtonText: confirmText, cancelButtonText: 'Cancel' })
            .then(r => {
                if (r.isConfirmed) {
                    router.post(url, { ids: [...selected] }, { preserveState: true, onSuccess: clearSelection })
                }
            })
    }

    return (
        <AuthenticatedLayout>
            <Head title="Leave - Master Timetable" />
            <FlashAlert message={flash?.success} />

            <FlashAlert message={flash?.error} variant="danger" />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">Leave Requests</h5>
                        <Button onClick={openApply}><i className="bi bi-plus-lg me-1"></i>Apply</Button>
                    </div>
                    <Row className="mb-3 g-2">
                        <Col md={3}><Select2 value={filter.status} onChange={v => setFilter(f => ({ ...f, status: v }))}
                            options={[
                                { value: 'pending_hod', label: 'Pending HOD' },
                                { value: 'pending_principal', label: 'Pending Principal' },
                                { value: 'approved', label: 'Approved' },
                                { value: 'rejected', label: 'Rejected' },
                            ].filter(o => !(user?.role === 'principal' && o.value === 'pending_hod'))} placeholder="All Status" /></Col>
                        {!isStaff && <Col md={3}><Select2 value={filter.employee_id} onChange={v => setFilter(f => ({ ...f, employee_id: v }))}
                            options={employees?.map(e => ({ value: e.id, label: e.name }))} placeholder="All Employees" /></Col>}
                    </Row>
                    {isApprover && selected.size > 0 && (
                        <Row className="mb-3 g-2 align-items-center p-2 bg-light rounded border">
                            <Col md={4}>
                                <strong>{selected.size}</strong> selected
                                <Button size="sm" variant="link" className="ms-2 p-0" onClick={clearSelection}>Clear</Button>
                            </Col>
                            <Col md={8} className="text-md-end">
                                {isHod && (
                                    <Button size="sm" variant="success" className="me-2" onClick={() => bulkConfirm('Forward Selected?', `Forward ${selected.size} leave request(s) to the principal?`, '/leave/bulk-approve-hod', '#198754', 'Forward')}>
                                        <i className="bi bi-check2-all me-1"></i>Forward Selected
                                    </Button>
                                )}
                                {isPrincipalAdmin && (
                                    <Button size="sm" variant="success" className="me-2" onClick={() => bulkConfirm('Approve Selected?', `Approve ${selected.size} leave request(s)?`, '/leave/bulk-approve-principal', '#198754', 'Approve')}>
                                        <i className="bi bi-check2-all me-1"></i>Approve Selected
                                    </Button>
                                )}
                                <Button size="sm" variant="danger" onClick={() => bulkConfirm('Reject Selected?', `Reject ${selected.size} leave request(s)?`, '/leave/bulk-reject', '#dc3545', 'Reject')}>
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
                                const l = row.original
                                const eligible = canBulkSelect(l)
                                return <Form.Check type="checkbox" aria-label="Select" disabled={!eligible} checked={eligible && selected.has(l.id)} onChange={() => toggleSelect(l.id)} />
                            }
                        }] : []),
                        { header: 'Employee', accessorKey: 'employee.name' },
                        { header: 'Leave Date', accessorKey: 'leave_date' },
                        { header: 'Due Date', accessorKey: 'due_date', cell: ({ row }) => row.original.due_date || '—' },
                        {
                            header: 'Time',
                            id: 'time',
                            cell: ({ row }) => {
                                const s = row.original.start_time
                                const e = row.original.due_time
                                return s && e ? `${s.slice(0, 5)} - ${e.slice(0, 5)}` : '—'
                            }
                        },
                        { header: 'Nature', accessorKey: 'nature' },
                        {
                            header: 'Days / Hours',
                            id: 'duration',
                            cell: ({ row }) => PERMISSION_NATURES.includes(row.original.nature)
                                ? `${row.original.days} hr`
                                : row.original.days || 1
                        },
                        { header: 'Status', accessorKey: 'status', cell: ({ getValue }) => { const s = getValue() || 'pending_hod'; return <Badge bg={statusColors[s] || 'secondary'}>{s}</Badge> } },
                        { header: 'Actions', id: 'actions', enableSorting: false, cell: ({ row }) => {
                            const id = row.original.id
                            const status = row.original.status || 'pending_hod'
                            const confirm = (opts) => Swal.fire({ title: opts.title, text: opts.text, icon: 'question', showCancelButton: true, confirmButtonColor: opts.color || '#198754', cancelButtonColor: '#6c757d', confirmButtonText: opts.confirmText || 'Yes', cancelButtonText: 'Cancel' })
                            return (<>
                                {canManageRequest(row.original) && (
                                    <Button size="sm" variant="outline-primary" className="me-1" onClick={() => openEdit(row.original)}>
                                        <i className="bi bi-pencil-square"></i>
                                    </Button>
                                )}
                                {canManageRequest(row.original) && (
                                    <Button size="sm" variant="outline-danger" className="me-1" onClick={() => handleDelete(row.original)}>
                                        <i className="bi bi-trash"></i>
                                    </Button>
                                )}
                                {user?.role === 'hod' && status === 'pending_hod' && (
                                    <Button size="sm" variant="outline-success" className="me-1" onClick={() => confirm({ title: 'Forward Leave?', text: 'Forward this leave request to principal?', confirmText: 'Forward' }).then(r => r.isConfirmed && router.post(`/leave/${id}/approve-hod`, {}, { preserveState: true }))}>
                                        <i className="bi bi-check"></i> Forward
                                    </Button>
                                )}
                                {['principal', 'admin', 'super_admin'].includes(user?.role) && ['pending_hod', 'pending_principal'].includes(status) && (
                                    <Button size="sm" variant="outline-success" className="me-1" onClick={() => confirm({ title: 'Approve Leave?', text: 'Approve this leave request?', confirmText: 'Approve', color: '#198754' }).then(r => r.isConfirmed && router.post(`/leave/${id}/approve-principal`, {}, { preserveState: true }))}>
                                        <i className="bi bi-check"></i> Approve
                                    </Button>
                                )}
                                {['hod', 'principal', 'admin', 'super_admin'].includes(user?.role) && !['approved', 'rejected'].includes(status) && (
                                    <Button size="sm" variant="outline-danger" onClick={() => confirm({ title: 'Reject Leave?', text: 'Reject this leave request?', confirmText: 'Reject', color: '#dc3545' }).then(r => r.isConfirmed && router.post(`/leave/${id}/reject`, {}, { preserveState: true }))}>
                                        <i className="bi bi-x"></i> Reject
                                    </Button>
                                )}
                            </>)
                        }},
                    ]} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton><Modal.Title>{edit ? 'Edit Leave Request' : 'Apply Leave'}</Modal.Title></Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        {leaveBalance && (
                            <div className="mb-3 p-2 bg-light rounded border">
                                <small className="text-muted">{isPermission ? 'Available Hours: ' : 'Available Balance: '}</small>
                                <strong>{available}</strong>
                                <small className="text-muted"> / {limit} {isPermission ? 'hours' : 'days'}</small>
                            </div>
                        )}
                        {leaveBalance && noBalance && (
                            <Alert variant={availableNum < 0 ? 'danger' : 'warning'} className="py-2">
                                <i className={`bi ${availableNum < 0 ? 'bi-exclamation-octagon-fill' : 'bi-exclamation-triangle-fill'} me-1`}></i>
                                {isPermission
                                    ? `You have no ${natureLabel} hours remaining (${availableNum}). You cannot apply for this permission type.`
                                    : `You have no ${natureLabel} leave balance remaining (${availableNum}). You cannot apply for this leave type.`}
                            </Alert>
                        )}
                        {isPermission ? (
                            <>
                                <Row>
                                    <Col md={6}><FormField name="leave_date" label={nature === 'early_permission' ? 'Early Leave Date' : 'Late Arrival Date'} type="date" control={control} errors={errors} /></Col>
                                </Row>
                                <Row>
                                    <Col md={6}><FormField name="start_time" label="Start Time" type="time" control={control} errors={errors} /></Col>
                                    <Col md={6}><FormField name="due_time" label="Due Time" type="time" control={control} errors={errors} /></Col>
                                </Row>
                                <div className="mb-3 p-2 bg-light rounded border">
                                    <small className="text-muted">Duration: </small>
                                    <strong>{calcHours(startTime, dueTime)}</strong>
                                    <small className="text-muted"> hour(s)</small>
                                </div>
                                <input type="hidden" {...register('days')} />
                            </>
                        ) : (
                            <>
                                <Row>
                                    <Col md={6}><FormField name="leave_date" label="Leave Date" type="date" control={control} errors={errors} /></Col>
                                    <Col md={6}><FormField name="due_date" label="Due Date" type="date" control={control} errors={errors} /></Col>
                                </Row>
                            </>
                        )}
                        <Row>
                            <Col md={6}><Select2Field name="nature" label="Nature" control={control} errors={errors}
                                options={natureOptions} isClearable={false} /></Col>
                            {!isPermission && <Col md={6}><FormField name="days" label="Days" type="number" step="0.5" control={control} errors={errors} /></Col>}
                        </Row>
                        {HALF_DAY_NATURES.includes(nature) && (
                            <div className="mb-3">
                                <Form.Check
                                    type="switch"
                                    id="half_day"
                                    label="Half Day Leave"
                                    checked={!!halfDay}
                                    onChange={e => setValue('half_day', e.target.checked)}
                                />
                                <small className="text-muted">Half day deducts 0.5 day from your {natureLabel} balance.</small>
                            </div>
                        )}
                        <FormField name="reason" label="Reason" as="textarea" rows={3} control={control} errors={errors} />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShow(false)}>Cancel</Button>
                        <Button type="submit" variant="primary" disabled={!!leaveBalance && noBalance} title={noBalance ? 'No balance remaining for this leave type' : ''}>Submit</Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    )
}
