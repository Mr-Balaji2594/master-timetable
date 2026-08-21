import { Head, router, usePage } from '@inertiajs/react'
import { Card, Button, Badge, Form, Row, Col } from 'react-bootstrap'
import { useState } from 'react'
import Select2 from '../../Components/Select2'
import DataTable from '../../Components/DataTable'
import FlashAlert from '../../Components/FlashAlert'
import { showConfirmCustom } from '../../Helpers/sweetAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

export default function Index({ attempts }) {
    const { flash } = usePage().props
    const [empFilter, setEmpFilter] = useState('')

    const filtered = empFilter
        ? attempts.filter(a => a.emp_id.toLowerCase().includes(empFilter.toLowerCase()))
        : attempts

    const resetAttempts = async (empId) => {
        const result = await showConfirmCustom({
            title: 'Reset Login Attempts?',
            text: `Clear failed login attempts for ${empId}?`,
            confirmText: 'Yes, reset',
        })
        if (result.isConfirmed) router.post(`/login-attempts/reset/${encodeURIComponent(empId)}`, {}, { preserveState: true })
    }

    const resetAll = async () => {
        const result = await showConfirmCustom({
            title: 'Reset All Login Attempts?',
            text: 'Clear failed login attempts for all employees?',
            confirmText: 'Yes, reset all',
        })
        if (result.isConfirmed) router.post('/login-attempts/reset-all', {}, { preserveState: true })
    }

    return (
        <AuthenticatedLayout>
            <Head title="Login Attempts - Master Timetable" />
            <FlashAlert message={flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">Login Attempts</h5>
                        <Button variant="outline-danger" onClick={resetAll} disabled={!attempts.length}>
                            <i className="bi bi-arrow-counterclockwise me-1"></i>Reset All
                        </Button>
                    </div>

                    <Row className="mb-3">
                        <Col md={4}>
                            <Form.Control
                                type="text"
                                placeholder="Search Employee ID"
                                value={empFilter}
                                onChange={(e) => setEmpFilter(e.target.value)}
                            />
                        </Col>
                    </Row>

                    <DataTable
                        data={filtered}
                        columns={[
                            { header: 'Emp ID', accessorKey: 'emp_id' },
                            { header: 'Name', accessorKey: 'employee_name' },
                            {
                                header: 'Status',
                                accessorKey: 'locked',
                                cell: ({ row }) => (
                                    <Badge bg={row.original.locked ? 'danger' : 'success'} style={{ fontSize: '12px', letterSpacing: '0.3px' }}>
                                        <i className={`bi bi-${row.original.locked ? 'lock-fill' : 'unlock-fill'} me-1`}></i>
                                        {row.original.locked ? 'Locked' : 'Active'}
                                    </Badge>
                                ),
                            },
                            { header: 'Failures (15 min)', accessorKey: 'recent_failures' },
                            { header: 'Failures (24 hr)', accessorKey: 'total_failures' },
                            { header: 'Last Attempt', accessorKey: 'last_attempt', cell: ({ getValue }) => <span style={{ whiteSpace: 'nowrap' }}>{getValue()}</span> },
                            {
                                header: 'Actions',
                                id: 'actions',
                                enableSorting: false,
                                enableColumnFilter: false,
                                cell: ({ row }) => (
                                    <Button size="sm" variant="outline-danger" onClick={() => resetAttempts(row.original.emp_id)} title="Reset Login Attempts">
                                        <i className="bi bi-arrow-counterclockwise me-1"></i>Reset
                                    </Button>
                                ),
                            },
                        ]}
                        pageSize={15}
                    />
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    )
}
