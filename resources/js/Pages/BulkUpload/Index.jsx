import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Form, Row, Col, Alert, Badge } from 'react-bootstrap'
import { useState } from 'react'
import Select2 from '../../Components/Select2'
import DataTable from '../../Components/DataTable'
import FlashAlert from '../../Components/FlashAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const entities = ['departments', 'employees', 'classes', 'subjects', 'timetable']

const defaultTemplates = {
    departments: ['name', 'code', 'branch_code', 'staff_count'],
    employees: ['emp_id', 'name', 'department', 'designation', 'mode', 'role', 'email', 'phone', 'password', 'is_active'],
    classes: ['name', 'department', 'program_type', 'batch_year', 'year', 'block', 'floor'],
    subjects: ['name', 'code', 'department', 'credits', 'lecture_hours_per_week', 'year', 'sem', 'sem_mode', 'is_common'],
    timetable: ['class', 'subject', 'employee', 'day_of_week', 'period_no', 'semester', 'room_no'],
}

const examples = {
    departments: 'CSE, CSE, 01, 20',
    employees: 'EMP001, John Doe, CSE, Assistant Professor, permanent, staff, john@college.edu, 9876543210, password123, 1',
    classes: 'CSE-A, CSE, UG, 2022-2025, III, Main, Ground',
    subjects: 'Data Structures, CS201, CSE, 4, 4, III, V, annual, 0',
    timetable: 'CSE-A, CS201, EMP001, Monday, 1, V, A-101',
}

export default function Index({ entities: serverEntities, templates: serverTemplates }) {
    const { flash } = usePage().props
    const entityList = serverEntities?.length ? serverEntities : entities
    const templateMap = serverTemplates ?? defaultTemplates
    const [entity, setEntity] = useState(entityList[0] || 'employees')
    const [file, setFile] = useState(null)
    const [preview, setPreview] = useState(null)
    const [previewHeaders, setPreviewHeaders] = useState([])
    const [skipDupes, setSkipDupes] = useState(true)
    const [loading, setLoading] = useState(false)

    const detectDelimiter = (f) => {
        const name = f.name.toLowerCase()
        if (name.endsWith('.tsv')) return '\t'
        if (name.endsWith('.csv')) return ','
        return null
    }

    const handleFile = async (e) => {
        const f = e.target.files[0]
        if (!f) return
        setFile(f)
        setPreview(null)
        setPreviewHeaders([])

        const text = await f.text()
        const delimiter = detectDelimiter(f)
        const lines = text.trim().split('\n')
        const firstLine = lines[0] || ''

        let rows
        if (delimiter) {
            rows = lines.map(l => l.split(delimiter))
        } else {
            const tabs = (firstLine.match(/\t/g) || []).length
            const commas = (firstLine.match(/,/g) || []).length
            rows = lines.map(l => (tabs > commas ? l.split('\t') : l.split(',')))
        }

        setPreviewHeaders(rows[0] || [])
        setPreview(rows.slice(0, 11))
    }

    const downloadTemplate = () => {
        const headers = templateMap[entity] || defaultTemplates[entity] || []
        const example = examples[entity] || ''
        const content = `${headers.join(',')}\n${example}\n`
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `${entity}_template.csv`
        a.click()
        URL.revokeObjectURL(url)
    }

    const importData = () => {
        if (!file) return
        setLoading(true)
        const form = new FormData()
        form.append('file', file)
        form.append('type', entity)
        form.append('skip_duplicates', skipDupes ? '1' : '0')
        router.post('/bulk-upload/import', form, {
            onFinish: () => { setLoading(false); setPreview(null); setFile(null); setPreviewHeaders([]) }
        })
    }

    const requiredHeaders = templateMap[entity] || defaultTemplates[entity] || []

    return (
        <AuthenticatedLayout>
            <Head title="Bulk Upload - Master Timetable" />
            <FlashAlert message={flash?.success} />
            <FlashAlert message={flash?.error} variant="danger" />

            <Card>
                <Card.Body>
                    <h5 className="mb-3">Bulk Upload</h5>
                    <Row className="mb-3 g-2 align-items-center">
                        <Col md={3}>
                            <Select2 value={entity} onChange={v => { setEntity(v); setPreview(null); setFile(null); setPreviewHeaders([]) }}
                                options={entityList.map(e => ({ value: e, label: e.charAt(0).toUpperCase() + e.slice(1) }))} isClearable={false} />
                        </Col>
                        <Col md={5}>
                            <Form.Control type="file" accept=".csv,.tsv,.txt" onChange={handleFile} />
                        </Col>
                        <Col md={4} className="d-flex gap-2 flex-wrap">
                            <Button variant="outline-primary" onClick={downloadTemplate}>
                                <i className="bi bi-download me-1"></i>Template
                            </Button>
                            <Button variant="secondary" onClick={importData} disabled={!file || loading}>
                                {loading ? 'Importing...' : 'Import'}
                            </Button>
                        </Col>
                    </Row>

                    <Form.Check
                        type="switch"
                        id="skip_duplicates"
                        label="Skip existing records (emp_id / code / name based)"
                        checked={skipDupes}
                        onChange={e => setSkipDupes(e.target.checked)}
                        className="mb-2"
                    />

                    <Alert variant="info" className="py-2 mb-0">
                        <div className="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <strong>Required columns:</strong>{' '}
                                {requiredHeaders.map(h => <Badge bg="light" text="dark" className="me-1 fw-normal">{h}</Badge>)}
                            </div>
                            <small className="text-muted">departments can be by name or code; timetable day accepts 1-6 or Mon-Sat</small>
                        </div>
                    </Alert>

                    {preview && (() => {
                        const headers = preview[0] || []
                        const previewRows = preview.slice(1)
                        const previewColumns = headers.map((h, ci) => ({
                            header: h || `Column ${ci + 1}`,
                            accessorKey: `col${ci}`,
                            cell: ({ getValue }) => getValue() ?? '-',
                        }))
                        const previewData = previewRows.map((row, ri) => {
                            const obj = {}
                            headers.forEach((_, ci) => { obj[`col${ci}`] = row[ci] ?? '' })
                            obj._id = ri
                            return obj
                        })
                        return (
                            <>
                                <DataTable
                                    data={previewData}
                                    columns={previewColumns}
                                    searchable
                                    enableExport={false}
                                    enableColumnToggle={false}
                                    enableReset={false}
                                    enableColumnFilters={false}
                                    emptyMessage="No rows to preview"
                                />
                                {preview.length > 11 && <p className="text-muted small mt-2">... and {preview.length - 11} more rows</p>}
                                <p className="text-muted small">Showing first 10 rows of data. Click Import to proceed.</p>
                            </>
                        )
                    })()}
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    )
}
