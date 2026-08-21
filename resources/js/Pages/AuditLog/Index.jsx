import { Head, usePage } from '@inertiajs/react'
import { Card, Form, Row, Col, Modal, Button, Badge } from 'react-bootstrap'
import { useState } from 'react'
import Select2 from '../../Components/Select2'
import DataTable from '../../Components/DataTable'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const actionOptions = [
    'created', 'updated', 'deleted', 'login', 'logout', 'password_reset', 'status_change',
    'lesson_plan_create', 'lesson_plan_update', 'lesson_plan_delete',
    'lesson_plan_hod_approve', 'lesson_plan_principal_approve', 'lesson_plan_reject',
    'lesson_plan_bulk_hod_approve', 'lesson_plan_bulk_principal_approve', 'lesson_plan_bulk_reject',
    'leave_balance_update', 'leave_balance_reset', 'login_attempts_reset',
    'lesson_report_export', 'export', 'employee_create', 'employee_update', 'employee_delete',
    'employee_status_change', 'employee_password_reset',
]

export default function Index({ logs }) {
    const { flash } = usePage().props
    const [filters, setFilters] = useState({ emp_id: '', action: '', date_from: '', date_to: '' })
    const [detail, setDetail] = useState(null)

    const filtered = logs.filter(l =>
        (!filters.emp_id || (l.emp_id || '').toLowerCase().includes(filters.emp_id.toLowerCase()) || (l.user?.name || '').toLowerCase().includes(filters.emp_id.toLowerCase())) &&
        (!filters.action || l.action === filters.action) &&
        (!filters.date_from || l.created_at >= filters.date_from) &&
        (!filters.date_to || l.created_at <= filters.date_to)
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

    const actionLabel = (a) => a?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) || '-'

    return (
        <AuthenticatedLayout>
            <Head title="Audit Log - Master Timetable" />

            <Card>
                <Card.Body>
                    <h5 className="mb-3">Audit Log</h5>
                    <Row className="mb-3 g-2">
                        <Col md={3}><Form.Control type="text" placeholder="Search Emp ID / Name" value={filters.emp_id} onChange={e => setFilters(f => ({ ...f, emp_id: e.target.value }))} /></Col>
                        <Col md={3}><Select2 value={filters.action} onChange={v => setFilters(f => ({ ...f, action: v }))}
                            options={actionOptions.map(a => ({ value: a, label: a.replace(/_/g, ' ') }))} placeholder="All Actions" /></Col>
                        <Col md={3}><Form.Control type="date" value={filters.date_from} onChange={handleDateChange('date_from')} max="9999-12-31" placeholder="From" /></Col>
                        <Col md={3}><Form.Control type="date" value={filters.date_to} onChange={handleDateChange('date_to')} max="9999-12-31" placeholder="To" /></Col>
                    </Row>
                    <DataTable data={filtered} columns={[
                        { header: 'Date/Time', accessorKey: 'created_at', cell: ({ getValue }) => <span style={{ whiteSpace: 'nowrap' }}>{getValue()}</span> },
                        { header: 'User', id: 'user_display', cell: ({ row }) => {
                            const u = row.original.user
                            return u?.name ? (
                                <>
                                    {u.name}
                                    <span className="text-muted ms-1 small" style={{ fontFamily: 'monospace' }}>({u.emp_id})</span>
                                </>
                            ) : 'System'
                        } },
                        { header: 'Action', accessorKey: 'action', cell: ({ getValue }) => <Badge bg="secondary" style={{ fontSize: '11px' }}>{actionLabel(getValue())}</Badge> },
                        { header: 'Details', accessorKey: 'details', cell: ({ getValue }) => (
                            <span title={getValue() || ''} style={{ maxWidth: 300, display: 'inline-block', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{getValue()}</span>
                        ) },
                        { header: 'IP Address', accessorKey: 'ip_address', cell: ({ getValue }) => <span style={{ fontFamily: 'monospace', fontSize: 12 }}>{getValue() || '-'}</span> },
                        { header: 'User Agent', accessorKey: 'user_agent', cell: ({ getValue }) => (
                            <span title={getValue() || ''} style={{ maxWidth: 220, display: 'inline-block', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{getValue() || '-'}</span>
                        ) },
                        { header: 'View', id: 'view', enableSorting: false, enableColumnFilter: false, cell: ({ row }) => (
                            <Button size="sm" variant="outline-secondary" onClick={() => setDetail(row.original)} title="View all details">
                                <i className="bi bi-eye"></i>
                            </Button>
                        ) },
                    ]} pageSize={15} />
                </Card.Body>
            </Card>

            <Modal show={!!detail} onHide={() => setDetail(null)} size="lg">
                <Modal.Header closeButton><Modal.Title>Audit Log Details</Modal.Title></Modal.Header>
                <Modal.Body>
                    {detail && (
                        <>
                            <Row>
                                <Col md={6} className="pb-2">
                                    <div className="text-muted small">Date/Time</div>
                                    <div className="fw-semibold">{detail.created_at}</div>
                                </Col>
                                <Col md={6} className="pb-2">
                                    <div className="text-muted small">Action</div>
                                    <div className="fw-semibold"><Badge bg="secondary" style={{ fontSize: '11px' }}>{actionLabel(detail.action)}</Badge></div>
                                </Col>
                                <Col md={6} className="pb-2">
                                    <div className="text-muted small">User</div>
                                    <div className="fw-semibold">
                                        {detail.user?.name ? `${detail.user.name} (${detail.user.emp_id})` : (detail.emp_id || 'System')}
                                    </div>
                                </Col>
                                <Col md={6} className="pb-2">
                                    <div className="text-muted small">User ID</div>
                                    <div className="fw-semibold">{detail.user_id || '-'}</div>
                                </Col>
                                <Col md={6} className="pb-2">
                                    <div className="text-muted small">Emp ID</div>
                                    <div className="fw-semibold" style={{ fontFamily: 'monospace' }}>{detail.emp_id || '-'}</div>
                                </Col>
                                <Col md={6} className="pb-2">
                                    <div className="text-muted small">IP Address</div>
                                    <div className="fw-semibold" style={{ fontFamily: 'monospace' }}>{detail.ip_address || '-'}</div>
                                </Col>
                            </Row>
                            <hr />
                            <div className="mb-2"><strong>Details</strong></div>
                            <p className="text-muted" style={{ whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>{detail.details || 'No details.'}</p>
                            <hr />
                            <div className="mb-2"><strong>User Agent</strong></div>
                            <p className="text-muted small" style={{ whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>{detail.user_agent || '-'}</p>
                        </>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setDetail(null)}>Close</Button>
                </Modal.Footer>
            </Modal>
        </AuthenticatedLayout>
    )
}