import { Head, Link, usePage } from '@inertiajs/react'
import { Card, Badge } from 'react-bootstrap'
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

const qa = (href, icon, label) => ({ href, icon, label })

const quickAccessByRole = {
    super_admin: [
        qa('/departments', 'building', 'Departments'),
        qa('/employees', 'people', 'Staff'),
        qa('/classes', 'mortarboard', 'Classes'),
        qa('/subjects', 'book', 'Subjects'),
        qa('/bulk-upload', 'upload', 'Bulk Upload'),
        qa('/timetable', 'calendar-week', 'Timetable'),
        qa('/leave', 'file-text', 'Leave'),
        qa('/lesson-plans', 'journal-text', 'Lesson Plan'),
        qa('/audit-log', 'clock-history', 'Audit Log'),
        qa('/login-attempts', 'shield-lock', 'Login Attempts'),
        qa('/workload', 'bar-chart', 'Workload'),
    ],
    admin: [
        qa('/departments', 'building', 'Departments'),
        qa('/employees', 'people', 'Staff'),
        qa('/classes', 'mortarboard', 'Classes'),
        qa('/subjects', 'book', 'Subjects'),
        qa('/bulk-upload', 'upload', 'Bulk Upload'),
        qa('/timetable', 'calendar-week', 'Timetable'),
        qa('/leave', 'file-text', 'Leave'),
        qa('/lesson-plans', 'journal-text', 'Lesson Plan'),
        qa('/audit-log', 'clock-history', 'Audit Log'),
        qa('/login-attempts', 'shield-lock', 'Login Attempts'),
        qa('/workload', 'bar-chart', 'Workload'),
    ],
    principal: [
        qa('/employees', 'people', 'Staff'),
        qa('/classes', 'mortarboard', 'Classes'),
        qa('/subjects', 'book', 'Subjects'),
        qa('/common-papers', 'globe2', 'Common Papers'),
        qa('/timetable', 'calendar-week', 'Timetable'),
        qa('/leave', 'file-text', 'Leave'),
        qa('/substitution', 'arrow-repeat', 'Substitution'),
        qa('/lesson-plans', 'journal-text', 'Lesson Plan'),
        qa('/lesson-reports', 'clipboard-data', 'Lesson Report'),
        qa('/workload', 'bar-chart', 'Workload'),
    ],
    vice_principal: [
        qa('/employees', 'people', 'Staff'),
        qa('/classes', 'mortarboard', 'Classes'),
        qa('/subjects', 'book', 'Subjects'),
        qa('/timetable', 'calendar-week', 'Timetable'),
        qa('/leave', 'file-text', 'Leave'),
        qa('/substitution', 'arrow-repeat', 'Substitution'),
        qa('/compensations', 'arrow-left-right', 'Compensation'),
        qa('/lesson-plans', 'journal-text', 'Lesson Plan'),
        qa('/lesson-reports', 'clipboard-data', 'Lesson Report'),
    ],
    hod: [
        qa('/employees', 'people', 'Staff'),
        qa('/subjects', 'book', 'Subjects'),
        qa('/staff-subjects', 'diagram-3', 'Staff Subjects'),
        qa('/timetable', 'calendar-week', 'Timetable'),
        qa('/leave', 'file-text', 'Leave'),
        qa('/substitution', 'arrow-repeat', 'Substitution'),
        qa('/lesson-plans', 'journal-text', 'Lesson Plan'),
        qa('/lesson-reports', 'clipboard-data', 'Lesson Report'),
        qa('/workload', 'bar-chart', 'Workload'),
    ],
    staff: [
        qa('/staff-subjects', 'diagram-3', 'My Subjects'),
        qa('/timetable', 'calendar-week', 'Timetable'),
        qa('/leave', 'file-text', 'Leave'),
        qa('/substitution', 'arrow-repeat', 'Substitution'),
        qa('/compensations', 'arrow-left-right', 'Compensation'),
        qa('/lesson-plans', 'journal-text', 'Lesson Plan'),
        qa('/lesson-reports', 'clipboard-data', 'Lesson Report'),
    ],
}

const defaultQuickAccess = [
    qa('/timetable', 'calendar-week', 'Timetable'),
    qa('/leave', 'file-text', 'Leave'),
    qa('/lesson-plans', 'journal-text', 'Lesson Plan'),
]

