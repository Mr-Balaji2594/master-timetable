import { Head, Link, usePage, router } from '@inertiajs/react'
import { Card, Button, Modal, Form, Row, Col } from 'react-bootstrap'
import { useState, useMemo } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import DataTable from '../../Components/DataTable'
import FormErrors from '../../Components/FormErrors'
import FormField from '../../Components/FormField'
import Select2Field from '../../Components/Select2Field'
import FlashAlert from '../../Components/FlashAlert'
import { showConfirm } from '../../Helpers/sweetAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const schema = z.object({
    name: z.string().min(1, 'Name is required'),
    code: z.string().min(1, 'Code is required'),
    hod_id: z.string().optional()
})

export default function Index({ departments, employees }) {
    const { auth } = usePage().props
    const user = auth?.user
    const [show, setShow] = useState(false)
    const [edit, setEdit] = useState(null)
    const defaults = { name: '', code: '', hod_id: '' }
    const { control, handleSubmit, reset, setError, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: defaults
    })

    const canManage = ['admin', 'super_admin', 'principal'].includes(user?.role)
    const filtered = user?.role === 'hod' ? departments.filter(d => d.id === user?.department_id) : departments
    const hodOptions = employees?.map(e => ({ value: String(e.id), label: e.name })) || []

    const openCreate = () => { reset(defaults); setEdit(null); setShow(true) }
    const openEdit = (d) => { reset({ name: d.name, code: d.code, hod_id: String(d.hod_id || '') }); setEdit(d); setShow(true) }
    const submit = handleSubmit((formData) => {
        const done = () => { setShow(false); setEdit(null); reset({ name: '', code: '' }) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        edit ? router.put(`/departments/${edit.id}`, formData, { onSuccess: done, onError })
            : router.post('/departments', formData, { onSuccess: done, onError })
    })
    const handleDelete = async (dept) => {
        const result = await showConfirm('Delete Department?', `Delete department ${dept.name}? This cannot be undone.`)
        if (result.isConfirmed) router.delete(`/departments/${dept.id}`)
    }

    const columns = useMemo(() => [
        { header: 'Name', accessorKey: 'name' },
        { header: 'Code', accessorKey: 'code' },
        { header: 'HOD', accessorKey: 'hod.name', cell: ({ getValue }) => getValue() || '-' },
        { header: 'Staff Count', accessorKey: 'employees_count', cell: ({ getValue }) => getValue() ?? 0 },
        { header: 'Actions', id: 'actions', enableSorting: false, cell: ({ row }) => (
            <>
                {canManage && <Button size="sm" variant="outline-primary" className="me-1" onClick={() => openEdit(row.original)}><i className="bi bi-pencil"></i></Button>}
                {canManage && <Button size="sm" variant="outline-danger" onClick={() => handleDelete(row.original)}><i className="bi bi-trash"></i></Button>}
            </>
        )},
    ], [canManage])

    return (
        <AuthenticatedLayout>
            <Head title="Departments - Master Timetable" />

            <FlashAlert message={usePage().props.flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">All Departments</h5>
                        {canManage && <Button onClick={openCreate}><i className="bi bi-plus-lg me-1"></i>Add</Button>}
                    </div>
                    <DataTable data={filtered} columns={columns} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton><Modal.Title>{edit ? 'Edit' : 'Create'} Department</Modal.Title></Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        <FormField name="name" label="Name" control={control} errors={errors} />
                        <FormField name="code" label="Code" control={control} errors={errors} />
                        <Select2Field name="hod_id" label="HOD" control={control} errors={errors}
                            options={hodOptions} placeholder="Select HOD" isClearable />
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
