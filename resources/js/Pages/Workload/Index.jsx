import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Modal, Form, Row, Col } from 'react-bootstrap'
import { useMemo, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import * as z from 'zod'
import DataTable from '../../Components/DataTable'
import FlashAlert from '../../Components/FlashAlert'
import Select2Field from '../../Components/Select2Field'
import Select2 from '../../Components/Select2'
import AuthenticatedLayout from '../../Layouts/Authenticated'

export default function Index({ workloads, years, departments }) {
    const { auth } = usePage().props
    const user = auth?.user
    const isAdmin = ['admin', 'super_admin', 'principal', 'vice_principal'].includes(user?.role)
    const [showModal, setShowModal] = useState(false)
    const [editing, setEditing] = useState(null)
    const [filterYear, setFilterYear] = useState('')

    const schema = z.object({
        year: z.string().min(1, 'Year is required'),
        sem_mode: z.string().min(1, 'Semester is required'),
        department_id: z.string().min(1, 'Department is required'),
        subject_name: z.string().min(1, 'Subject name is required'),
        total_hours: z.string().min(1, 'Total hours is required'),
    })

    const { control, register, handleSubmit, reset, formState: { errors } } = useForm({
        resolver: zodResolver(schema),
    })

    const filteredWorkloads = useMemo(() =>
        filterYear ? workloads.filter(w => w.year === filterYear) : workloads,
        [workloads, filterYear]
    )

    const totalHours = useMemo(() =>
        filteredWorkloads.reduce((sum, w) => sum + Number(w.total_hours), 0),
        [filteredWorkloads]
    )

    const yearOptions = useMemo(() => years.map(y => ({ value: y, label: y })), [years])

    const deptOptions = useMemo(() => departments.map(d => ({ value: String(d.id), label: d.name })), [departments])

    const semOptions = useMemo(() => [
        { value: 'odd', label: 'Odd' },
        { value: 'even', label: 'Even' },
    ], [])

    const columns = useMemo(() => [
        { header: 'S.No', id: 'sno', cell: ({ row }) => row.index + 1 },
        { header: 'Year', accessorKey: 'year' },
        { header: 'Sem', accessorKey: 'sem_mode' },
        { header: 'Department', accessorKey: 'department.name', cell: ({ getValue }) => getValue() || '-' },
        { header: 'Subject Name', accessorKey: 'subject_name' },
        { header: 'Hours', accessorKey: 'total_hours' },
        ...(isAdmin ? [{
            header: 'Action', id: 'action', enableSorting: false,
            cell: ({ row }) => (
                <Button variant="outline-primary" size="sm" onClick={() => openEdit(row.original)}>
                    <i className="bi bi-pencil"></i>
                </Button>
            )
        }] : []),
    ], [isAdmin])

    const totalColumns = useMemo(() => [
        { header: '', id: 'spacer' },
        { header: '', id: 'spacer2' },
        { header: '', id: 'spacer3' },
        { header: '', id: 'spacer4' },
        { header: 'Total', id: 'total-label', cell: () => <strong>Total Hours</strong> },
        { header: '', id: 'total-value', cell: () => <strong>{totalHours.toFixed(1)}</strong> },
        ...(isAdmin ? [{ header: '', id: 'spacer-action' }] : []),
    ], [totalHours, isAdmin])

    const openAdd = () => {
        setEditing(null)
        reset({ year: '', sem_mode: '', department_id: String(user?.department_id || ''), subject_name: '', total_hours: '' })
        setShowModal(true)
    }

    const openEdit = (workload) => {
        setEditing(workload)
        reset({
            year: workload.year,
            sem_mode: workload.sem_mode,
            department_id: String(workload.department_id || ''),
            subject_name: workload.subject_name,
            total_hours: String(workload.total_hours),
        })
        setShowModal(true)
    }

    const onSubmit = (formData) => {
        const payload = { ...formData, total_hours: parseFloat(formData.total_hours) }
        const url = editing ? `/workload/${editing.id}` : '/workload'
        const method = editing ? 'put' : 'post'
        router[method](url, payload, {
            onSuccess: () => setShowModal(false),
        })
    }

    return (
        <AuthenticatedLayout>
            <Head title="Workload - Master Timetable" />
            <FlashAlert message={usePage().props.flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">Subject Workload</h5>
                        <div className="d-flex gap-2">
                            <div style={{ minWidth: 150 }}>
                                <Select2 placeholder="Filter by Year"
                                    value={filterYear} onChange={v => setFilterYear(v)}
                                    options={yearOptions} isClearable={true} />
                            </div>
                            {isAdmin && <Button onClick={openAdd}><i className="bi bi-plus-lg me-1"></i>Add</Button>}
                        </div>
                    </div>
                    <DataTable data={filteredWorkloads} columns={columns} searchable />
                    <hr />
                    <div className="d-flex justify-content-end px-3">
                        <Row style={{ width: '40%' }}>
                            {totalColumns.map(col => (
                                col.id.startsWith('spacer') ? <Col key={col.id}></Col> :
                                <Col key={col.id} className="text-end">{col.cell()}</Col>
                            ))}
                        </Row>
                    </div>
                </Card.Body>
            </Card>

            <Modal show={showModal} onHide={() => setShowModal(false)} centered>
                <Modal.Header closeButton>
                    <Modal.Title>{editing ? 'Edit' : 'Add'} Workload</Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleSubmit(onSubmit)}>
                    <Modal.Body>
                        <Row>
                            <Col md={4}>
                                <Select2Field name="year" label="Year" control={control} errors={errors}
                                    options={[
                                        { value: 'I', label: 'I' },
                                        { value: 'II', label: 'II' },
                                        { value: 'III', label: 'III' },
                                    ]} isClearable={false} />
                            </Col>
                            <Col md={4}>
                                <Select2Field name="sem_mode" label="Semester" control={control} errors={errors}
                                    options={semOptions} isClearable={false} />
                            </Col>
                            <Col md={4}>
                                {isAdmin ? (
                                    <Select2Field name="department_id" label="Department" control={control} errors={errors}
                                        options={deptOptions} isClearable={false} />
                                ) : (
                                    <input type="hidden" {...register('department_id')} />
                                )}
                            </Col>
                        </Row>
                        <Form.Group className="mb-3">
                            <Form.Label>Subject Name</Form.Label>
                            <Form.Control {...register('subject_name')} isInvalid={errors.subject_name} />
                            <Form.Control.Feedback type="invalid">{errors.subject_name?.message}</Form.Control.Feedback>
                        </Form.Group>
                        <Form.Group className="mb-3">
                            <Form.Label>Total Hours</Form.Label>
                            <Form.Control type="number" step="0.1" min="0" {...register('total_hours')} isInvalid={errors.total_hours} />
                            <Form.Control.Feedback type="invalid">{errors.total_hours?.message}</Form.Control.Feedback>
                        </Form.Group>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowModal(false)}>Cancel</Button>
                        <Button type="submit">{editing ? 'Update' : 'Save'}</Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    )
}
