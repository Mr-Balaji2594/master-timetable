import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Modal, Form, Row, Col, Badge } from 'react-bootstrap'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import Select2Field from '../../Components/Select2Field'
import FormField from '../../Components/FormField'
import DataTable from '../../Components/DataTable'
import FormErrors from '../../Components/FormErrors'
import FlashAlert from '../../Components/FlashAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const dayNames = ['I', 'II', 'III', 'IV', 'V', 'VI']

const today = () => {
    const d = new Date()
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const toIsoDate = (raw) => {
    if (!raw) return today()
    const s = String(raw)
    if (/^\d{4}-\d{2}-\d{2}/.test(s)) return s.substring(0, 10)
    const m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/)
    if (m) return `${m[3]}-${m[2].padStart(2, '0')}-${m[1].padStart(2, '0')}`
    return today()
}

const schema = z.object({
    original_employee_id: z.string().min(1, 'Original staff is required'),
    substitute_employee_id: z.string().min(1, 'Substitute is required'),
    class_id: z.string().min(1, 'Class is required'),
    subject_id: z.string().min(1, 'Subject is required'),
    day_of_week: z.string().min(1, 'Day is required'),
    period_no: z.string().min(1, 'Period is required'),
    leave_date: z.string().min(1, 'Date is required')
})

const compSchema = z.object({
    substitution_id: z.string().min(1, 'Required'),
    original_employee_id: z.string().min(1, 'Required'),
    substitute_employee_id: z.string().min(1, 'Required'),
    class_id: z.string().min(1, 'Required'),
    subject_id: z.string().min(1, 'Required'),
    day_of_week: z.string().min(1, 'Required'),
    period_no: z.string().min(1, 'Required'),
    leave_date: z.string().min(1, 'Required'),
})

