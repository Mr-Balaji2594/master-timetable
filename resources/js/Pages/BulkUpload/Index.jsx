import { Head, usePage, router } from '@inertiajs/react'
import { Card, Table, Button, Form, Row, Col } from 'react-bootstrap'
import { useState } from 'react'
import Select2 from '../../Components/Select2'
import FlashAlert from '../../Components/FlashAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const entities = ['departments', 'employees', 'classes', 'subjects']

export default function Index() {
    const [entity, setEntity] = useState('employees')
    const [file, setFile] = useState(null)
    const [preview, setPreview] = useState(null)
    const [loading, setLoading] = useState(false)

    const handleFile = async (e) => {
        const f = e.target.files[0]
        if (!f) return
        setFile(f)
        const text = await f.text()
        const lines = text.trim().split('\n').map(l => l.split('\t'))
        setPreview(lines.slice(0, 11))
    }

    const importData = () => {
        if (!file) return
        setLoading(true)
        const form = new FormData()
        form.append('file', file)
        form.append('entity', entity)
        router.post('/bulk-upload/import', form, {
            onFinish: () => { setLoading(false); setPreview(null); setFile(null) }
        })
    }

    return (
        <AuthenticatedLayout>
            <Head title="Bulk Upload - Master Timetable" />
            <FlashAlert message={usePage().props.flash?.success} />

            <Card>
                <Card.Body>
                    <h5 className="mb-3">Bulk Upload</h5>
                    <Row className="mb-3 g-2">
                        <Col md={3}>
                            <Select2 value={entity} onChange={v => { setEntity(v); setPreview(null); setFile(null) }}
                                options={entities.map(e => ({ value: e, label: e.charAt(0).toUpperCase() + e.slice(1) }))} isClearable={false} />
                        </Col>
                        <Col md={5}>
                            <Form.Control type="file" accept=".csv,.tsv,.txt" onChange={handleFile} />
                        </Col>
                        <Col md={2}>
                            <Button variant="secondary" onClick={importData} disabled={!file || loading}>
                                {loading ? 'Importing...' : 'Import'}
                            </Button>
                        </Col>
                    </Row>

                    {preview && (
                        <div className="table-responsive">
                            <Table striped hover size="sm">
                                <thead><tr>{preview[0]?.map((h, i) => <th key={i}>{h}</th>)}</tr></thead>
                                <tbody>
                                    {preview.slice(1).map((row, ri) => (
                                        <tr key={ri}>{row.map((c, ci) => <td key={ci}>{c}</td>)}</tr>
                                    ))}
                                </tbody>
                            </Table>
                            {preview.length > 11 && <p className="text-muted small">... and {preview.length - 11} more rows</p>}
                            <p className="text-muted small">Showing first 10 rows of data. Click Import to proceed.</p>
                        </div>
                    )}
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    )
}
