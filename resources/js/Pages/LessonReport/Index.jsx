import { Head, usePage } from '@inertiajs/react'
import { Card, Button, Form, Row, Col } from 'react-bootstrap'
import { useState, useMemo } from 'react'
import Select2 from '../../Components/Select2'
import DataTable from '../../Components/DataTable'
import FlashAlert from '../../Components/FlashAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

export default function Index({ reports, employees, classes, subjects }) {
    const { auth, flash } = usePage().props
    const isStaff = auth?.user?.role === 'staff'
    const [filters, setFilters] = useState({ employee_id: '', class_id: '', subject_id: '', from_date: '', to_date: '' })

    const filtered = reports.filter(r =>
        (!filters.employee_id || r.employee_id == filters.employee_id) &&
        (!filters.class_id || r.class_id == filters.class_id) &&
        (!filters.subject_id || r.subject_id == filters.subject_id) &&
        (!filters.from_date || r.plan_date >= filters.from_date) &&
        (!filters.to_date || r.plan_date <= filters.to_date)
    )

    const handleDateChange = (key) => (e) => {
        let val = e.target.value
        if (val) {
            const parts = val.split('-')
            if (parts[0] && parts[0].length > 4) {
                parts[0] = parts[0].slice(0, 4)
                val = parts.join('-')
                e.target.value = val
            }
        }
        setFilters(f => ({ ...f, [key]: val }))
    }

    const exportCsv = () => {
        const params = new URLSearchParams()
        Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v) })
        window.location.href = `/lesson-reports/export?${params.toString()}`
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
                        <Col md={isStaff ? 4 : 2}><Select2 value={filters.employee_id} onChange={v => setFilters(f => ({ ...f, employee_id: v }))}
                            options={employees?.map(e => ({ value: e.id, label: e.name }))} placeholder="All Employees" /></Col>
                        {!isStaff && (
                            <>
                                <Col md={2}><Select2 value={filters.class_id} onChange={v => setFilters(f => ({ ...f, class_id: v }))}
                                    options={classes?.map(c => ({ value: c.id, label: c.name }))} placeholder="All Classes" /></Col>
                                <Col md={2}><Select2 value={filters.subject_id} onChange={v => setFilters(f => ({ ...f, subject_id: v }))}
                                    options={subjects?.map(s => ({ value: s.id, label: s.name }))} placeholder="All Subjects" /></Col>
                            </>
                        )}
                        <Col md={2}><Form.Control type="date" placeholder="From" value={filters.from_date} onChange={handleDateChange('from_date')} max="9999-12-31" /></Col>
                        <Col md={2}><Form.Control type="date" placeholder="To" value={filters.to_date} onChange={handleDateChange('to_date')} max="9999-12-31" /></Col>
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
