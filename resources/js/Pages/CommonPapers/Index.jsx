import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Modal, Form, Badge } from 'react-bootstrap'
import { useState, useMemo } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import Select2Field from '../../Components/Select2Field'
import DataTable from '../../Components/DataTable'
import FormErrors from '../../Components/FormErrors'
import FlashAlert from '../../Components/FlashAlert'
import { showConfirm } from '../../Helpers/sweetAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const schema = z.object({
    subject_id: z.string().min(1, 'Subject is required'),
    class_ids: z.array(z.number()).min(1, 'Select at least one class'),
})

export default function Index({ commonSubjects, commonSubjectOptions, classes }) {
    const { auth } = usePage().props
    const user = auth?.user
    const canAllocate = ['admin', 'super_admin', 'principal'].includes(user?.role)
    const [show, setShow] = useState(false)
    const [edit, setEdit] = useState(null)

    const defaults = { subject_id: '', class_ids: [] }
    const { control, handleSubmit, reset, watch, setValue, setError, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: defaults
    })
    const class_ids = watch('class_ids')

    const openAllocate = () => { setEdit(null); reset(defaults); setShow(true) }
    const openEdit = (a) => {
        reset({
            subject_id: String(a.subject_id),
            class_ids: a.class_ids,
        })
        setEdit(a)
        setShow(true)
    }
    const handleClassToggle = (id) => {
        setValue('class_ids',
            class_ids.includes(id) ? class_ids.filter(c => c !== id) : [...class_ids, id],
            { shouldValidate: true }
        )
    }
    const submit = handleSubmit((formData) => {
        const done = () => { setShow(false); setEdit(null); reset(defaults) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        if (edit) {
            router.put(`/common-papers/allocate/${edit.slot_ids[0]}`, formData, { onSuccess: done, onError })
        } else {
            router.post('/common-papers/allocate', formData, { onSuccess: done, onError })
        }
    })
    const del = async (a) => {
        const result = await showConfirm('Delete Allocation?', 'Remove this common paper allocation?')
        if (result.isConfirmed) router.delete(`/common-papers/allocate/${a.slot_ids[0]}`)
    }

    const columns = useMemo(() => [
        { header: 'Subject', accessorKey: 'name' },
        { header: 'Code', accessorKey: 'code' },
        { header: 'Department', accessorKey: 'department.name' },
        {
            header: 'Status',
            id: 'status',
            cell: ({ row }) => row.original.is_allocated
                ? <Badge bg="success">Allocated</Badge>
                : <Badge bg="warning" text="dark">Not Allocated</Badge>,
        },
        {
            header: 'Allocations',
            id: 'allocations',
            enableSorting: false,
            cell: ({ row }) => {
                if (!row.original.is_allocated) return <span className="text-muted">—</span>
                return row.original.allocations?.map((a, i) => (
                    <div key={i} className="d-flex justify-content-between align-items-start mb-1 p-1 border-bottom">
                        <div>
                            <div className="text-muted" style={{ fontSize: '12px' }}>
                                {a.classes?.map(c => c.name).join(', ')}
                            </div>
                        </div>
                        {canAllocate && <div className="d-flex gap-1 flex-shrink-0">
                            <Button size="sm" variant="outline-primary" onClick={() => openEdit(a)}><i className="bi bi-pencil"></i></Button>
                            <Button size="sm" variant="outline-danger" onClick={() => del(a)}><i className="bi bi-trash"></i></Button>
                        </div>}
                    </div>
                ))
            },
        },
    ], [canAllocate])

    return (
        <AuthenticatedLayout>
            <Head title="Common Papers - Master Timetable" />
            <FlashAlert message={usePage().props.flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">Common Subjects</h5>
                        {canAllocate && <Button onClick={openAllocate}><i className="bi bi-plus-lg me-1"></i>Allocate</Button>}
                    </div>
                    <DataTable data={commonSubjects} columns={columns} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)} size="lg">
                <Modal.Header closeButton><Modal.Title>{edit ? 'Edit' : 'Allocate'} Common Paper</Modal.Title></Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        <Select2Field name="subject_id" label="Subject" control={control} errors={errors}
                            options={commonSubjectOptions?.map(s => ({ value: s.id, label: `${s.name} (${s.code})` }))} isClearable={false} />
                        <Form.Label>Select Classes</Form.Label>
                        {errors.class_ids && <div className="invalid-feedback d-block mb-1">{errors.class_ids.message}</div>}
                        <div style={{ maxHeight: 150, overflowY: 'auto' }} className="mb-2 border rounded p-2">
                            {classes.map(c => (
                                <Form.Check key={c.id} type="checkbox"
                                    label={`${c.name}-${c.department?.name}-${c.year || ''}`}
                                    checked={class_ids.includes(c.id)}
                                    onChange={() => handleClassToggle(c.id)} />
                            ))}
                        </div>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShow(false)}>Cancel</Button>
                        {edit && <Button variant="outline-danger" onClick={() => { del(edit); setShow(false) }}>Delete</Button>}
                        <Button type="submit" variant="primary">{edit ? 'Update' : 'Allocate'}</Button>
                    </Modal.Footer>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    )
}
