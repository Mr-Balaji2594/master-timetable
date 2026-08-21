import { Head, usePage, router } from "@inertiajs/react";
import {
    Card,
    Button,
    Modal,
    Form,
    Row,
    Col,
    Badge,
} from "react-bootstrap";
import { useState, useMemo } from "react";
import { useForm, Controller } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import Select2 from "../../Components/Select2";
import Select2Field from "../../Components/Select2Field";
import FormField from "../../Components/FormField";
import DataTable from "../../Components/DataTable";
import FormErrors from "../../Components/FormErrors";
import FlashAlert from "../../Components/FlashAlert";
import { showConfirm } from "../../Helpers/sweetAlert";
import AuthenticatedLayout from "../../Layouts/Authenticated";

const roles = [
    "super_admin",
    "admin",
    "principal",
    "vice_principal",
    "hod",
    "staff",
];

const roleBadgeColors = {
    super_admin: "dark",
    admin: "danger",
    principal: "warning",
    vice_principal: "info",
    hod: "success",
    staff: "secondary",
};

const roleBadgeColor = (role) => roleBadgeColors[role] || "secondary";

const modes = ["permanent", "temporary", "contract"];

const modeBadgeColors = {
    permanent: "success",
    temporary: "warning",
    contract: "info",
};

const modeBadgeColor = (mode) => modeBadgeColors[mode] || "secondary";

const schema = z.object({
    emp_id: z.string().min(1, "Emp ID is required"),
    name: z.string().min(1, "Name is required"),
    designation: z.string().optional(),
    mode: z.string().optional(),
    department_id: z.string().min(1, "Department is required"),
    role: z.string().min(1, "Role is required"),
    password: z.string().optional(),
    is_active: z.boolean().optional(),
});

