import { useForm, Head, usePage } from "@inertiajs/react";
import { useState } from "react";
import { Alert, Button, Form } from "react-bootstrap";
import Select2 from "../../Components/Select2";
import GuestLayout from "../../Layouts/Guest";

export default function Login({ departments }) {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        emp_id: "",
        department_id: "",
        password: "",
        remember: false,
    });
    const [showPassword, setShowPassword] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        post("/login");
    };

    // Detect deactivation-specific error to style differently
    const isDeactivated = errors.emp_id?.toLowerCase().includes("deactivated");
    // Detect lockout-specific error to show a countdown-style message
    const isLocked = errors.emp_id
        ?.toLowerCase()
        .includes("too many failed attempts");

    return (
        <GuestLayout>
            <Head title="Login - Master Timetable" />

            <div className="login-card">
                <div className="login-card-header">
                    <div className="login-logo">
                        <img src="/images/favicon.png" alt="Master Timetable Logo" className="login-logo-img" />
                    </div>
                    <h3>Welcome Back</h3>
                    <p className="login-subtitle">Sign in to your account</p>
                </div>

                {flash?.error && (
                    <Alert
                        variant="danger"
                        className="d-flex align-items-start gap-2 py-2 px-3"
                        style={{ fontSize: "14px", borderRadius: "10px" }}
                    >
                        <i className="bi bi-shield-exclamation fs-5 flex-shrink-0 mt-1"></i>
                        <div>{flash.error.message || flash.error}</div>
                    </Alert>
                )}

                {errors.emp_id && (
                    <Alert
                        variant={
                            isDeactivated
                                ? "warning"
                                : isLocked
                                  ? "danger"
                                  : "danger"
                        }
                        className="d-flex align-items-start gap-2 py-2 px-3"
                        style={{ fontSize: "14px", borderRadius: "10px" }}
                    >
                        <i
                            className={`bi ${isDeactivated ? "bi-person-slash" : isLocked ? "bi-shield-lock" : "bi-exclamation-circle"} fs-5 flex-shrink-0 mt-1`}
                        ></i>
                        <div>
                            {isDeactivated && (
                                <div className="fw-semibold mb-1">
                                    Account Deactivated
                                </div>
                            )}
                            {isLocked && (
                                <div className="fw-semibold mb-1">
                                    Account Temporarily Locked
                                </div>
                            )}
                            {errors.emp_id}
                        </div>
                    </Alert>
                )}

                <Form onSubmit={submit}>
                    <Form.Group className="mb-3">
                        <Form.Label>
                            <i className="bi bi-person-badge me-1"></i> Employee
                            ID
                        </Form.Label>
                        <div className="input-group">
                            <span className="input-group-text">
                                <i className="bi bi-person"></i>
                            </span>
                            <Form.Control
                                type="text"
                                name="emp_id"
                                value={data.emp_id}
                                onChange={(e) =>
                                    setData("emp_id", e.target.value)
                                }
                                required
                                placeholder="Enter Employee ID"
                                autoComplete="username"
                                isInvalid={!!errors.emp_id && !isDeactivated}
                            />
                            {!isDeactivated && (
                                <Form.Control.Feedback type="invalid">
                                    {errors.emp_id}
                                </Form.Control.Feedback>
                            )}
                        </div>
                    </Form.Group>

                    <Form.Group className="mb-3">
                        <Form.Label>
                            <i className="bi bi-building me-1"></i> Department
                        </Form.Label>
                        <Select2
                            value={data.department_id}
                            onChange={(v) => setData("department_id", v)}
                            options={departments.map((d) => ({
                                value: d.id,
                                label: d.name,
                            }))}
                            placeholder="Select Department"
                            isClearable={false}
                        />
                        {errors.department_id && (
                            <div className="invalid-feedback d-block">
                                {errors.department_id}
                            </div>
                        )}
                    </Form.Group>

                    <Form.Group className="mb-4">
                        <Form.Label>
                            <i className="bi bi-lock me-1"></i> Password
                        </Form.Label>
                        <div className="input-group">
                            <span className="input-group-text">
                                <i className="bi bi-key"></i>
                            </span>
                            <Form.Control
                                type={showPassword ? "text" : "password"}
                                name="password"
                                value={data.password}
                                onChange={(e) =>
                                    setData("password", e.target.value)
                                }
                                required
                                placeholder="Enter Password"
                                autoComplete="current-password"
                                isInvalid={!!errors.password}
                            />
                            <button
                                type="button"
                                className="btn btn-outline-secondary"
                                onClick={() => setShowPassword(!showPassword)}
                                tabIndex={-1}
                                aria-label={
                                    showPassword
                                        ? "Hide password"
                                        : "Show password"
                                }
                            >
                                <i
                                    className={`bi ${showPassword ? "bi-eye-slash" : "bi-eye"}`}
                                ></i>
                            </button>
                            <Form.Control.Feedback type="invalid">
                                {errors.password}
                            </Form.Control.Feedback>
                        </div>
                    </Form.Group>

                    <div className="d-flex justify-content-between align-items-center mb-4">
                        <Form.Check
                            type="checkbox"
                            id="remember-me"
                            label="Remember me"
                            checked={data.remember}
                            onChange={(e) =>
                                setData("remember", e.target.checked)
                            }
                        />
                    </div>

                    <Button
                        type="submit"
                        variant="primary"
                        className="w-100 login-btn"
                        disabled={processing}
                    >
                        {processing ? (
                            <>
                                <span
                                    className="spinner-border spinner-border-sm me-2"
                                    role="status"
                                ></span>
                                Signing in...
                            </>
                        ) : (
                            <>
                                <i className="bi bi-box-arrow-in-right me-2"></i>
                                Sign In
                            </>
                        )}
                    </Button>
                </Form>

                <div className="login-footer">
                    <p>
                        Master Timetable — College Timetable Management System
                    </p>
                    <p className="login-security">
                        <i className="bi bi-shield-lock me-1"></i>
                        Protected access — accounts lock after 5 failed
                        attempts.
                    </p>
                </div>
            </div>
        </GuestLayout>
    );
}
