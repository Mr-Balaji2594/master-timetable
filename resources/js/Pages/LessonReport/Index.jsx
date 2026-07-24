import { Head, usePage } from '@inertiajs/react'
import { Card, Button, Form, Row, Col } from 'react-bootstrap'
import { useState, useMemo } from 'react'
import Select2 from '../../Components/Select2'
import DataTable from '../../Components/DataTable'
import FlashAlert from '../../Components/FlashAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

export default function Index({ reports, employees, classes, subjects }) {
    const { flash } = usePage().props
    const [filters, setFilters] = useState({ employee_id: '', class_id: '', subject_id: '', status: '', from_date: '', to_date: '' })

    const filtered = reports.filter(r =>
        (!filters.employee_id || r.employee_id == filters.employee_id) &&
        (!filters.class_id || r.class_id == filters.class_id) &&
        (!filters.subject_id || r.subject_id == filters.subject_id) &&
        (!filters.status || r.status === filters.status) &&
        (!filters.from_date || r.plan_date >= filters.from_date) &&
        (!filters.to_date || r.plan_date <= filters.to_date)
    )

    const exportCsv = () => {
        const headers = ['Employee', 'Emp ID', 'Class', 'Subject', 'Topic', 'Unit', 'Date', 'Status', 'HOD Approved At', 'Principal Approved At']
        const rows = filtered.map(r => [
            r.employee?.name, r.employee?.emp_id, r.class?.name, r.subject?.name,
            r.topic, r.unit, r.plan_date, r.status, r.hod_approved_at || '', r.principal_approved_at || ''
        ])
        const csv = [headers, ...rows].map(row => row.map(c => `"${c || ''}"`).join(',')).join('\n')
        const blob = new Blob([csv], { type: 'text/csv' })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a'); a.href = url; a.download = 'lesson_reports.csv'; a.click()
        URL.revokeObjectURL(url)
    }

    return (
        <AuthenticatedLayout>
            <Head title="Lesson Reports - Master Timetable" />
            <FlashAlert message={flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">Lesson Reports</h5>
                        <Button onClick={exportCsv}><i className="bi bi-download me-1"></i>Export CSV</Button>
                    </div>
                    <Row className="mb-3 g-2">
                        <Col md={2}><Select2 value={filters.employee_id} onChange={v => setFilters(f => ({ ...f, employee_id: v }))}
                            options={employees?.map(e => ({ value: e.id, label: e.name }))} placeholder="All Employees" /></Col>
                        <Col md={2}><Select2 value={filters.class_id} onChange={v => setFilters(f => ({ ...f, class_id: v }))}
                            options={classes?.map(c => ({ value: c.id, label: c.name }))} placeholder="All Classes" /></Col>
                        <Col md={2}><Select2 value={filters.subject_id} onChange={v => setFilters(f => ({ ...f, subject_id: v }))}
                            options={subjects?.map(s => ({ value: s.id, label: s.name }))} placeholder="All Subjects" /></Col>
                        <Col md={2}><Select2 value={filters.status} onChange={v => setFilters(f => ({ ...f, status: v }))}
                            options={[
                                { value: 'pending_hod', label: 'Pending HOD' },
                                { value: 'pending_principal', label: 'Pending Principal' },
                                { value: 'approved', label: 'Approved' },
                                { value: 'rejected', label: 'Rejected' },
                            ]} placeholder="All Status" /></Col>
                        <Col md={2}><Form.Control type="date" placeholder="From" value={filters.from_date} onChange={e => setFilters(f => ({ ...f, from_date: e.target.value }))} /></Col>
                        <Col md={2}><Form.Control type="date" placeholder="To" value={filters.to_date} onChange={e => setFilters(f => ({ ...f, to_date: e.target.value }))} /></Col>
                    </Row>
                    <DataTable data={filtered} columns={[
                        { header: 'Employee', accessorKey: 'employee.name' },
                        { header: 'Class', accessorKey: 'class.name' },
                        { header: 'Subject', accessorKey: 'subject.name' },
                        { header: 'Topic', accessorKey: 'topic' },
                        { header: 'Unit', accessorKey: 'unit' },
                        { header: 'Date', accessorKey: 'plan_date' },
                        { header: 'Status', accessorKey: 'status' },
                    ]} searchable />
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    )
}
