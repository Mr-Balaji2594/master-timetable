import { Head, usePage, router } from "@inertiajs/react";
import { Card, Button, Modal, Form, Row, Col } from "react-bootstrap";
import { useState, useMemo, useEffect, useRef } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import Select2Field from "../../Components/Select2Field";
import FormField from "../../Components/FormField";
import DataTable from "../../Components/DataTable";
import FormErrors from "../../Components/FormErrors";
import FlashAlert from "../../Components/FlashAlert";
import { showConfirm } from "../../Helpers/sweetAlert";
import AuthenticatedLayout from "../../Layouts/Authenticated";

const schema = z.object({
    name: z.string().min(1, "Name is required"),
    department_id: z.string().min(1, "Department is required"),
    program_type: z.string().min(1, "Program type is required"),
    batch_year: z.string().optional(),
    year: z.string().optional(),
    block: z.string().optional(),
    floor: z.string().optional(),
});

// UG runs for 3 years (e.g. 2022-2025), PG for 2 years (e.g. 2022-2024).
const currentYear = new Date().getFullYear();

const buildBatchYearOptions = (programType) => {
    const duration = programType === "PG" ? 2 : 3;
    const opts = [];
    for (let start = currentYear - 4; start <= currentYear + 1; start++) {
        const range = `${start}-${start + duration}`;
        opts.push({ value: range, label: range });
    }
    return opts;
};

const buildAcademicYearOptions = (programType) => {
    const years = programType === "PG" ? ["I", "II"] : ["I", "II", "III"];
    return years.map((y) => ({ value: y, label: y }));
};

const programTypeOptions = ["UG", "PG"].map((p) => ({ value: p, label: p }));

const blockOptions = ["Main", "Old", "New"].map((b) => ({
    value: b,
    label: b,
}));

const floorOptions = ["Ground", "Floor 1", "Floor 2"].map((f) => ({
    value: f,
    label: f,
}));

