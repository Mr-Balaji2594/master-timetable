import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Modal, Form, Row, Col, Badge } from 'react-bootstrap'
import { useState, useMemo } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import Select2 from '../../Components/Select2'
import Select2Field from '../../Components/Select2Field'
import FormField from '../../Components/FormField'
import DataTable from '../../Components/DataTable'
import FormErrors from '../../Components/FormErrors'
import FlashAlert from '../../Components/FlashAlert'
import { showConfirmCustom } from '../../Helpers/sweetAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const statusColors = { pending_hod: 'warning', pending_principal: 'info', approved: 'success', rejected: 'danger' }

const schema = z.object({
    plan_date: z.string().min(1, 'Date is required'),
    class_id: z.string().min(1, 'Class is required'),
    subject_id: z.string().min(1, 'Subject is required'),
    topic: z.string().min(1, 'Topic is required'),
    unit: z.string().optional(),
    description: z.string().optional(),
    employee_id: z.string().min(1)
})

export default function Index({ plans, classes, subjects, employees }) {
    const { auth, flash } = usePage().props
    const user = auth?.user
    const [show, setShow] = useState(false)
    const [filter, setFilter] = useState({ class_id: '', subject_id: '' })
    const [edit, setEdit] = useState(null)

    const defaults = { plan_date: '', class_id: '', subject_id: '', topic: '', unit: '', description: '', employee_id: user.id }
    const { control, handleSubmit, reset, setError, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: defaults
    })

    const openCreate = () => { reset(defaults); setShow(true) }
    const openEdit = (lp) => { reset({ ...lp, plan_date: lp.plan_date?.split('/').reverse().join('-'), class_id: String(lp.class_id ?? ''), subject_id: String(lp.subject_id ?? ''), employee_id: String(lp.employee_id ?? '') }); setEdit(lp); setShow(true) }
    const submit = handleSubmit((formData) => {
        const done = () => { setShow(false); setEdit(null); reset(defaults) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        router.post('/lesson-plans', formData, { onSuccess: done, onError })
    })

    const filtered = plans.filter(lp =>
        (!filter.class_id || lp.class_id == filter.class_id) &&
        (!filter.subject_id || lp.subject_id == filter.subject_id)
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
                        <Col md={3}><Select2 value={filter.class_id} onChange={v => setFilter(f => ({ ...f, class_id: v }))}
                            options={classes?.map(c => ({ value: c.id, label: c.name }))} placeholder="All Classes" /></Col>
                        <Col md={3}><Select2 value={filter.subject_id} onChange={v => setFilter(f => ({ ...f, subject_id: v }))}
                            options={subjects?.map(s => ({ value: s.id, label: s.name }))} placeholder="All Subjects" /></Col>
                    </Row>
                    <DataTable data={filtered} columns={[
                        { header: 'Date', accessorKey: 'plan_date' },
                        { header: 'Employee', accessorKey: 'employee.name' },
                        { header: 'Class', accessorKey: 'class.name' },
                        { header: 'Subject', accessorKey: 'subject.name' },
                        { header: 'Topic', accessorKey: 'topic' },
                        { header: 'Unit', accessorKey: 'unit' },
                        { header: 'Status', accessorKey: 'status', cell: ({ getValue }) => <Badge bg={statusColors[getValue()] || 'secondary'}>{getValue()}</Badge> },
                        { header: 'Actions', id: 'actions', enableSorting: false, cell: ({ row }) => (
                            <>
                                {user?.role === 'hod' && row.original.status === 'pending_hod' && (
                                    <Button size="sm" variant="outline-success" className="me-1" onClick={async () => {
                                        const r = await showConfirmCustom({ title: 'Forward Plan?', text: 'Forward this lesson plan to principal for approval?', confirmText: 'Forward', confirmColor: '#198754' })
                                        if (r.isConfirmed) router.post(`/lesson-plans/${row.original.id}/approve-hod`)
                                    }}>
                                        <i className="bi bi-check"></i> Forward
                                    </Button>
                                )}
                                {user?.role === 'principal' && row.original.status === 'pending_principal' && (
                                    <Button size="sm" variant="outline-success" className="me-1" onClick={async () => {
                                        const r = await showConfirmCustom({ title: 'Approve Plan?', text: 'Approve this lesson plan?', confirmText: 'Approve', confirmColor: '#198754' })
                                        if (r.isConfirmed) router.post(`/lesson-plans/${row.original.id}/approve-principal`)
                                    }}>
                                        <i className="bi bi-check"></i> Approve
                                    </Button>
                                )}
                                {['hod', 'principal', 'admin', 'super_admin'].includes(user?.role) && !['approved', 'rejected'].includes(row.original.status) && (
                                    <Button size="sm" variant="outline-danger" onClick={async () => {
                                        const r = await showConfirmCustom({ title: 'Reject Plan?', text: 'Reject this lesson plan?', confirmText: 'Reject', confirmColor: '#dc3545' })
                                        if (r.isConfirmed) router.post(`/lesson-plans/${row.original.id}/reject`)
                                    }}>
                                        <i className="bi bi-x"></i> Reject
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
                            <Col md={4}><Select2Field name="class_id" label="Class" control={control} errors={errors}
                                options={classes?.map(c => ({ value: c.id, label: c.name }))} isClearable={false} /></Col>
                            <Col md={4}><Select2Field name="subject_id" label="Subject" control={control} errors={errors}
                                options={subjects?.map(s => ({ value: s.id, label: s.name }))} isClearable={false} /></Col>
                        </Row>
                        <Row>
                            <Col md={6}><FormField name="topic" label="Topic" control={control} errors={errors} /></Col>
                            <Col md={6}><FormField name="unit" label="Unit" control={control} errors={errors} /></Col>
                        </Row>
                        <FormField name="description" label="Description" as="textarea" rows={3} control={control} errors={errors} />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShow(false)}>Cancel</Button>
                        <Button type="submit" variant="primary">Save</Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    )
}
