import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Form, Row, Col, Alert, ProgressBar, Badge } from 'react-bootstrap'
import { useState } from 'react'
import DataTable from '../../Components/DataTable'
import FlashAlert from '../../Components/FlashAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

export default function Index({ employees, recentCredits, elThreshold }) {
    const { flash } = usePage().props
    const [file, setFile] = useState(null)
    const [preview, setPreview] = useState(null)
    const [loading, setLoading] = useState(false)

    const handleFile = async (e) => {
        const f = e.target.files[0]
        if (!f) return
        setFile(f)
        setPreview(null)

        const text = await f.text()
        const name = f.name.toLowerCase()
        const delimiter = name.endsWith('.tsv') ? '\t' : name.endsWith('.csv') ? ',' : null
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
        setPreview(rows.slice(0, 11))
    }

    const downloadTemplate = () => {
        const content = 'emp_id,attendance_date,check_in,check_out\nEMP001,2026-08-03,09:00,17:30\nEMP001,2026-08-04,09:05,17:15\n'
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = 'biometric_attendance_template.csv'
        a.click()
        URL.revokeObjectURL(url)
    }

    const importData = () => {
        if (!file) return
        setLoading(true)
        const form = new FormData()
        form.append('file', file)
        router.post('/earned-leave/import', form, {
            onFinish: () => { setLoading(false); setFile(null); setPreview(null) }
        })
    }

    const recalculate = () => {
        router.post('/earned-leave/recalculate', {}, { preserveState: true })
    }

    const previewData = preview && preview.length > 1
        ? preview.slice(1).map((row, ri) => {
            const headers = preview[0]
            const obj = { _id: ri }
            headers.forEach((h, ci) => { obj[`col${ci}`] = row[ci] ?? '' })
            return obj
        })
        : []
    const previewColumns = preview
        ? (preview[0] || []).map((h, ci) => ({ header: h || `Column ${ci + 1}`, accessorKey: `col${ci}` }))
        : []

    const streakPct = (emp) => {
        const s = emp.current_streak || 0
        return Math.min(100, (s / elThreshold) * 100)
    }

    return (
        <AuthenticatedLayout>
            <Head title="Earned Leave - Master Timetable" />
            <FlashAlert message={flash?.success} />
            <FlashAlert message={flash?.error} variant="danger" />

            <Card className="mb-3">
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">Earned Leave (EL)</h5>
                        <Button variant="outline-secondary" onClick={recalculate}>
                            <i className="bi bi-arrow-repeat me-1"></i>Recalculate EL
                        </Button>
                    </div>
                    <Alert variant="info" className="py-2 mb-3">
                        Staff and HOD earn <strong>1 Earned Leave</strong> for every{' '}
                        <strong>{elThreshold} consecutive working days</strong> (Mon-Sat biometric punch) without taking
                        approved Casual or Medical leave. Approved CL/ML or a missed punch resets the streak. Sundays are skipped.
                    </Alert>
                    <Row className="g-2 align-items-center">
                        <Col md={6}>
                            <Form.Control type="file" accept=".csv,.tsv,.txt" onChange={handleFile} />
                        </Col>
                        <Col md={6} className="d-flex gap-2">
                            <Button variant="outline-primary" onClick={downloadTemplate}>
                                <i className="bi bi-download me-1"></i>Template
                            </Button>
                            <Button variant="secondary" onClick={importData} disabled={!file || loading}>
                                {loading ? 'Importing...' : 'Import Biometric Data'}
                            </Button>
                        </Col>
                    </Row>
                    <small className="text-muted d-block mt-2">
                        Columns: emp_id, attendance_date, check_in, check_out. Dates can be YYYY-MM-DD or DD/MM/YYYY.
                    </small>

                    {preview && (
                        <>
                            <hr />
                            <h6>Preview (first 10 rows)</h6>
                            <DataTable
                                data={previewData}
                                columns={previewColumns}
                                searchable={false}
                                enableExport={false}
                                enableColumnToggle={false}
                                enableReset={false}
                                enableColumnFilters={false}
                                emptyMessage="No rows to preview"
                            />
                            {preview.length > 11 && <p className="text-muted small mt-2">... and {preview.length - 11} more rows</p>}
                        </>
                    )}
                </Card.Body>
            </Card>

            <Card>
                <Card.Body>
                    <h5 className="mb-3">EL Progress & Balances</h5>
                    <DataTable
                        data={employees}
                        searchable
                        columns={[
                            { header: 'Employee', accessorKey: 'name', cell: ({ row }) => `${row.original.name} (${row.original.emp_id})` },
                            { header: 'Department', accessorKey: 'dept_name', cell: ({ getValue }) => getValue() || '-' },
                            { header: 'Attendance Days', accessorKey: 'attendance_days' },
                            {
                                header: 'Current Streak', id: 'streak', enableSorting: false,
                                cell: ({ row }) => {
                                    const s = row.original.current_streak || 0
                                    const done = s >= elThreshold
                                    return (
                                        <div>
                                            <div className="d-flex justify-content-between small">
                                                <span>{s} / {elThreshold} days</span>
                                                {done && <Badge bg="success">Ready</Badge>}
                                            </div>
                                            <ProgressBar now={streakPct(row.original)} style={{ height: 6 }}
                                                variant={done ? 'success' : s >= 15 ? 'warning' : 'info'} />
                                        </div>
                                    )
                                },
                            },
                            { header: 'EL Earned', accessorKey: 'earned_leave_limit' },
                            { header: 'EL Availed', accessorKey: 'earned_leave_availed' },
                            { header: 'Available', accessorKey: 'available' },
                        ]}
                    />
                </Card.Body>
            </Card>

            <Card className="mt-3">
                <Card.Body>
                    <h5 className="mb-3">Recent EL Credits</h5>
                    <DataTable
                        data={recentCredits}
                        searchable={false}
                        enableExport={false}
                        enableColumnToggle={false}
                        enableReset={false}
                        enableColumnFilters={false}
                        columns={[
                            { header: 'Employee', accessorKey: 'employee.name', cell: ({ row }) => row.original.employee ? `${row.original.employee.name} (${row.original.employee.emp_id})` : '-' },
                            { header: 'Credit Date', accessorKey: 'credit_date' },
                            { header: 'Consecutive Days', accessorKey: 'consecutive_days' },
                        ]}
                        emptyMessage="No EL credits yet"
                    />
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    )
}
