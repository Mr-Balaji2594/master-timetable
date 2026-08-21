import { Link, usePage, router } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import Swal from "sweetalert2";
import { toast } from "../Helpers/sweetAlert";

const IDLE_TIMEOUT_MINUTES = 15;
const WARNING_MINUTES_BEFORE = 1;

function IdleTimeout() {
    const idleRef = useRef(null);
    const warnedRef = useRef(false);

    useEffect(() => {
        const reset = () => {
            warnedRef.current = false;
            clearTimeout(idleRef.current);
            idleRef.current = setTimeout(
                onIdle,
                IDLE_TIMEOUT_MINUTES * 60 * 1000,
            );
        };
        const onIdle = () => {
            if (!warnedRef.current) {
                warnedRef.current = true;
                toast.fire({
                    icon: "warning",
                    title: `You'll be logged out in ${WARNING_MINUTES_BEFORE} minute(s) due to inactivity.`,
                });
                idleRef.current = setTimeout(
                    logout,
                    WARNING_MINUTES_BEFORE * 60 * 1000,
                );
            }
        };
        const logout = () => {
            toast.close();
            Swal.fire({
                title: "Session Expired",
                text: "You were logged out due to inactivity.",
                icon: "info",
                confirmButtonText: "Sign in again",
                allowOutsideClick: false,
            }).then(() => {
                router.post("/logout");
            });
        };

        const events = [
            "mousemove",
            "keydown",
            "click",
            "scroll",
            "touchstart",
        ];
        events.forEach((ev) =>
            window.addEventListener(ev, reset, { passive: true }),
        );

        reset();
        return () => {
            clearTimeout(idleRef.current);
            events.forEach((ev) => window.removeEventListener(ev, reset));
        };
    }, []);

    return null;
}

const navigation = [
    {
        section: "Administration",
        roles: ["admin", "super_admin", "principal", "vice_principal"],
    },
    { name: "Dashboard", href: "/", icon: "speedometer2", roles: ["any"] },
    {
        name: "Departments",
        href: "/departments",
        icon: "building",
        roles: ["admin", "super_admin", "principal", "vice_principal"],
    },
    {
        name: "Staff",
        href: "/employees",
        icon: "people",
        roles: ["admin", "super_admin", "principal", "vice_principal", "hod"],
    },
    {
        name: "Classes",
        href: "/classes",
        icon: "mortarboard",
        roles: ["admin", "super_admin", "principal", "vice_principal", "hod"],
    },
    {
        name: "Subjects",
        href: "/subjects",
        icon: "book",
        roles: ["admin", "super_admin", "principal", "vice_principal", "hod"],
    },
    {
        name: "Staff Subjects",
        href: "/staff-subjects",
        icon: "diagram-3",
        roles: ["admin", "super_admin", "principal", "vice_principal", "hod"],
    },
    {
        name: "Bulk Upload",
        href: "/bulk-upload",
        icon: "upload",
        roles: ["admin", "super_admin"],
    },
    {
        name: "Leave Balance",
        href: "/leave-balance",
        icon: "sliders",
        roles: ["admin", "super_admin"],
    },
    {
        name: "Earned Leave",
        href: "/earned-leave",
        icon: "calendar-check",
        roles: ["admin", "super_admin"],
    },
    { section: "My Subjects", roles: ["staff"] },
    {
        name: "My Subjects",
        href: "/staff-subjects",
        icon: "diagram-3",
        roles: ["staff"],
    },
    { section: "Operations", roles: ["any"] },
    {
        name: "Timetable",
        href: "/timetable",
        icon: "calendar-week",
        roles: ["any"],
    },
    {
        name: "Academic Calendar",
        href: "/academic-calendar",
        icon: "calendar2-week",
        roles: ["any"],
    },
    {
        name: "Common Papers",
        href: "/common-papers",
        icon: "globe2",
        roles: ["admin", "super_admin", "principal", "vice_principal"],
    },
    { name: "Leave", href: "/leave", icon: "file-text", roles: ["any"] },
    {
        name: "Substitution",
        href: "/substitution",
        icon: "arrow-repeat",
        roles: ["any"],
    },
    {
        name: "Compensation",
        href: "/compensations",
        icon: "arrow-left-right",
        roles: ["any"],
    },
    { name: "Workload", href: "/workload", icon: "bar-chart", roles: ["admin", "principal", "hod"] },
    {
        name: "Lesson Plan",
        href: "/lesson-plans",
        icon: "journal-text",
        roles: ["any"],
    },
    {
        name: "Lesson Report",
        href: "/lesson-reports",
        icon: "clipboard-data",
        roles: ["any"],
    },
    {
        name: "Change Password",
        href: "/change-password",
        icon: "key",
        roles: ["any"],
    },
    {
        name: "Audit Log",
        href: "/audit-log",
        icon: "clock-history",
        roles: ["admin", "super_admin"],
    },
    {
        name: "Login Attempts",
        href: "/login-attempts",
        icon: "shield-lock",
        roles: ["admin", "super_admin"],
    },
];

