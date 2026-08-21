import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Modal, Form, Row, Col, ProgressBar } from 'react-bootstrap'
import { useState, useMemo } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import FormField from '../../Components/FormField'
import DataTable from '../../Components/DataTable'
import FormErrors from '../../Components/FormErrors'
import FlashAlert from '../../Components/FlashAlert'
import { showConfirm } from '../../Helpers/sweetAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const leaveTypes = [
    { key: 'casual', label: 'Casual', limitKey: 'casual_leave_limit', availedKey: 'casual_leave_availed' },
    { key: 'medical', label: 'Medical', limitKey: 'medical_leave_limit', availedKey: 'medical_leave_availed' },
    { key: 'onduty', label: 'On Duty', limitKey: 'onduty_leave_limit', availedKey: 'onduty_leave_availed' },
    { key: 'early_permission', label: 'Early Permission', limitKey: 'early_permission_limit', availedKey: 'early_permission_availed' },
    { key: 'late_permission', label: 'Late Permission', limitKey: 'late_permission_limit', availedKey: 'late_permission_availed' },
    { key: 'deputation', label: 'Deputation', limitKey: 'deputation_limit', availedKey: 'deputation_availed' },
    { key: 'earned', label: 'Earned (EL)', limitKey: 'earned_leave_limit', availedKey: 'earned_leave_availed' },
]

const leaveDefaults = Object.fromEntries(leaveTypes.flatMap(lt => [[lt.limitKey, ''], [lt.availedKey, '']]))
const leaveSchema = z.object(Object.fromEntries(leaveTypes.flatMap(lt => [
    [lt.limitKey, z.union([z.string(), z.number()]).optional()],
    [lt.availedKey, z.union([z.string(), z.number()]).optional()],
])))

export default function Index({ employees }) {
    const { flash } = usePage().props
    const [show, setShow] = useState(false)
    const [edit, setEdit] = useState(null)

    const { control, handleSubmit, reset, setError, formState: { errors } } = useForm({
        resolver: zodResolver(leaveSchema), defaultValues: leaveDefaults
    })

    const openEdit = (emp) => { reset(emp); setEdit(emp); setShow(true) }
    const submit = handleSubmit((formData) => {
        const done = () => { setShow(false); setEdit(null); reset(leaveDefaults) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        router.put(`/leave-balance/${edit.id}`, formData, { onSuccess: done, onError, preserveState: true })
    })
    const resetAll = async () => {
        const result = await showConfirm('Reset All Balances?', 'All leave balances will be reset for the new academic year.')
        if (result.isConfirmed) router.post('/leave-balance/reset', {}, { preserveState: true })
    }

    return (
        <AuthenticatedLayout>
            <Head title="Leave Balance - Master Timetable" />
            <FlashAlert message={flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">Leave Balances</h5>
                        <Button variant="outline-danger" onClick={resetAll}><i className="bi bi-arrow-counterclockwise me-1"></i>Reset All</Button>
                    </div>
                    <DataTable data={employees} columns={[
                        { header: 'Employee', accessorKey: 'name', cell: ({ row }) => `${row.original.name} (${row.original.emp_id})` },
                        ...leaveTypes.map(lt => ({
                            header: lt.label,
                            id: lt.key,
                            enableSorting: false,
                            cell: ({ row }) => {
                                const limit = row.original[lt.limitKey] || 0
                                const availed = row.original[lt.availedKey] || 0
                                const pct = limit > 0 ? (availed / limit) * 100 : 0
                                return (
                                    <div className="text-center">
                                        <small>{availed} / {limit}</small>
                                        <ProgressBar now={pct} style={{ height: 4 }}
                                            variant={pct > 80 ? 'danger' : pct > 60 ? 'warning' : 'success'} />
                                    </div>
                                )
                            },
                        })),
                        { header: 'Actions', id: 'actions', enableSorting: false, cell: ({ row }) => (
                            <Button size="sm" variant="outline-primary" onClick={() => openEdit(row.original)}><i className="bi bi-pencil-square"></i></Button>
                        )},
                    ]} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton><Modal.Title>Edit Leave Balance - {edit?.name}</Modal.Title></Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        {leaveTypes.map(lt => (
                            <Row key={lt.key} className="mb-2">
                                <Col><FormField name={lt.limitKey} label={`${lt.label} Limit`} type="number" control={control} errors={errors} /></Col>
                                <Col><FormField name={lt.availedKey} label="Availed" type="number" control={control} errors={errors} /></Col>
                            </Row>
                        ))}
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
