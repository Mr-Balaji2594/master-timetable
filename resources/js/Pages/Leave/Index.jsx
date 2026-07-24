import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Modal, Form, Row, Col, Badge } from 'react-bootstrap'
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
    { value: 'onduty', label: 'On Duty' }, { value: 'permission', label: 'Permission' },
    { value: 'deputation', label: 'Deputation' }
]

const schema = z.object({
    employee_id: z.string().min(1),
    leave_date: z.string().min(1, 'Leave date is required'),
    due_date: z.string().min(1, 'Due date is required'),
    nature: z.string().min(1, 'Nature is required'),
    days: z.string().min(1, 'Days is required'),
    reason: z.string().min(1, 'Reason is required')
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
    const [filter, setFilter] = useState({ status: '', employee_id: '' })

    const isStaff = user?.role === 'staff'

    const defaults = { employee_id: String(user?.id ?? ''), leave_date: '', due_date: '', nature: 'casual', days: '1', reason: '' }
    const { control, handleSubmit, reset, setError, setValue, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: defaults
    })

    const leaveDate = useWatch({ control, name: 'leave_date' })
    const dueDate = useWatch({ control, name: 'due_date' })
    const nature = useWatch({ control, name: 'nature' })

    useEffect(() => {
        setValue('days', calcDays(leaveDate, dueDate))
    }, [leaveDate, dueDate, setValue])

    const limitKey = nature ? `${nature}_leave_limit` : null
    const availedKey = nature ? (nature === 'permission' || nature === 'deputation' ? `${nature}_availed` : `${nature}_leave_availed`) : null
    const limit = limitKey && leaveBalance ? (leaveBalance[limitKey] ?? 0) : 0
    const availed = availedKey && leaveBalance ? (leaveBalance[availedKey] ?? 0) : 0
    const available = limit - availed

    const openApply = () => { reset(defaults); setShow(true) }
    const submit = handleSubmit((formData) => {
        const done = () => { setShow(false); reset(defaults) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        router.post('/leave', formData, { onSuccess: done, onError })
    })

    const filtered = leaves.filter(l =>
        (!filter.status || l.status === filter.status) &&
        (!filter.employee_id || l.employee_id == filter.employee_id)
    )

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
                            ]} placeholder="All Status" /></Col>
                        {!isStaff && <Col md={3}><Select2 value={filter.employee_id} onChange={v => setFilter(f => ({ ...f, employee_id: v }))}
                            options={employees?.map(e => ({ value: e.id, label: e.name }))} placeholder="All Employees" /></Col>}
                    </Row>
                    <DataTable data={filtered} columns={[
                        { header: 'Employee', accessorKey: 'employee.name' },
                        { header: 'Leave Date', accessorKey: 'leave_date' },
                        { header: 'Due Date', accessorKey: 'due_date' },
                        { header: 'Nature', accessorKey: 'nature' },
                        { header: 'Days', accessorKey: 'days', cell: ({ getValue }) => getValue() || 1 },
                        { header: 'Status', accessorKey: 'status', cell: ({ getValue }) => { const s = getValue() || 'pending_hod'; return <Badge bg={statusColors[s] || 'secondary'}>{s}</Badge> } },
                        { header: 'Actions', id: 'actions', enableSorting: false, cell: ({ row }) => {
                            const id = row.original.id
                            const status = row.original.status || 'pending_hod'
                            const confirm = (opts) => Swal.fire({ title: opts.title, text: opts.text, icon: 'question', showCancelButton: true, confirmButtonColor: opts.color || '#198754', cancelButtonColor: '#6c757d', confirmButtonText: opts.confirmText || 'Yes', cancelButtonText: 'Cancel' })
                            return (<>
                                {user?.role === 'hod' && status === 'pending_hod' && (
                                    <Button size="sm" variant="outline-success" className="me-1" onClick={() => confirm({ title: 'Forward Leave?', text: 'Forward this leave request to principal?', confirmText: 'Forward' }).then(r => r.isConfirmed && router.post(`/leave/${id}/approve-hod`, {}))}>
                                        <i className="bi bi-check"></i> Forward
                                    </Button>
                                )}
                                {['principal', 'admin', 'super_admin'].includes(user?.role) && ['pending_hod', 'pending_principal'].includes(status) && (
                                    <Button size="sm" variant="outline-success" className="me-1" onClick={() => confirm({ title: 'Approve Leave?', text: 'Approve this leave request?', confirmText: 'Approve', color: '#198754' }).then(r => r.isConfirmed && router.post(`/leave/${id}/approve-principal`, {}))}>
                                        <i className="bi bi-check"></i> Approve
                                    </Button>
                                )}
                                {['hod', 'principal', 'admin', 'super_admin'].includes(user?.role) && !['approved', 'rejected'].includes(status) && (
                                    <Button size="sm" variant="outline-danger" onClick={() => confirm({ title: 'Reject Leave?', text: 'Reject this leave request?', confirmText: 'Reject', color: '#dc3545' }).then(r => r.isConfirmed && router.post(`/leave/${id}/reject`, {}))}>
                                        <i className="bi bi-x"></i> Reject
                                    </Button>
                                )}
                            </>)
                        }},
                    ]} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton><Modal.Title>Apply Leave</Modal.Title></Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        {leaveBalance && (
                            <div className="mb-3 p-2 bg-light rounded border">
                                <small className="text-muted">Available Balance: </small>
                                <strong>{available}</strong>
                                <small className="text-muted"> / {limit} days</small>
                            </div>
                        )}
                        <Row>
                            <Col md={6}><FormField name="leave_date" label="Leave Date" type="date" control={control} errors={errors} /></Col>
                            <Col md={6}><FormField name="due_date" label="Due Date" type="date" control={control} errors={errors} /></Col>
                        </Row>
                        <Row>
                            <Col md={6}><Select2Field name="nature" label="Nature" control={control} errors={errors}
                                options={natureOptions} isClearable={false} /></Col>
                            <Col md={6}><FormField name="days" label="Days" type="number" control={control} errors={errors} /></Col>
                        </Row>
                        <FormField name="reason" label="Reason" as="textarea" rows={3} control={control} errors={errors} />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShow(false)}>Cancel</Button>
                        <Button type="submit" variant="primary">Submit</Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    )
}
