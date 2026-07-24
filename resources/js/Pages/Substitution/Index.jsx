import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Modal, Form, Row, Col, Badge } from 'react-bootstrap'
import { useState, useMemo } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import Select2Field from '../../Components/Select2Field'
import FormField from '../../Components/FormField'
import DataTable from '../../Components/DataTable'
import FormErrors from '../../Components/FormErrors'
import FlashAlert from '../../Components/FlashAlert'
import { showConfirm } from '../../Helpers/sweetAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

const schema = z.object({
    original_employee_id: z.string().min(1, 'Original staff is required'),
    substitute_employee_id: z.string().min(1, 'Substitute is required'),
    class_id: z.string().min(1, 'Class is required'),
    subject_id: z.string().min(1, 'Subject is required'),
    day_of_week: z.string().min(1, 'Day is required'),
    period_no: z.string().min(1, 'Period is required'),
    leave_date: z.string().min(1, 'Date is required')
})

export default function Index({ substitutions, employees, classes, subjects }) {
    const { auth, flash } = usePage().props
    const user = auth?.user
    const canDelete = ['admin', 'super_admin', 'principal', 'hod'].includes(user?.role)
    const [show, setShow] = useState(false)
    const defaults = { original_employee_id: '', substitute_employee_id: '', class_id: '', subject_id: '', day_of_week: '', period_no: '', leave_date: '' }
    const { control, handleSubmit, reset, setError, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: defaults
    })

    const openAssign = () => { reset(defaults); setShow(true) }
    const submit = handleSubmit((formData) => {
        const done = () => { setShow(false); reset(defaults) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        router.post('/substitution', formData, { onSuccess: done, onError })
    })
    const handleDelete = async (sub) => {
        const result = await showConfirm('Remove Substitution?', 'Remove this substitution?')
        if (result.isConfirmed) router.delete(`/substitution/${sub.id}`)
    }

    return (
        <AuthenticatedLayout>
            <Head title="Substitution - Master Timetable" />
            <FlashAlert message={flash?.success} />

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
                        { header: 'Day/Period', cell: ({ row }) => `${row.original.day_of_week}/${row.original.period_no}` },
                        { header: 'Date', accessorKey: 'leave_date' },
                        { header: 'Status', accessorKey: 'status', cell: ({ getValue }) => {
                            const color = getValue() === 'completed' ? 'success' : getValue() === 'cancelled' ? 'danger' : 'warning'
                            return <Badge bg={color}>{getValue()}</Badge>
                        }},
                        { header: 'Actions', id: 'actions', enableSorting: false, cell: ({ row }) => (
                            canDelete && <Button size="sm" variant="outline-danger" onClick={() => handleDelete(row.original)}><i className="bi bi-trash"></i></Button>
                        )},
                    ]} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton><Modal.Title>Assign Substitution</Modal.Title></Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        <Row>
                            <Col md={6}><Select2Field name="original_employee_id" label="Original Staff" control={control} errors={errors}
                                options={employees?.map(e => ({ value: e.id, label: e.name }))} isClearable={false} /></Col>
                            <Col md={6}><Select2Field name="substitute_employee_id" label="Substitute" control={control} errors={errors}
                                options={employees?.map(e => ({ value: e.id, label: e.name }))} isClearable={false} /></Col>
                        </Row>
                        <Row>
                            <Col md={6}><Select2Field name="class_id" label="Class" control={control} errors={errors}
                                options={classes?.map(c => ({ value: c.id, label: c.name }))} isClearable={false} /></Col>
                            <Col md={6}><Select2Field name="subject_id" label="Subject" control={control} errors={errors}
                                options={subjects?.map(s => ({ value: s.id, label: s.name }))} isClearable={false} /></Col>
                        </Row>
                        <Row>
                            <Col md={4}><Select2Field name="day_of_week" label="Day" control={control} errors={errors}
                                options={dayNames.map((d, i) => ({ value: String(i + 1), label: d }))} isClearable={false} /></Col>
                            <Col md={4}><Select2Field name="period_no" label="Period" control={control} errors={errors}
                                options={[1, 2, 3, 4, 5, 6].map(p => ({ value: String(p), label: String(p) }))} isClearable={false} /></Col>
                            <Col md={4}><FormField name="leave_date" label="Date" type="date" control={control} errors={errors} /></Col>
                        </Row>
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