function hasAccess(user, roles) {
    if (roles.includes("any")) return true;
    if (
        roles.includes("admin") &&
        (user?.role === "admin" || user?.role === "super_admin")
    )
        return true;
    return roles.includes(user?.role);
}

function roleBadgeColor(role) {
    const colors = {
        super_admin: "#dc2626",
        admin: "#ef4444",
        principal: "#8b5cf6",
        vice_principal: "#a78bfa",
        hod: "#f59e0b",
    };
    return colors[role] || "#94a3b8";
}

function getPageTitle(path) {
    const titles = {
        "/": "Dashboard",
        "/departments": "Departments",
        "/employees": "Staff",
        "/classes": "Classes",
        "/subjects": "Subjects",
        "/timetable": "Timetable",
        "/academic-calendar": "Academic Calendar",
        "/leave": "Leave",
        "/substitution": "Substitution",
        "/compensations": "Compensation",
        "/workload": "Workload",
        "/lesson-plans": "Lesson Plan",
        "/lesson-reports": "Lesson Report",
        "/bulk-upload": "Bulk Upload",
        "/staff-subjects": "Staff Subjects",
        "/change-password": "Change Password",
        "/audit-log": "Audit Log",
        "/leave-balance": "Leave Balance",
        "/earned-leave": "Earned Leave",
        "/common-papers": "Common Papers",
        "/login-attempts": "Login Attempts",
    };
    return titles[path] || "Dashboard";
}

function getPageIcon(path) {
    const icons = {
        "/": "house-door",
        "/departments": "building",
        "/employees": "people",
        "/classes": "mortarboard",
        "/subjects": "book",
        "/timetable": "calendar-week",
        "/academic-calendar": "calendar2-week",
        "/leave": "file-text",
        "/substitution": "arrow-repeat",
        "/compensations": "arrow-left-right",
        "/workload": "bar-chart",
        "/lesson-plans": "journal-text",
        "/lesson-reports": "clipboard-data",
        "/bulk-upload": "upload",
        "/staff-subjects": "diagram-3",
        "/change-password": "key",
        "/audit-log": "clock-history",
        "/leave-balance": "sliders",
        "/earned-leave": "calendar-check",
        "/common-papers": "globe2",
        "/login-attempts": "shield-lock",
    };
    return icons[path] || "circle";
}