function getGreeting() {
    const hour = new Date().getHours()
    if (hour < 12) return 'Good morning'
    if (hour < 17) return 'Good afternoon'
    return 'Good evening'
}

function formatToday() {
    return new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
}

function WelcomeHero({ user, heroStats }) {
    const roleLabel = user?.role?.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
    const userInitial = user?.name ? user.name.charAt(0).toUpperCase() : '?'
    const firstName = user?.name?.split(' ')[0] || 'there'

    return (
        <div className="welcome-hero mb-4">
            <div className="welcome-hero-grid" aria-hidden="true"></div>
            <div className="welcome-hero-orb welcome-hero-orb-1" aria-hidden="true"></div>
            <div className="welcome-hero-orb welcome-hero-orb-2" aria-hidden="true"></div>
            <div className="welcome-hero-content">
                <div className="welcome-hero-main">
                    <div className="welcome-eyebrow">
                        <i className="bi bi-calendar3 me-2"></i>{formatToday()}
                    </div>
                    <h2 className="welcome-greeting">
                        {getGreeting()}, {firstName}!
                    </h2>
                    <p className="welcome-subtitle">
                        Welcome back to your <span className="welcome-role-chip"><i className="bi bi-shield-check me-1"></i>{roleLabel}</span> workspace.
                    </p>

                    {heroStats.length > 0 && (
                        <div className="welcome-stats">
                            {heroStats.map((s, i) => (
                                <div key={i} className="welcome-stat">
                                    <span className="welcome-stat-icon" style={{ background: s.style.bg, color: s.style.color }}>
                                        <i className={`bi bi-${s.style.icon}`}></i>
                                    </span>
                                    <div className="welcome-stat-body">
                                        <div className="welcome-stat-value">{s.value}</div>
                                        <div className="welcome-stat-label">{s.label}</div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="welcome-hero-card">
                    <div className="hero-avatar-ring">
                        <div className="hero-avatar">{userInitial}</div>
                    </div>
                    <div className="hero-card-info">
                        <div className="hero-card-name">{user?.name}</div>
                        <div className="hero-card-role">{roleLabel}</div>
                        <div className="hero-card-dept"><i className="bi bi-building me-1"></i>{user?.dept_name || '—'}</div>
                    </div>
                    <div className="hero-card-secure">
                        <i className="bi bi-shield-lock-fill me-1"></i>Secure session
                    </div>
                </div>
            </div>
        </div>
    )
}

function QuickAccess({ user }) {
    const available = quickAccessByRole[user?.role] || defaultQuickAccess

    return (
        <div className="quick-access mb-4">
            <div className="quick-access-header">
                <h6 className="mb-0">
                    <i className="bi bi-lightning-charge-fill me-2"></i>Quick Access
                </h6>
            </div>
            <div className="quick-access-grid">
                {available.map((a, i) => (
                    <Link key={i} href={a.href} className="quick-access-tile">
                        <span className="quick-access-icon">
                            <i className={`bi bi-${a.icon}`}></i>
                        </span>
                        <span className="quick-access-label">{a.label}</span>
                    </Link>
                ))}
            </div>
        </div>
    )
}

export default function Dashboard({ stats, pendingLeaves, recentPlans, todaySlots }) {
    const { auth } = usePage().props
    const user = auth?.user
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

    const heroStats = adminStats.length > 0 ? adminStats : staffStats

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard - Master Timetable" />

            <WelcomeHero user={user} heroStats={heroStats} />

            <QuickAccess user={user} />

            {isStaff && todaySlots?.length > 0 && (
                <Card className="mb-4">
                    <h6><i className="bi bi-calendar-check me-2"></i>Today's Schedule</h6>
                    <DataTable data={todaySlots} columns={[
                        { header: 'Period', accessorKey: 'period_no' },
                        { header: 'Subject', cell: ({ row }) => `${row.original.subject?.name} (${row.original.subject?.code})` },
                        {
                            header: 'Class', cell: ({ row }) => (
                                <>
                                    {row.original.class?.name}
                                    {row.original.is_combined && <Badge bg="dark" className="ms-2" style={{ fontSize: '10px' }}><i className="bi bi-link-45deg me-1"></i>Combined</Badge>}
                                </>
                            )
                        },
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
