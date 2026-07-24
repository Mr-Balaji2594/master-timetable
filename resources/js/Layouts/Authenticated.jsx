import { Link, usePage, router } from '@inertiajs/react'
import { useState } from 'react'

const navigation = [
    { section: 'Administration', roles: ['admin', 'super_admin', 'principal', 'vice_principal'] },
    { name: 'Dashboard', href: '/', icon: 'speedometer2', roles: ['any'] },
    { name: 'Departments', href: '/departments', icon: 'building', roles: ['admin', 'super_admin', 'principal', 'vice_principal'] },
    { name: 'Staff', href: '/employees', icon: 'people', roles: ['admin', 'super_admin', 'principal', 'vice_principal', 'hod'] },
    { name: 'Classes', href: '/classes', icon: 'mortarboard', roles: ['admin', 'super_admin', 'principal', 'vice_principal', 'hod'] },
    { name: 'Subjects', href: '/subjects', icon: 'book', roles: ['admin', 'super_admin', 'principal', 'vice_principal', 'hod'] },
    { name: 'Staff Subjects', href: '/staff-subjects', icon: 'diagram-3', roles: ['any'] },
    { name: 'Bulk Upload', href: '/bulk-upload', icon: 'upload', roles: ['admin', 'super_admin'] },
    { name: 'Leave Balance', href: '/leave-balance', icon: 'sliders', roles: ['admin', 'super_admin'] },
    { section: 'My Subjects', roles: ['staff'] },
    { name: 'My Subjects', href: '/staff-subjects', icon: 'diagram-3', roles: ['staff'] },
    { section: 'Operations', roles: ['any'] },
    { name: 'Timetable', href: '/timetable', icon: 'calendar-week', roles: ['any'] },
    { name: 'Common Papers', href: '/common-papers', icon: 'globe2', roles: ['admin', 'super_admin', 'principal', 'vice_principal'] },
    { name: 'Leave', href: '/leave', icon: 'file-text', roles: ['any'] },
    { name: 'Substitution', href: '/substitution', icon: 'arrow-repeat', roles: ['any'] },
    { name: 'Workload', href: '/workload', icon: 'bar-chart', roles: ['any'] },
    { name: 'Lesson Plan', href: '/lesson-plans', icon: 'journal-text', roles: ['any'] },
    { name: 'Lesson Report', href: '/lesson-reports', icon: 'clipboard-data', roles: ['any'] },
    { name: 'Change Password', href: '/change-password', icon: 'key', roles: ['any'] },
    { name: 'Audit Log', href: '/audit-log', icon: 'clock-history', roles: ['admin', 'super_admin'] },
]

function hasAccess(user, roles) {
    if (roles.includes('any')) return true
    if (roles.includes('admin') && (user?.role === 'admin' || user?.role === 'super_admin')) return true
    return roles.includes(user?.role)
}

function roleBadgeColor(role) {
    const colors = {
        super_admin: '#dc2626',
        admin: '#ef4444',
        principal: '#8b5cf6',
        vice_principal: '#a78bfa',
        hod: '#f59e0b',
    }
    return colors[role] || '#94a3b8'
}

function getPageTitle(path) {
    const titles = {
        '/': 'Dashboard',
        '/departments': 'Departments',
        '/employees': 'Staff',
        '/classes': 'Classes',
        '/subjects': 'Subjects',
        '/timetable': 'Timetable',
        '/leave': 'Leave',
        '/substitution': 'Substitution',
        '/workload': 'Workload',
        '/lesson-plans': 'Lesson Plan',
        '/lesson-reports': 'Lesson Report',
        '/bulk-upload': 'Bulk Upload',
        '/staff-subjects': 'Staff Subjects',
        '/change-password': 'Change Password',
        '/audit-log': 'Audit Log',
        '/leave-balance': 'Leave Balance',
        '/common-papers': 'Common Papers',
    }
    return titles[path] || 'Dashboard'
}

function getPageIcon(path) {
    const icons = {
        '/': 'house-door',
        '/departments': 'building',
        '/employees': 'people',
        '/classes': 'mortarboard',
        '/subjects': 'book',
        '/timetable': 'calendar-week',
        '/leave': 'file-text',
        '/substitution': 'arrow-repeat',
        '/workload': 'bar-chart',
        '/lesson-plans': 'journal-text',
        '/lesson-reports': 'clipboard-data',
        '/bulk-upload': 'upload',
        '/staff-subjects': 'diagram-3',
        '/change-password': 'key',
        '/audit-log': 'clock-history',
        '/leave-balance': 'sliders',
        '/common-papers': 'globe2',
    }
    return icons[path] || 'circle'
}

export default function AuthenticatedLayout({ children }) {
    const { auth } = usePage().props
    const [sidebarOpen, setSidebarOpen] = useState(false)
    const user = auth?.user

    const toggleSidebar = () => setSidebarOpen(!sidebarOpen)

    const visibleNav = navigation.filter((item) => {
        if (item.section) return true
        return hasAccess(user, item.roles)
    })

    const handleLogout = (e) => {
        e.preventDefault()
        router.post('/logout')
    }

    const userInitial = user?.name ? user.name.charAt(0).toUpperCase() : '?'

    return (
        <div>
            <button
                className="sidebar-toggle"
                onClick={toggleSidebar}
                aria-label="Toggle sidebar"
            >
                <i className="bi bi-list"></i>
            </button>
            <div
                className={`sidebar-overlay ${sidebarOpen ? 'show' : ''}`}
                onClick={toggleSidebar}
            ></div>

            <div className={`sidebar ${sidebarOpen ? 'open' : ''}`}>
                <div className="brand">
                    <i className="bi bi-calendar-range"></i>
                    Master Timetable
                </div>
                <div className="brand-sub">College Management System</div>

                <div style={{ flex: 1 }}>
                    {visibleNav.map((item, idx) => {
                        if (item.section) {
                            return (
                                <div key={idx} className="sidebar-section">
                                    {item.section}
                                </div>
                            )
                        }
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={window.location.pathname === item.href ? 'active' : ''}
                                onClick={() => { if (window.innerWidth <= 768) setSidebarOpen(false) }}
                            >
                                <i className={`bi bi-${item.icon} icon`}></i>
                                {item.name}
                            </Link>
                        )
                    })}
                </div>

                <a href="#" onClick={handleLogout} className="logout-link">
                    <i className="bi bi-box-arrow-right icon"></i> Logout
                </a>
            </div>

            <div className="main-content">
                <div className="navbar-custom">
                    <h4>
                        <i className={`bi bi-${getPageIcon(window.location.pathname)} me-2`}></i>
                        {getPageTitle(window.location.pathname)}
                    </h4>
                    <div className="user-badges">
                        {user && (
                            <>
                                <div className="user-info">
                                    <div className="user-avatar">{userInitial}</div>
                                    <div>
                                        <div className="user-name">{user.name}</div>
                                    </div>
                                </div>
                                <span className="badge" style={{ background: '#4f46e5' }}>
                                    {user.dept_name}
                                </span>
                                <span className="badge" style={{ background: roleBadgeColor(user.role) }}>
                                    {user.role.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
                                </span>
                            </>
                        )}
                    </div>
                </div>

                <div id="page-content-wrapper">{children}</div>
            </div>
        </div>
    )
}