export default function AuthenticatedLayout({ children }) {
    const { auth } = usePage().props;
    const url = usePage().url;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(false);
    const user = auth?.user;

    const toggleSidebar = () => setSidebarOpen(!sidebarOpen);
    const toggleCollapsed = () => setCollapsed(!collapsed);

    const visibleNav = navigation.filter((item) => {
        if (item.section) return true;
        return hasAccess(user, item.roles);
    });

    const handleLogout = (e) => {
        e.preventDefault();
        router.post("/logout");
    };

    const userInitial = user?.name ? user.name.charAt(0).toUpperCase() : "?";
    const roleLabel = user?.role
        ?.replace(/_/g, " ")
        .replace(/\b\w/g, (c) => c.toUpperCase());

    return (
        <div className={`app-shell ${collapsed ? "sidebar-collapsed" : ""}`}>
            <IdleTimeout />
            <button
                className="sidebar-toggle"
                onClick={toggleSidebar}
                aria-label="Toggle sidebar"
            >
                <i className="bi bi-list"></i>
            </button>
            <div
                className={`sidebar-overlay ${sidebarOpen ? "show" : ""}`}
                onClick={toggleSidebar}
            ></div>

            <div className={`sidebar ${sidebarOpen ? "open" : ""}`}>
                <div className="sidebar-header">
                    <div className="brand">
                        <span className="brand-logo">
                            <img src="/images/favicon.png" alt="Master Timetable Logo" />
                        </span>
                        Master Timetable
                    </div>
                    <div className="brand-sub">College Management System</div>
                </div>

                <div className="sidebar-nav">
                    {visibleNav.map((item, idx) => {
                        if (item.section) {
                            return (
                                <div key={idx} className="sidebar-section">
                                    {item.section}
                                </div>
                            );
                        }
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={
                                    window.location.pathname === item.href
                                        ? "active"
                                        : ""
                                }
                                onClick={() => {
                                    if (window.innerWidth <= 768)
                                        setSidebarOpen(false);
                                }}
                            >
                                <i className={`bi bi-${item.icon} icon`}></i>
                                {item.name}
                            </Link>
                        );
                    })}
                </div>

                {user && (
                    <div className="sidebar-profile">
                        <div className="sidebar-avatar">{userInitial}</div>
                        <div className="sidebar-profile-info">
                            <div className="sidebar-profile-name">
                                {user.name}
                            </div>
                            <div className="sidebar-profile-role">
                                {roleLabel}
                            </div>
                        </div>
                        {/* <a
                            href="#"
                            onClick={handleLogout}
                            className="sidebar-profile-logout"
                            title="Logout"
                        >
                            <i className="bi bi-box-arrow-right"></i>
                        </a> */}
                    </div>
                )}
            </div>

            <div className="main-content">
                <div className="navbar-custom">
                    <button
                        className="sidebar-collapse-btn"
                        onClick={toggleCollapsed}
                        aria-label="Toggle sidebar"
                        title={collapsed ? "Expand sidebar" : "Collapse sidebar"}
                    >
                        <i className={`bi bi-${collapsed ? "chevron-right" : "chevron-left"}`}></i>
                    </button>
                    <h4>
                        <i
                            className={`bi bi-${getPageIcon(window.location.pathname)} me-2`}
                        ></i>
                        {getPageTitle(window.location.pathname)}
                    </h4>
                    <div className="user-badges">
                        {user && (
                            <>
                                <div className="user-info">
                                    <div className="user-avatar">
                                        {userInitial}
                                    </div>
                                    <div>
                                        <div className="user-name">
                                            {user.name}
                                        </div>
                                    </div>
                                </div>
                                <span
                                    className="badge"
                                    style={{ background: "#4f46e5" }}
                                >
                                    {user.dept_name}
                                </span>
                                <span
                                    className="badge"
                                    style={{
                                        background: roleBadgeColor(user.role),
                                    }}
                                >
                                    {user.role
                                        .replace(/_/g, " ")
                                        .replace(/\b\w/g, (c) =>
                                            c.toUpperCase(),
                                        )}
                                </span>
                                <a
                                    href="#"
                                    onClick={handleLogout}
                                    className="btn-logout-nav"
                                >
                                    <i className="bi bi-box-arrow-right"></i>
                                    <span>Logout</span>
                                </a>
                            </>
                        )}
                    </div>
                </div>

                <div id="page-content-wrapper">
                    <div key={url} className="page-fade">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
