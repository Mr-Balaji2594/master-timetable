import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Modal, Form, Row, Col } from 'react-bootstrap'
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

const schema = z.object({
    name: z.string().min(1, 'Name is required'),
    department_id: z.string().min(1, 'Department is required'),
    batch_year: z.string().optional(),
    year: z.string().optional()
})

export default function Index({ classes, departments }) {
    const { auth } = usePage().props
    const user = auth?.user
    const canManage = ['admin', 'super_admin', 'principal'].includes(user?.role)
    const [show, setShow] = useState(false)
    const [edit, setEdit] = useState(null)
    const [viewOnly, setViewOnly] = useState(false)
    const { control, handleSubmit, reset, setError, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: { name: '', department_id: '', batch_year: '', year: '' }
    })

    const openCreate = () => { reset({ name: '', department_id: '', batch_year: '', year: '' }); setEdit(null); setViewOnly(false); setShow(true) }
    const openEdit = (c) => { reset({ name: c.name, department_id: String(c.department_id ?? ''), batch_year: c.batch_year || '', year: c.year || '' }); setEdit(c); setViewOnly(false); setShow(true) }
    const openView = (c) => { reset({ name: c.name, department_id: String(c.department_id ?? ''), batch_year: c.batch_year || '', year: c.year || '' }); setEdit(c); setViewOnly(true); setShow(true) }
    const submit = handleSubmit((formData) => {
        const payload = { ...formData, batch_year: formData.batch_year || null, year: formData.year || null }
        const done = () => { setShow(false); setEdit(null); setViewOnly(false); reset({ name: '', department_id: '', batch_year: '', year: '' }) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        edit ? router.put(`/classes/${edit.id}`, payload, { onSuccess: done, onError })
            : router.post('/classes', payload, { onSuccess: done, onError })
    })
    const handleDelete = async (cls) => {
        const result = await showConfirm('Delete Class?', `Delete class ${cls.name}?`)
        if (result.isConfirmed) router.delete(`/classes/${cls.id}`)
    }

    const columns = useMemo(() => [
        { header: 'Name', accessorKey: 'name' },
        { header: 'Department', accessorKey: 'department.name' },
        { header: 'Batch Year', accessorKey: 'batch_year' },
        { header: 'Year', accessorKey: 'year' },
        { header: 'Actions', id: 'actions', enableSorting: false, cell: ({ row }) => (
            <>
                {canManage && <Button size="sm" variant="outline-primary" className="me-1" onClick={() => openEdit(row.original)}><i className="bi bi-pencil"></i></Button>}
                {!canManage && <Button size="sm" variant="outline-info" className="me-1" onClick={() => openView(row.original)}><i className="bi bi-eye"></i></Button>}
                {canManage && <Button size="sm" variant="outline-danger" onClick={() => handleDelete(row.original)}><i className="bi bi-trash"></i></Button>}
            </>
        )},
    ], [canManage])

    return (
        <AuthenticatedLayout>
            <Head title="Classes - Master Timetable" />
            <FlashAlert message={usePage().props.flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">All Classes</h5>
                        {canManage && <Button onClick={openCreate}><i className="bi bi-plus-lg me-1"></i>Add</Button>}
                    </div>
                    <DataTable data={classes} columns={columns} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton><Modal.Title>{viewOnly ? 'View Class' : edit ? 'Edit' : 'Create'} Class</Modal.Title></Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        <FormField name="name" label="Name" control={control} errors={errors} disabled={viewOnly} />
                        <Select2Field name="department_id" label="Department" control={control} errors={errors}
                            options={departments.map(d => ({ value: d.id, label: d.name }))} isClearable={false} isDisabled={viewOnly} />
                        <Row><Col md={6}><FormField name="batch_year" label="Batch Year" control={control} errors={errors} disabled={viewOnly} /></Col>
                            <Col md={6}><FormField name="year" label="Year" control={control} errors={errors} disabled={viewOnly} /></Col>
                        </Row>
                    </Modal.Body>
                    <Modal.Footer>
                        {viewOnly ? (
                            <Button variant="secondary" onClick={() => setShow(false)}>Close</Button>
                        ) : (
                            <>
                                <Button variant="secondary" onClick={() => setShow(false)}>Cancel</Button>
                                <Button type="submit" variant="primary">Save</Button>
                            </>
                        )}
                    </Modal.Footer>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    )
}
