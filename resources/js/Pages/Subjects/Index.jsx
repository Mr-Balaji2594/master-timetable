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

const schema = z.object({
    name: z.string().min(1, 'Name is required'),
    code: z.string().min(1, 'Code is required'),
    department_id: z.string().min(1, 'Department is required'),
    credits: z.string().optional(),
    lecture_hours_per_week: z.string().optional(),
    year: z.string().optional(),
    sem: z.string().optional(),
    sem_mode: z.string().optional(),
    is_common: z.boolean().optional(),
})

export default function Index({ subjects, departments }) {
    const { auth } = usePage().props
    const user = auth?.user
    const canManage = ['admin', 'super_admin', 'principal', 'hod'].includes(user?.role)
    const canDelete = ['admin', 'super_admin', 'principal'].includes(user?.role)
    const isHod = user?.role === 'hod'
    const deptSubjects = isHod ? subjects.filter(s => s.department_id === user?.dept_id) : subjects
    const [show, setShow] = useState(false)
    const [edit, setEdit] = useState(null)
    const [viewOnly, setViewOnly] = useState(false)
    const defaults = { name: '', code: '', department_id: '', credits: '', lecture_hours_per_week: '', year: '', sem: '', sem_mode: '', is_common: false }
    const { control, handleSubmit, reset, watch, setValue, setError, formState: { errors } } = useForm({
        resolver: zodResolver(schema), defaultValues: defaults
    })

    const toFormData = (s) => ({
        name: s.name ?? '',
        code: s.code ?? '',
        department_id: String(s.department_id ?? ''),
        credits: s.credits != null ? String(s.credits) : '',
        lecture_hours_per_week: s.lecture_hours_per_week != null ? String(s.lecture_hours_per_week) : '',
        year: s.year ?? '',
        sem: s.sem ?? '',
        sem_mode: s.sem_mode ?? '',
        is_common: s.is_common ?? false,
    })
    const openCreate = () => { reset({ ...defaults, department_id: isHod ? String(user?.dept_id ?? '') : '' }); setEdit(null); setViewOnly(false); setShow(true) }
    const openEdit = (s) => { reset(toFormData(s)); setEdit(s); setViewOnly(false); setShow(true) }
    const openView = (s) => { reset(toFormData(s)); setEdit(s); setViewOnly(true); setShow(true) }
    const submit = handleSubmit((formData) => {
        const done = () => { setShow(false); setEdit(null); setViewOnly(false); reset(defaults) }
        const onError = (serverErrors) => Object.entries(serverErrors).forEach(([k, msgs]) => setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }))
        edit ? router.put(`/subjects/${edit.id}`, formData, { onSuccess: done, onError })
            : router.post('/subjects', formData, { onSuccess: done, onError })
    })
    const handleDelete = async (subj) => {
        const result = await showConfirm('Delete Subject?', `Delete subject ${subj.name}?`)
        if (result.isConfirmed) router.delete(`/subjects/${subj.id}`)
    }

    const columns = useMemo(() => [
        { header: 'Name', accessorKey: 'name' },
        { header: 'Code', accessorKey: 'code' },
        { header: 'Department', accessorKey: 'department.name' },
        { header: 'Credits', accessorKey: 'credits' },
        { header: 'Hrs/Wk', accessorKey: 'lecture_hours_per_week' },
        { header: 'Year', accessorKey: 'year' },
        { header: 'Sem', accessorKey: 'sem' },
        { header: 'Common', id: 'is_common', cell: ({ row }) => row.original.is_common ? <Badge bg="info">Common</Badge> : null },
        { header: 'Actions', id: 'actions', enableSorting: false, cell: ({ row }) => (
            <>
                {canManage && <Button size="sm" variant="outline-primary" className="me-1" onClick={() => openEdit(row.original)}><i className="bi bi-pencil"></i></Button>}
                {!canManage && <Button size="sm" variant="outline-info" className="me-1" onClick={() => openView(row.original)}><i className="bi bi-eye"></i></Button>}
                {canDelete && <Button size="sm" variant="outline-danger" onClick={() => handleDelete(row.original)}><i className="bi bi-trash"></i></Button>}
            </>
        )},
    ], [canManage, canDelete, isHod])

    return (
        <AuthenticatedLayout>
            <Head title="Subjects - Master Timetable" />
            <FlashAlert message={usePage().props.flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">All Subjects</h5>
                        {canManage && <Button onClick={openCreate}><i className="bi bi-plus-lg me-1"></i>Add</Button>}
                    </div>
                    <DataTable data={deptSubjects} columns={columns} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)} size="lg">
                <Modal.Header closeButton><Modal.Title>{viewOnly ? 'View Subject' : edit ? 'Edit' : 'Create'} Subject</Modal.Title></Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        <Row><Col md={6}><FormField name="name" label="Name" control={control} errors={errors} disabled={viewOnly} /></Col>
                            <Col md={6}><FormField name="code" label="Code" control={control} errors={errors} disabled={viewOnly} /></Col>
                        </Row>
                        <Select2Field name="department_id" label="Department" control={control} errors={errors}
                            options={departments.map(d => ({ value: d.id, label: d.name }))} isClearable={false} isDisabled={viewOnly || isHod} />
                        <Row><Col md={3}><FormField name="credits" label="Credits" type="number" control={control} errors={errors} disabled={viewOnly} /></Col>
                            <Col md={3}><FormField name="lecture_hours_per_week" label="Hours/Week" type="number" control={control} errors={errors} disabled={viewOnly} /></Col>
                            <Col md={3}><Select2Field name="year" label="Year" control={control} errors={errors}
                                options={[{ value: 'I', label: 'I' }, { value: 'II', label: 'II' }, { value: 'III', label: 'III' }]} isClearable isDisabled={viewOnly} /></Col>
                            <Col md={3}><Select2Field name="sem" label="Semester" control={control} errors={errors}
                                options={[{ value: 'I', label: 'I' }, { value: 'II', label: 'II' }, { value: 'III', label: 'III' }, { value: 'IV', label: 'IV' }, { value: 'V', label: 'V' }, { value: 'VI', label: 'VI' }]} isClearable isDisabled={viewOnly} /></Col>
                        </Row>
                        <Form.Check type="switch" id="is_common" label="Common Paper (shows in Common Papers page)"
                            checked={watch('is_common')} onChange={e => setValue('is_common', e.target.checked)} disabled={viewOnly} />
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
