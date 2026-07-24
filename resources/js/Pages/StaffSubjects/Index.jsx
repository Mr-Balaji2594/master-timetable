import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Modal, Form, Row, Col, Badge } from 'react-bootstrap'
import { useState, useMemo } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import Select2 from '../../Components/Select2'
import Select2Field from '../../Components/Select2Field'
import DataTable from '../../Components/DataTable'
import FormErrors from '../../Components/FormErrors'
import FlashAlert from '../../Components/FlashAlert'
import { showConfirm } from '../../Helpers/sweetAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const schema = z.object({
    employee_id: z.string().min(1, 'Employee is required'),
    subject_ids: z.array(z.number()).min(1, 'Select at least one subject')
})

export default function Index({ assignments, employees, subjects, departments }) {
    const { auth, flash } = usePage().props
    const user = auth?.user
    const [show, setShow] = useState(false)
    const [selectedEmp, setSelectedEmp] = useState(user?.role === 'staff' ? user.id : '')

    const isStaff = user?.role === 'staff'
    const canAssign = ['admin', 'super_admin', 'hod'].includes(user?.role)

    const visible = isStaff ? assignments.filter(s => s.employee_id === user.id) : assignments
    const filtered = selectedEmp ? visible.filter(s => s.employee_id == selectedEmp) : visible
    const empList = isStaff ? employees.filter(e => e.id === user.id) : employees

    const defaults = { employee_id: user?.role === 'staff' ? user.id : '', subject_ids: [] }
    const { control, handleSubmit, reset, watch, setValue, setError, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: defaults
    })
    const subject_ids = watch('subject_ids')
    const formEmpId = watch('employee_id')

    const assignedEmpSubjectIds = useMemo(() => {
        if (!formEmpId) return []
        return assignments
            .filter(a => a.employee_id == formEmpId)
            .map(a => a.subject_id)
    }, [formEmpId, assignments])

    const subjectsByDept = useMemo(() => {
        const map = {}
        subjects.forEach(s => {
            const deptName = s.department?.name || 'Unknown'
            if (!map[deptName]) map[deptName] = []
            map[deptName].push(s)
        })
        return map
    }, [subjects])

    const openAssign = (empId) => {
        reset({ ...defaults, employee_id: empId ?? '' })
        setShow(true)
    }

    const handleSubjToggle = (subjId) => {
        setValue('subject_ids',
            subject_ids.includes(subjId)
                ? subject_ids.filter(id => id !== subjId)
                : [...subject_ids, subjId],
            { shouldValidate: true }
        )
    }

    const submit = handleSubmit((formData) => {
        const done = () => { setShow(false); reset(defaults) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        router.post('/staff-subjects', formData, { onSuccess: done, onError })
    })

    const handleDelete = async (assignment) => {
        const result = await showConfirm('Remove Assignment?', `Remove ${assignment.subject?.name} from ${assignment.employee?.name}?`)
        if (result.isConfirmed) router.delete(`/staff-subjects/${assignment.id}`)
    }

    return (
        <AuthenticatedLayout>
            <Head title="Staff Subjects - Master Timetable" />
            <FlashAlert message={flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">Subject Assignments</h5>
                        {canAssign && <Button onClick={() => openAssign('')}><i className="bi bi-plus-lg me-1"></i>Assign Subjects</Button>}
                    </div>
                    {!isStaff && <Row className="mb-3"><Col md={3}>
                        <Select2 value={selectedEmp} onChange={v => setSelectedEmp(v)}
                            options={employees.map(e => ({ value: e.id, label: e.name }))} placeholder="All Employees" />
                    </Col></Row>}
                    <DataTable data={filtered} columns={[
                        { header: 'Employee', accessorKey: 'employee.name' },
                        { header: 'Department', accessorKey: 'employee.emp_id' },
                        { header: 'Subject', accessorKey: 'subject.name', cell: ({ row }) => `${row.original.subject?.name} (${row.original.subject?.code})` },
                        { header: 'Actions', id: 'actions', enableSorting: false, cell: ({ row }) => (
                            canAssign ? (
                                <>
                                    <Button size="sm" variant="outline-primary" className="me-1" onClick={() => openAssign(String(row.original.employee_id))}><i className="bi bi-pencil"></i></Button>
                                    <Button size="sm" variant="outline-danger" onClick={() => handleDelete(row.original)}><i className="bi bi-trash"></i></Button>
                                </>
                            ) : null
                        )},
                    ]} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)} size="lg">
                <Modal.Header closeButton><Modal.Title>Assign Subjects</Modal.Title></Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        {!isStaff && <Select2Field name="employee_id" label="Employee" control={control} errors={errors}
                            options={employees.map(e => ({ value: e.id, label: `${e.name} (${e.emp_id})` }))} isClearable={false} />}
                        <Form.Label>Subjects</Form.Label>
                        {errors.subject_ids && <div className="invalid-feedback d-block mb-1">{errors.subject_ids.message}</div>}
                        <div style={{ maxHeight: 400, overflowY: 'auto' }}>
                            {Object.entries(subjectsByDept).map(([deptName, deptSubjects]) => (
                                <div key={deptName} className="mb-3">
                                    <Badge bg="secondary" className="mb-2 px-3 py-2 fs-6 fw-semibold w-100 text-start" style={{ borderRadius: '6px', background: '#f1f5f9', color: '#475569', fontSize: '13px' }}>
                                        {deptName}
                                    </Badge>
                                    <div className="ps-2">
                                        {deptSubjects.map(s => {
                                            const alreadyAssigned = assignedEmpSubjectIds.includes(s.id)
                                            const isChecked = subject_ids.includes(s.id) || alreadyAssigned
                                            return (
                                                <Form.Check
                                                    key={s.id}
                                                    type="checkbox"
                                                    id={`subj-${s.id}`}
                                                    disabled={alreadyAssigned}
                                                    checked={isChecked}
                                                    onChange={() => { if (!alreadyAssigned) handleSubjToggle(s.id) }}
                                                    label={
                                                        <span className="d-flex align-items-center gap-2">
                                                            <span>{s.name} ({s.code})</span>
                                                            {alreadyAssigned && <Badge bg="light" text="dark" className="fw-normal" style={{ fontSize: '11px' }}>Assigned</Badge>}
                                                        </span>
                                                    }
                                                    className="mb-1"
                                                />
                                            )
                                        })}
                                    </div>
                                </div>
                            ))}
                        </div>
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
