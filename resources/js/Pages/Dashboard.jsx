import { Head, Link } from '@inertiajs/react'
import { Card, Col, Row, Badge } from 'react-bootstrap'
import DataTable from '../Components/DataTable'
import AuthenticatedLayout from '../Layouts/Authenticated'

const statusColors = { pending_hod: 'warning', pending_principal: 'info', approved: 'success', rejected: 'danger' }
const statusLabels = { pending_hod: 'Pending HOD', pending_principal: 'Pending Principal', approved: 'Approved', rejected: 'Rejected' }

const statStyles = [
    { bg: '#ede9fe', color: '#7c3aed', icon: 'building' },
    { bg: '#dbeafe', color: '#2563eb', icon: 'people' },
    { bg: '#fce7f3', color: '#db2777', icon: 'mortarboard' },
    { bg: '#fef3c7', color: '#d97706', icon: 'book' },
]

const staffStatStyles = [
    { bg: '#dbeafe', color: '#2563eb', icon: 'file-text' },
    { bg: '#fef3c7', color: '#d97706', icon: 'clock' },
    { bg: '#ede9fe', color: '#7c3aed', icon: 'journal-text' },
    { bg: '#d1fae5', color: '#059669', icon: 'calendar-check' },
]

export default function Dashboard({ stats, pendingLeaves, recentPlans, todaySlots }) {
    const isStaff = stats.my_leave_count !== undefined

    const adminStats = stats.departments_count !== undefined ? [
        { value: stats.departments_count, label: 'Departments', style: statStyles[0] },
        { value: stats.employees_count, label: 'Staff', style: statStyles[1] },
        { value: stats.classes_count, label: 'Classes', style: statStyles[2] },
        { value: stats.subjects_count, label: 'Subjects', style: statStyles[3] },
    ] : []

    const staffStats = isStaff ? [
        { value: stats.my_leave_count, label: 'Total Leaves', style: staffStatStyles[0] },
        { value: stats.my_pending_leave, label: 'Pending Leaves', style: staffStatStyles[1] },
        { value: stats.my_plans_count, label: 'Lesson Plans', style: staffStatStyles[2] },
        { value: stats.my_classes_today, label: 'Classes Today', style: staffStatStyles[3] },
    ] : []

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard - Master Timetable" />

            {adminStats.length > 0 && (
                <Row className="g-4 mb-4">
                    {adminStats.map((stat, i) => (
                        <Col md={3} key={i}>
                            <Card className="stat-card border-0 shadow-sm">
                                <Card.Body className="text-center">
                                    <div className="stat-icon" style={{ background: stat.style.bg, color: stat.style.color }}>
                                        <i className={`bi bi-${stat.style.icon}`}></i>
                                    </div>
                                    <h5 className="stat-value">{stat.value}</h5>
                                    <p className="stat-label">{stat.label}</p>
                                </Card.Body>
                            </Card>
                        </Col>
                    ))}
                </Row>
            )}

            {staffStats.length > 0 && (
                <Row className="g-4 mb-4">
                    {staffStats.map((stat, i) => (
                        <Col md={3} key={i}>
                            <Card className="stat-card border-0 shadow-sm">
                                <Card.Body className="text-center">
                                    <div className="stat-icon" style={{ background: stat.style.bg, color: stat.style.color }}>
                                        <i className={`bi bi-${stat.style.icon}`}></i>
                                    </div>
                                    <h5 className="stat-value">{stat.value}</h5>
                                    <p className="stat-label">{stat.label}</p>
                                </Card.Body>
                            </Card>
                        </Col>
                    ))}
                </Row>
            )}

            {isStaff && todaySlots?.length > 0 && (
                <Card className="mb-4">
                    <h6><i className="bi bi-calendar-check me-2"></i>Today's Schedule</h6>
                    <DataTable data={todaySlots} columns={[
                        { header: 'Period', accessorKey: 'period_no' },
                        { header: 'Subject', cell: ({ row }) => `${row.original.subject?.name} (${row.original.subject?.code})` },
                        { header: 'Class', accessorKey: 'class.name' },
                        { header: 'Room', accessorKey: 'room_no', cell: ({ getValue }) => getValue() || '-' },
                    ]} pageSize={20} />
                </Card>
            )}

            {isStaff && recentPlans?.length > 0 && (
                <Card className="mb-4">
                    <h6><i className="bi bi-journal-text me-2"></i>Recent Lesson Plans</h6>
                    <DataTable data={recentPlans} columns={[
                        { header: 'Date', accessorKey: 'plan_date' },
                        { header: 'Topic', accessorKey: 'topic' },
                        { header: 'Subject', accessorKey: 'subject.name' },
                        { header: 'Class', accessorKey: 'class.name' },
                        { header: 'Status', accessorKey: 'status', cell: ({ getValue }) => (
                            <Badge bg={statusColors[getValue()] || 'secondary'}>{statusLabels[getValue()] || getValue()}</Badge>
                        )},
                    ]} pageSize={20} />
                </Card>
            )}

            {pendingLeaves?.length > 0 && (
                <Card className="mb-4">
                    <h6><i className="bi bi-bell me-2"></i>Pending Leave Requests</h6>
                    <DataTable data={pendingLeaves} columns={[
                        { header: 'Employee', accessorKey: 'employee.name' },
                        { header: 'Nature', accessorKey: 'nature' },
                        { header: 'Date', accessorKey: 'leave_date' },
                        { header: 'Status', accessorKey: 'status', cell: ({ getValue }) => (
                            <Badge bg={statusColors[getValue()] || 'secondary'}>{statusLabels[getValue()] || getValue()?.replace(/_/g, ' ')}</Badge>
                        )},
                    ]} pageSize={20} />
                    <Link href="/leave" className="text-decoration-none small mt-2 d-inline-block">
                        View all leaves <i className="bi bi-arrow-right"></i>
                    </Link>
                </Card>
            )}

            {!stats.departments_count && !isStaff && (
                <Card>
                    <div className="empty-state">
                        <i className="bi bi-speedometer2"></i>
                        <p>Welcome to Master Timetable</p>
                    </div>
                </Card>
            )}
        </AuthenticatedLayout>
    )
}