export default function Index({ substitutions, employees, allEmployees, classes, subjects }) {
    const { auth, flash } = usePage().props
    const user = auth?.user
    const isStaff = user?.role === 'staff'
    const [show, setShow] = useState(false)
    const [showComp, setShowComp] = useState(false)
    const [compSub, setCompSub] = useState(null)
    const defaults = { original_employee_id: isStaff ? String(user.id) : '', substitute_employee_id: '', class_id: '', subject_id: '', day_of_week: '', period_no: '', leave_date: today() }
    const { control, handleSubmit, reset, setError, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: defaults
    })
    const compForm = useForm({ resolver: zodResolver(compSchema) })

    const openAssign = () => { reset(defaults); setShow(true) }
    const submit = handleSubmit((formData) => {
        const done = () => { setShow(false); reset(defaults) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        router.post('/substitution', formData, { onSuccess: done, onError, preserveState: true })
    })
    const openCompensation = (sub) => {
        setCompSub(sub)
        compForm.reset({
            substitution_id: String(sub.id),
            original_employee_id: String(sub.substitute_employee?.id),
            substitute_employee_id: String(sub.original_employee?.id),
            class_id: String(sub.class_id),
            subject_id: String(sub.subject_id),
            day_of_week: String(sub.day_of_week),
            period_no: String(sub.period_no),
            leave_date: toIsoDate(sub.leave_date),
        })
        setShowComp(true)
    }
    const submitComp = compForm.handleSubmit((formData) => {
        const done = () => { setShowComp(false); setCompSub(null); compForm.reset() }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => compForm.setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        router.post('/compensations', formData, { onSuccess: done, onError, preserveState: true })
    })

    return (
        <AuthenticatedLayout>
            <Head title="Substitution - Master Timetable" />
            <FlashAlert message={flash?.success} />
            <FlashAlert message={flash?.error} variant="danger" />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">Substitutions</h5>
                        <Button onClick={openAssign}><i className="bi bi-plus-lg me-1"></i>Assign</Button>
                    </div>
                    <DataTable data={substitutions} columns={[
                        { header: 'Original Staff', accessorKey: 'original_employee.name' },
                        { header: 'Substitute', accessorKey: 'substitute_employee.name' },
                        { header: 'Class', accessorKey: 'class.name' },
                        { header: 'Subject', accessorKey: 'subject.name' },
                        { header: 'Day/Period', cell: ({ row }) => `${dayNames[row.original.day_of_week - 1] || row.original.day_of_week}/${dayNames[row.original.period_no - 1] || row.original.period_no}` },
                        { header: 'Date', accessorKey: 'leave_date' },
                        { header: 'Status', accessorKey: 'status', cell: ({ getValue }) => {
                            const color = getValue() === 'completed' ? 'success' : getValue() === 'cancelled' ? 'danger' : 'warning'
                            return <Badge bg={color}>{getValue().charAt(0).toUpperCase() + getValue().slice(1)}</Badge>
                        }},
                        { header: 'Actions', id: 'actions', enableSorting: false, cell: ({ row }) => {
                            const s = row.original
                            return s.compensated ? (
                                <Badge bg="info" style={{ fontSize: '11px' }}><i className="bi bi-check-circle me-1"></i>Compensated</Badge>
                            ) : (
                                s.status === 'pending' && (
                                    <Button size="sm" variant="outline-info" onClick={() => openCompensation(s)}>
                                        <i className="bi bi-arrow-left-right me-1"></i>Compensation
                                    </Button>
                                )
                            )
                        }},
                    ]} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)} size="lg">
                <Modal.Header closeButton><Modal.Title>Assign Substitution</Modal.Title></Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        <Row>
                            <Col md={6}>
                                {isStaff ? (
                                    <Form.Group className="mb-3">
                                        <Form.Label>Original Staff</Form.Label>
                                        <Form.Control type="text" value={user?.name} disabled />
                                    </Form.Group>
                                ) : (
                                    <Select2Field name="original_employee_id" label="Original Staff" control={control} errors={errors}
                                        options={employees?.map(e => ({ value: e.id, label: e.name }))} isClearable={false} />
                                )}
                            </Col>
                            <Col md={6}><Select2Field name="substitute_employee_id" label="Substitute" control={control} errors={errors}
                                options={allEmployees?.filter(e => !isStaff || e.id !== user.id).map(e => ({ value: e.id, label: e.name }))} isClearable={false} /></Col>
                        </Row>
                        <Row>
                            <Col md={6}><Select2Field name="class_id" label="Class" control={control} errors={errors}
                                options={classes?.map(c => ({ value: c.id, label: `${c.name} - ${c.department?.name} - ${c.year}` }))} isClearable={false} /></Col>
                            <Col md={6}><Select2Field name="subject_id" label="Subject" control={control} errors={errors}
                                options={subjects?.map(s => ({ value: s.id, label: s.name }))} isClearable={false} /></Col>
                        </Row>
                        <Row>
                            <Col md={4}><Select2Field name="day_of_week" label="Day" control={control} errors={errors}
                                options={dayNames.map((d, i) => ({ value: String(i + 1), label: d }))} isClearable={false} /></Col>
                            <Col md={4}><Select2Field name="period_no" label="Period" control={control} errors={errors}
                                options={dayNames.map((p, i) => ({ value: String(i + 1), label: p }))} isClearable={false} /></Col>
                            <Col md={4}><FormField name="leave_date" label="Date" type="date" control={control} errors={errors} /></Col>
                        </Row>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShow(false)}>Cancel</Button>
                        <Button type="submit" variant="primary">Save</Button>
                    </Modal.Footer>
                </Form>
            </Modal>

            <Modal show={showComp} onHide={() => setShowComp(false)} size="lg">
                <Modal.Header closeButton><Modal.Title>Create Compensation</Modal.Title></Modal.Header>
                <Form onSubmit={submitComp}>
                    <Modal.Body>
                        <FormErrors errors={compForm.formState.errors} />
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Original Staff</Form.Label>
                                    <Form.Control type="text" value={compSub?.substitute_employee?.name || ''} disabled />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Substitute</Form.Label>
                                    <Form.Control type="text" value={compSub?.original_employee?.name || ''} disabled />
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Class</Form.Label>
                                    <Form.Control type="text" value={compSub?.class?.name || ''} disabled />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Select2Field name="subject_id" label="Subject" control={compForm.control} errors={compForm.formState.errors}
                                    options={subjects?.map(s => ({ value: s.id, label: s.name }))} isClearable={false} />
                            </Col>
                        </Row>
                        <Row>
                            <Col md={4}>
                                <Select2Field name="day_of_week" label="Day" control={compForm.control} errors={compForm.formState.errors}
                                    options={dayNames.map((d, i) => ({ value: String(i + 1), label: d }))} isClearable={false} />
                            </Col>
                            <Col md={4}>
                                <Select2Field name="period_no" label="Period" control={compForm.control} errors={compForm.formState.errors}
                                    options={dayNames.map((p, i) => ({ value: String(i + 1), label: p }))} isClearable={false} />
                            </Col>
                            <Col md={4}>
                                <FormField name="leave_date" label="Date" type="date" control={compForm.control} errors={compForm.formState.errors} />
                            </Col>
                        </Row>
                        <input type="hidden" {...compForm.register('original_employee_id')} />
                        <input type="hidden" {...compForm.register('substitute_employee_id')} />
                        <input type="hidden" {...compForm.register('class_id')} />
                        <input type="hidden" {...compForm.register('substitution_id')} />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowComp(false)}>Cancel</Button>
                        <Button type="submit" variant="primary">Create Compensation</Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    )
}