export default function Index({ classes, departments }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const canManage = ["admin", "super_admin", "principal"].includes(
        user?.role,
    );
    const [show, setShow] = useState(false);
    const [edit, setEdit] = useState(null);
    const [viewOnly, setViewOnly] = useState(false);
    const {
        control,
        handleSubmit,
        watch,
        reset,
        setError,
        formState: { errors },
    } = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: "",
            department_id: "",
            program_type: "UG",
            batch_year: "",
            year: "",
            block: "",
            floor: "",
        },
    });

    const programType = watch("program_type");
    const batchYearOptions = useMemo(
        () => buildBatchYearOptions(programType),
        [programType],
    );
    const academicYearOptions = useMemo(
        () => buildAcademicYearOptions(programType),
        [programType],
    );

    const prevProgramType = useRef(programType);
    useEffect(() => {
        if (
            prevProgramType.current &&
            prevProgramType.current !== programType
        ) {
            reset((prev) => ({ ...prev, batch_year: "", year: "" }));
        }
        prevProgramType.current = programType;
    }, [programType, reset]);

    const defaults = {
        name: "",
        department_id: "",
        program_type: "UG",
        batch_year: "",
        year: "",
        block: "",
        floor: "",
    };

    const openCreate = () => {
        reset(defaults);
        setEdit(null);
        setViewOnly(false);
        setShow(true);
    };
    const openEdit = (c) => {
        reset({
            name: c.name,
            department_id: String(c.department_id ?? ""),
            program_type: c.program_type || "UG",
            batch_year: c.batch_year || "",
            year: c.year || "",
            block: c.block || "",
            floor: c.floor || "",
        });
        setEdit(c);
        setViewOnly(false);
        setShow(true);
    };
    const openView = (c) => {
        reset({
            name: c.name,
            department_id: String(c.department_id ?? ""),
            program_type: c.program_type || "UG",
            batch_year: c.batch_year || "",
            year: c.year || "",
            block: c.block || "",
            floor: c.floor || "",
        });
        setEdit(c);
        setViewOnly(true);
        setShow(true);
    };
    const submit = handleSubmit((formData) => {
        const payload = {
            ...formData,
            batch_year: formData.batch_year || null,
            year: formData.year || null,
            block: formData.block || null,
            floor: formData.floor || null,
        };
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
            ? router.put(`/classes/${edit.id}`, payload, {
                  onSuccess: done,
                  onError,
                  preserveState: true,
              })
            : router.post("/classes", payload, {
                  onSuccess: done,
                  onError,
                  preserveState: true,
              });
    });
    const handleDelete = async (cls) => {
        const result = await showConfirm(
            "Delete Class?",
            `Delete class ${cls.name}?`,
        );
        if (result.isConfirmed) router.delete(`/classes/${cls.id}`);
    };

    const columns = useMemo(
        () => [
            { header: "Name", accessorKey: "name" },
            { header: "Department", accessorKey: "department.name" },
            {
                header: "Program",
                accessorKey: "program_type",
                cell: ({ getValue }) =>
                    getValue() ? (
                        <span
                            className={`badge ${getValue() === "PG" ? "badge-pg" : "badge-ug"}`}
                        >
                            {getValue()}
                        </span>
                    ) : (
                        "—"
                    ),
            },
            { header: "Batch Year", accessorKey: "batch_year" },
            { header: "Academic Year", accessorKey: "year" },
            {
                header: "Block",
                accessorKey: "block",
                cell: ({ getValue }) =>
                    getValue() ? (
                        <span className="badge badge-location">
                            {getValue()}
                        </span>
                    ) : (
                        "—"
                    ),
            },
            {
                header: "Floor",
                accessorKey: "floor",
                cell: ({ getValue }) =>
                    getValue() ? (
                        <span className="badge badge-location">
                            {getValue()}
                        </span>
                    ) : (
                        "—"
                    ),
            },
            {
                header: "Actions",
                id: "actions",
                enableSorting: false,
                cell: ({ row }) => (
                    <>
                        {canManage && (
                            <Button
                                size="sm"
                                variant="outline-primary"
                                className="me-1"
                                onClick={() => openEdit(row.original)}
                            >
                                <i className="bi bi-pencil-square"></i>
                            </Button>
                        )}
                        {!canManage && (
                            <Button
                                size="sm"
                                variant="outline-info"
                                className="me-1"
                                onClick={() => openView(row.original)}
                            >
                                <i className="bi bi-eye"></i>
                            </Button>
                        )}
                        {canManage && (
                            <Button
                                size="sm"
                                variant="outline-danger"
                                onClick={() => handleDelete(row.original)}
                            >
                                <i className="bi bi-trash"></i>
                            </Button>
                        )}
                    </>
                ),
            },
        ],
        [canManage],
    );

    return (
        <AuthenticatedLayout>
            <Head title="Classes - Master Timetable" />
            <FlashAlert message={usePage().props.flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">All Classes</h5>
                        {canManage && (
                            <Button onClick={openCreate}>
                                <i className="bi bi-plus-lg me-1"></i>Add
                            </Button>
                        )}
                    </div>
                    <DataTable data={classes} columns={columns} searchable />
                </Card.Body>
            </Card>

            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>
                        {viewOnly ? "View Class" : edit ? "Edit" : "Create"}{" "}
                        Class
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={submit}>
                    <Modal.Body>
                        <FormErrors />
                        <FormField
                            name="name"
                            label="Name"
                            control={control}
                            errors={errors}
                            disabled={viewOnly}
                        />
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
                        <Row>
                            <Col md={6}>
                                <Select2Field
                                    name="program_type"
                                    label="Program Type"
                                    control={control}
                                    errors={errors}
                                    options={programTypeOptions}
                                    isClearable={false}
                                    isDisabled={viewOnly}
                                />
                            </Col>
                        </Row>
                        <Row>
                            <Col md={6}>
                                <Select2Field
                                    name="batch_year"
                                    label="Batch Year"
                                    control={control}
                                    errors={errors}
                                    options={batchYearOptions}
                                    isClearable={false}
                                    isDisabled={viewOnly}
                                />
                            </Col>
                            <Col md={6}>
                                <Select2Field
                                    name="year"
                                    label="Academic Year"
                                    control={control}
                                    errors={errors}
                                    options={academicYearOptions}
                                    isClearable={false}
                                    isDisabled={viewOnly}
                                />
                            </Col>
                        </Row>
                        <Row>
                            <Col md={6}>
                                <Select2Field
                                    name="block"
                                    label="Block"
                                    control={control}
                                    errors={errors}
                                    options={blockOptions}
                                    isClearable={false}
                                    isDisabled={viewOnly}
                                />
                            </Col>
                            <Col md={6}>
                                <Select2Field
                                    name="floor"
                                    label="Floor"
                                    control={control}
                                    errors={errors}
                                    options={floorOptions}
                                    isClearable={false}
                                    isDisabled={viewOnly}
                                />
                            </Col>
                        </Row>
                    </Modal.Body>
                    <Modal.Footer>
                        {viewOnly ? (
                            <Button
                                variant="secondary"
                                onClick={() => setShow(false)}
                            >
                                Close
                            </Button>
                        ) : (
                            <>
                                <Button
                                    variant="secondary"
                                    onClick={() => setShow(false)}
                                >
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
