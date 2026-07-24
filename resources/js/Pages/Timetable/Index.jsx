import { Head, usePage, router } from '@inertiajs/react'
import { Card, Table, Button, Modal, Form, Row, Col } from 'react-bootstrap'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import Select2 from '../../Components/Select2'
import Select2Field from '../../Components/Select2Field'
import FormField from '../../Components/FormField'
import FormErrors from '../../Components/FormErrors'
import FlashAlert from '../../Components/FlashAlert'
import { showConfirm } from '../../Helpers/sweetAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'
import { pdf } from '@react-pdf/renderer'
import TimetablePDF from '../../Components/TimetablePDF'

const dayNames = ['I', 'II', 'III', 'IV', 'V', 'VI']

const schema = z.object({
    employee_id: z.string().min(1, 'Employee is required'),
    class_id: z.string().min(1, 'Class is required'),
    subject_id: z.string().min(1, 'Subject is required'),
    day_of_week: z.string().min(1, 'Day is required'),
    period_no: z.string().min(1, 'Period is required'),
})

export default function Index({ slots, employees, classes, subjects }) {
    const { auth, flash } = usePage().props
    const user = auth?.user
    const canManage = ['admin', 'super_admin', 'principal', 'hod'].includes(user?.role)
    const [show, setShow] = useState(false)
    const [edit, setEdit] = useState(null)
    const [filters, setFilters] = useState({ employee_id: employees?.length > 0 ? String(employees[0].id) : '', class_id: '', day_of_week: '' })

    const defaults = { employee_id: '', class_id: '', subject_id: '', day_of_week: '', period_no: '' }
    const { control, handleSubmit, reset, setError, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: defaults
    })

    const openCreate = (dow, pno) => {
        reset({ ...defaults, day_of_week: String(dow), period_no: String(pno) })
        setEdit(null)
        setShow(true)
    }
    const openEdit = (s) => {
        reset({
            employee_id: String(s.employee_id),
            class_id: String(s.class_id),
            subject_id: String(s.subject_id),
            day_of_week: String(s.day_of_week),
            period_no: String(s.period_no),
        })
        setEdit(s)
        setShow(true)
    }
    const submit = handleSubmit((formData) => {
        const payload = { ...formData, day_of_week: Number(formData.day_of_week), period_no: Number(formData.period_no) }
        const done = () => { setShow(false); setEdit(null); reset(defaults) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        router.post('/timetable', payload, { onSuccess: done, onError })
    })
    const del = async (id) => {
        const result = await showConfirm('Delete Slot?', 'This timetable slot will be removed.')
        if (result.isConfirmed) router.delete(`/timetable/${id}`)
    }

    const filtered = slots.filter(s =>
        (!filters.employee_id || s.employee_id == filters.employee_id) &&
        (!filters.class_id || s.class_id == filters.class_id) &&
        (!filters.day_of_week || s.day_of_week == filters.day_of_week)
    )
    const getSlot = (dow, pno) => filtered.find(s => s.day_of_week === dow && s.period_no === pno)

    const handlePrint = async () => {
        const emp = employees?.find(e => e.id == filters.employee_id)
        const cls = classes?.find(c => c.id == filters.class_id)
        const blob = await pdf(
            <TimetablePDF
                slots={filtered}
                employeeName={emp?.name}
                className={cls?.label}
            />
        ).toBlob()
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
    }

    return (
        <AuthenticatedLayout>
            <Head title="Timetable - Master Timetable" />
            <FlashAlert message={flash?.success} />
            <FlashAlert message={flash?.error} variant="danger" />

                            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3 no-print">
                        <h5 className="mb-0">Timetable</h5>
                        <Button variant="outline-secondary" size="sm" onClick={handlePrint}>
                            <i className="bi bi-printer me-1"></i>Print / PDF
                        </Button>
                    </div>
                    <Row className="mb-3 g-2 no-print">
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
                    </Row>
                    <div className="table-responsive print-area">
                        <style>{`
                            .timetable-grid td, .timetable-grid th {
                                vertical-align: middle;
                            }
                            .timetable-grid .day-header {
                                background: #f8f9fa;
                                font-weight: 600;
                                min-width: 60px;
                            }
                            .timetable-grid .slot-cell {
                                min-width: 140px;
                                height: 80px;
                                cursor: ${canManage ? 'pointer' : 'default'};
                                transition: background 0.15s;
                            }
                            .timetable-grid .slot-cell:hover {
                                background: ${canManage ? '#f0f7ff' : 'inherit'};
                            }
                            .timetable-grid .slot-cell .subject {
                                font-weight: 600;
                                font-size: 0.8125rem;
                                color: #0d6efd;
                            }
                            .timetable-grid .slot-cell .teacher {
                                font-size: 0.75rem;
                                color: #6c757d;
                            }
                            .timetable-grid .slot-cell .class-tag {
                                font-size: 0.6875rem;
                                color: #495057;
                                font-weight: 500;
                            }

                            .timetable-grid .slot-cell .add-placeholder {
                                font-size: 0.75rem;
                                color: #adb5bd;
                            }
                            .timetable-grid .period-header {
                                background: #e9ecef;
                                font-weight: 700;
                                font-size: 0.8125rem;
                                text-align: center;
                                min-width: 44px;
                            }
                        `}</style>
                        <Table bordered className="timetable-grid mb-0">
                            <thead>
                                <tr>
                                    <th className="day-header text-center">Day</th>
                                    {[1, 2, 3, 4, 5].map(p => (
                                        <th key={p} className="period-header text-center">P{p}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {dayNames.map((d, i) => {
                                    const dow = i + 1
                                    return (
                                        <tr key={dow}>
                                            <td className="day-header text-center">{d}</td>
                                            {[1, 2, 3, 4, 5].map(pno => {
                                                const s = getSlot(dow, pno)
                                                const handleClick = canManage ? () => s ? openEdit(s) : openCreate(dow, pno) : undefined
                                            return (
                                                <td key={pno} className="slot-cell text-center p-1"
                                                    onClick={handleClick}>
                                                    {s ? (
                                                        <>
                                                            <div className="subject">{s.subject?.name} ({s.subject?.code})</div>
                                                            <div className="class-tag">{s.class?.dept_code} - {s.class?.name} - {s.class?.year}</div>
                                                            <div className="teacher">{s.employee?.name}</div>
                                                        </>
                                                    ) : canManage ? (
                                                        <span className="add-placeholder">+ Add</span>
                                                    ) : null}
                                                </td>
                                            )
                                        })}
                                    </tr>
                                )})}
                            </tbody>
                        </Table>
                    </div>
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton><Modal.Title>{edit ? 'Edit' : 'Add'} Timetable Slot</Modal.Title></Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        <Row>
                            <Col md={6}><Select2Field name="day_of_week" label="Day" control={control} errors={errors}
                                options={dayNames.map((d, i) => ({ value: String(i + 1), label: d }))} isClearable={false} /></Col>
                            <Col md={6}><Select2Field name="period_no" label="Period" control={control} errors={errors}
                                options={[1, 2, 3, 4, 5].map(p => ({ value: String(p), label: String(p) }))} isClearable={false} /></Col>
                        </Row>
                        <Select2Field name="employee_id" label="Employee" control={control} errors={errors}
                            options={employees?.map(e => ({ value: e.id, label: e.name }))} isClearable={false} />
                        <Select2Field name="subject_id" label="Subject" control={control} errors={errors}
                            options={subjects?.map(s => ({ value: s.id, label: `${s.name} (${s.code})` }))} isClearable={false} />
                        <Select2Field name="class_id" label="Class" control={control} errors={errors}
                            options={classes?.map(c => ({ value: c.id, label: c.label }))} isClearable={false} />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShow(false)}>Cancel</Button>
                        {edit && canManage && <Button variant="outline-danger" onClick={() => { del(edit.id); setShow(false) }}>Delete</Button>}
                        <Button type="submit" variant="primary">Save</Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    )
}
