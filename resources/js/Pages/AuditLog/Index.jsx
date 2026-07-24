import { Head, usePage } from '@inertiajs/react'
import { Card, Form, Row, Col } from 'react-bootstrap'
import { useState, useMemo } from 'react'
import Select2 from '../../Components/Select2'
import DataTable from '../../Components/DataTable'
import AuthenticatedLayout from '../../Layouts/Authenticated'

export default function Index({ logs }) {
    const [filters, setFilters] = useState({ action: '', date_from: '', date_to: '' })

    const filtered = logs.filter(l =>
        (!filters.action || l.action === filters.action) &&
        (!filters.date_from || l.created_at >= filters.date_from) &&
        (!filters.date_to || l.created_at <= filters.date_to)
    )

    return (
        <AuthenticatedLayout>
            <Head title="Audit Log - Master Timetable" />

            <Card>
                <Card.Body>
                    <h5 className="mb-3">Audit Log</h5>
                    <Row className="mb-3 g-2">
                        <Col md={4}><Select2 value={filters.action} onChange={v => setFilters(f => ({ ...f, action: v }))}
                            options={['created', 'updated', 'deleted', 'login', 'logout', 'password_reset', 'status_change'].map(a => ({ value: a, label: a.replace(/_/g, ' ') }))} placeholder="All Actions" /></Col>
                        <Col md={4}><Form.Control type="date" value={filters.date_from} onChange={e => setFilters(f => ({ ...f, date_from: e.target.value }))} placeholder="From" /></Col>
                        <Col md={4}><Form.Control type="date" value={filters.date_to} onChange={e => setFilters(f => ({ ...f, date_to: e.target.value }))} placeholder="To" /></Col>
                    </Row>
                    <DataTable data={filtered} columns={[
                        { header: 'Date/Time', accessorKey: 'created_at', cell: ({ getValue }) => <span style={{ whiteSpace: 'nowrap' }}>{getValue()}</span> },
                        { header: 'User', accessorKey: 'user.name', cell: ({ getValue }) => getValue() || 'System' },
                        { header: 'Action', accessorKey: 'action', cell: ({ getValue }) => <span className="text-capitalize">{getValue()?.replace(/_/g, ' ')}</span> },
                        { header: 'Details', accessorKey: 'details', cell: ({ getValue }) => <span style={{ maxWidth: 300, display: 'inline-block', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{getValue()}</span> },
                        { header: 'IP', accessorKey: 'ip_address', cell: ({ getValue }) => <span style={{ fontFamily: 'monospace', fontSize: 12 }}>{getValue()}</span> },
                    ]} pageSize={15} />
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    )
}