export default function Index({ employees, departments, filters }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const canManage = ["admin", "super_admin", "principal"].includes(
        user?.role,
    );
    const isHod = user?.role === 'hod'
    const [show, setShow] = useState(false);
    const [edit, setEdit] = useState(null);
    const [viewOnly, setViewOnly] = useState(false);
    const [deptFilter, setDeptFilter] = useState(isHod ? String(user?.dept_id ?? '') : (filters?.department_id || ""));

    const defaults = {
        emp_id: "",
        name: "",
        designation: "",
        mode: "permanent",
        department_id: "",
        role: "staff",
        password: "",
        is_active: true,
    };
    const {
        control,
        handleSubmit,
        reset,
        setError,
        formState: { errors },
    } = useForm({
        resolver: zodResolver(schema),
        defaultValues: defaults,
    });

    const openCreate = () => {
        reset(defaults);
        setEdit(null);
        setViewOnly(false);
        setShow(true);
    };
    const openEdit = (e) => {
        const { password, ...rest } = e;
        reset({
            ...rest,
            department_id: String(rest.department_id ?? ''),
            designation: String(rest.designation ?? ''),
            mode: String(rest.mode ?? 'permanent'),
            role: String(rest.role ?? 'staff'),
            is_active: rest.is_active !== false,
        });
        setEdit(e);
        setViewOnly(false);
        setShow(true);
    };
    const openView = (e) => {
        const { password, ...rest } = e;
        reset({
            ...rest,
            department_id: String(rest.department_id ?? ''),
            designation: String(rest.designation ?? ''),
            mode: String(rest.mode ?? 'permanent'),
            role: String(rest.role ?? 'staff'),
            is_active: rest.is_active !== false,
        });
        setEdit(e);
        setViewOnly(true);
        setShow(true);
    };
    const submit = handleSubmit((formData) => {
        if (!edit && (!formData.password || formData.password.length < 6)) {
            setError("password", {
                message: "Password must be at least 6 characters",
            });
            return;
        }
        const done = () => {
            setShow(false);
            setEdit(null);
            setViewOnly(false);
            reset(defaults);
        };
        const onError = (serverErrors) =>
            Object.entries(serverErrors).forEach(([k, msgs]) =>
                setError(k, { message: Array.isArray(msgs) ? msgs[0] : msgs }),
            );
        edit
            ? router.put(`/employees/${edit.id}`, formData, {
                  onSuccess: done,
                  onError,
                  preserveState: true,
              })
            : router.post("/employees", formData, { onSuccess: done, onError, preserveState: true });
    });
    const handleDelete = async (emp) => {
        const result = await showConfirm("Delete Employee?", `Delete ${emp.name} (${emp.emp_id})?`)
        if (result.isConfirmed) router.delete(`/employees/${emp.id}`)
    };
    const resetPwd = async (id) => {
        const result = await showConfirm("Reset Password?", "Password will be reset to default.");
        if (result.isConfirmed) router.post(`/employees/${id}/reset-password`, {}, { preserveState: true });
    };
    const toggleStatus = (e) => router.post(`/employees/${e.id}/toggle-status`, {}, { preserveState: true });

    const filtered = deptFilter
        ? employees.filter((e) => e.department_id == deptFilter)
        : employees;

    const columns = useMemo(
        () => [
            { header: "Emp ID", accessorKey: "emp_id" },
            { header: "Name", accessorKey: "name" },
            { header: "Designation", accessorKey: "designation" },
            {
                header: "Mode",
                accessorKey: "mode",
                cell: ({ getValue }) => (
                    <Badge
                        bg={modeBadgeColor(getValue())}
                        style={{ fontSize: '12px', letterSpacing: '0.3px' }}
                    >
                        {getValue() || 'permanent'}
                    </Badge>
                ),
            },
            { header: "Department", accessorKey: "department.name" },
            {
                header: "Role",
                accessorKey: "role",
                cell: ({ getValue }) => (
                    <Badge
                        bg={roleBadgeColor(getValue())}
                        className="role-badge"
                        style={{ fontSize: '12px', letterSpacing: '0.3px' }}
                    >
                        {getValue()}
                    </Badge>
                ),
            },
            {
                header: "Status",
                accessorKey: "is_active",
                enableColumnFilter: false,
                cell: ({ row }) => (
                    <Badge
                        bg={row.original.is_active !== false ? "success" : "secondary"}
                        style={{ fontSize: '12px', letterSpacing: '0.3px' }}
                    >
                        <i className={`bi bi-${row.original.is_active !== false ? 'check-circle' : 'x-circle'} me-1`}></i>
                        {row.original.is_active !== false ? 'Active' : 'Inactive'}
                    </Badge>
                ),
            },
            {
                header: "Actions",
                id: "actions",
                enableSorting: false,
                enableColumnFilter: false,
                cell: ({ row }) => (
                    <div className="d-flex gap-1 align-items-center flex-wrap">
                        {canManage && (
                            <Button
                                size="sm"
                                variant="outline-primary"
                                onClick={() => openEdit(row.original)}
                                title="Edit"
                            >
                                <i className="bi bi-pencil-square"></i>
                            </Button>
                        )}
                        {!canManage && (
                            <Button
                                size="sm"
                                variant="outline-info"
                                onClick={() => openView(row.original)}
                                title="View"
                            >
                                <i className="bi bi-eye"></i>
                            </Button>
                        )}
                        {canManage && (
                            <Button
                                size="sm"
                                variant={row.original.is_active !== false ? "outline-secondary" : "outline-success"}
                                onClick={() => toggleStatus(row.original)}
                                title={row.original.is_active !== false ? 'Deactivate' : 'Activate'}
                            >
                                <i className={`bi bi-toggle-${row.original.is_active !== false ? 'on' : 'off'}`}></i>
                            </Button>
                        )}
                        {canManage && (
                            <Button
                                size="sm"
                                variant="outline-warning"
                                onClick={() => resetPwd(row.original.id)}
                                title="Reset Password"
                            >
                                <i className="bi bi-key"></i>
                            </Button>
                        )}
                        {canManage && (
                            <Button
                                size="sm"
                                variant="outline-danger"
                                onClick={() => handleDelete(row.original)}
                                title="Delete"
                            >
                                <i className="bi bi-trash"></i>
                            </Button>
                        )}
                    </div>
                ),
            },
        ],
        [canManage],
    );

    return (
        <AuthenticatedLayout>
            <Head title="Staff - Master Timetable" />
            <FlashAlert message={usePage().props.flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">All Staff</h5>
                        {canManage && (
                            <Button onClick={openCreate}>
                                <i className="bi bi-plus-lg me-1"></i>Add
                            </Button>
                        )}
                    </div>
                    <Row className="mb-3">
                        <Col md={3}>
                            <Select2
                                value={deptFilter}
                                onChange={(v) => setDeptFilter(v)}
                                options={departments.map((d) => ({
                                    value: d.id,
                                    label: d.name,
                                }))}
                                placeholder="All Departments"
                                isDisabled={isHod}
                            />
                        </Col>
                    </Row>
                    <DataTable data={filtered} columns={columns} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        {viewOnly ? "View Employee" : edit ? "Edit" : "Create"} Employee
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        <Row>
                            <Col md={6}>
                                <FormField
                                    name="emp_id"
                                    label="Emp ID"
                                    control={control}
                                    errors={errors}
                                    disabled={viewOnly}
                                    readOnly={
                                        !viewOnly && !!edit &&
                                        !["admin", "super_admin"].includes(
                                            user?.role,
                                        )
                                    }
                                />
                            </Col>
                            <Col md={6}>
                                <FormField
                                    name="name"
                                    label="Name"
                                    control={control}
                                    errors={errors}
                                    disabled={viewOnly}
                                />
                            </Col>
                        </Row>
                        <Row>
                            <Col md={4}>
                                <Select2Field
                                    name="designation"
                                    label="Designation"
                                    control={control}
                                    errors={errors}
                                    options={[
                                        { value: 'Assistant Professor', label: 'Assistant Professor' },
                                        { value: 'Associate Professor', label: 'Associate Professor' },
                                        { value: 'Head of Department', label: 'Head of Department' },
                                        { value: 'Lab Assistant', label: 'Lab Assistant' },
                                    ]}
                                    isClearable={false}
                                    isDisabled={viewOnly}
                                />
                            </Col>
                            <Col md={4}>
                                <Select2Field
                                    name="mode"
                                    label="Mode"
                                    control={control}
                                    errors={errors}
                                    options={modes.map((m) => ({
                                        value: m,
                                        label: m.charAt(0).toUpperCase() + m.slice(1),
                                    }))}
                                    isClearable={false}
                                    isDisabled={viewOnly}
                                />
                            </Col>
                            <Col md={4}>
                                <Select2Field
                                    name="department_id"
                                    label="Department"
                                    control={control}
                                    errors={errors}
                                    options={departments.map((d) => ({
                                        value: d.id,
                                        label: d.name,
                                    }))}
                                    isClearable={false}
                                    isDisabled={viewOnly}
                                />
                            </Col>
                            <Col md={4}>
                                <Select2Field
                                    name="role"
                                    label="Role"
                                    control={control}
                                    errors={errors}
                                    options={roles.map((r) => ({
                                        value: r,
                                        label: r,
                                    }))}
                                    isClearable={false}
                                    isDisabled={viewOnly}
                                />
                            </Col>
                        </Row>
                        {!edit && !viewOnly && (
                            <FormField
                                name="password"
                                label="Password"
                                type="password"
                                control={control}
                                errors={errors}
                            />
                        )}
                        {edit && !viewOnly && canManage && (
                            <Form.Group className="mb-3">
                                <Form.Label>
                                    <i className="bi bi-toggle-on me-1"></i> Account Status
                                </Form.Label>
                                <div className="d-flex gap-4 align-items-center pt-1">
                                    <Controller
                                        name="is_active"
                                        control={control}
                                        render={({ field }) => (
                                            <>
                                                <Form.Check
                                                    type="radio"
                                                    id="is_active_true"
                                                    label={
                                                        <span className="badge bg-success ms-1" style={{ fontSize: '13px' }}>
                                                            <i className="bi bi-check-circle me-1"></i>Active
                                                        </span>
                                                    }
                                                    checked={field.value === true || field.value === 'true'}
                                                    onChange={() => field.onChange(true)}
                                                />
                                                <Form.Check
                                                    type="radio"
                                                    id="is_active_false"
                                                    label={
                                                        <span className="badge bg-secondary ms-1" style={{ fontSize: '13px' }}>
                                                            <i className="bi bi-x-circle me-1"></i>Inactive
                                                        </span>
                                                    }
                                                    checked={field.value === false || field.value === 'false'}
                                                    onChange={() => field.onChange(false)}
                                                />
                                            </>
                                        )}
                                    />
                                </div>
                            </Form.Group>
                        )}
                    </Modal.Body>
                    <Modal.Footer>
                        {viewOnly ? (
                            <Button variant="secondary" onClick={() => setShow(false)}>
                                Close
                            </Button>
                        ) : (
                            <>
                                <Button variant="secondary" onClick={() => setShow(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit" variant="primary">
                                    Save
                                </Button>
                            </>
                        )}
                    </Modal.Footer>
                </Form>
            </Modal>
        </AuthenticatedLayout>
    );
}
