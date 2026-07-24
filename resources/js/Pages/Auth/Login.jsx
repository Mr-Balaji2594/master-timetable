import { useForm, Head } from '@inertiajs/react'
import { Alert, Button, Form } from 'react-bootstrap'
import Select2 from '../../Components/Select2'
import GuestLayout from '../../Layouts/Guest'

export default function Login({ departments, errors: serverErrors }) {
    const { data, setData, post, processing, errors } = useForm({
        emp_id: '',
        department_id: '',
        password: '',
    })

    const submit = (e) => {
        e.preventDefault()
        post('/login')
    }

    return (
        <GuestLayout>
            <Head title="Login - Master Timetable" />

            <div className="login-card">
                <div className="login-card-header">
                    <div className="login-logo">
                        <i className="bi bi-calendar-range"></i>
                    </div>
                    <h3>Welcome Back</h3>
                    <p className="login-subtitle">Sign in to your account</p>
                </div>

                {serverErrors?.emp_id && (
                    <Alert variant="danger">
                        <i className="bi bi-exclamation-circle me-2"></i>
                        {serverErrors.emp_id}
                    </Alert>
                )}

                <Form onSubmit={submit}>
                    <Form.Group className="mb-3">
                        <Form.Label>
                            <i className="bi bi-person-badge me-1"></i> Employee ID
                        </Form.Label>
                        <div className="input-group">
                            <span className="input-group-text">
                                <i className="bi bi-person"></i>
                            </span>
                            <Form.Control
                                type="text" name="emp_id" value={data.emp_id}
                                onChange={(e) => setData('emp_id', e.target.value)}
                                required placeholder="Enter Employee ID"
                                autoComplete="username"
                                isInvalid={!!errors.emp_id}
                            />
                            <Form.Control.Feedback type="invalid">{errors.emp_id}</Form.Control.Feedback>
                        </div>
                    </Form.Group>

                    <Form.Group className="mb-3">
                        <Form.Label>
                            <i className="bi bi-building me-1"></i> Department
                        </Form.Label>
                        <Select2 value={data.department_id} onChange={v => setData('department_id', v)}
                            options={departments.map(d => ({ value: d.id, label: d.name }))}
                            placeholder="Select Department" isClearable={false} />
                        {errors.department_id && <div className="invalid-feedback d-block">{errors.department_id}</div>}
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
                                type="password" name="password" value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                required placeholder="Enter Password"
                                autoComplete="current-password"
                                isInvalid={!!errors.password}
                            />
                            <Form.Control.Feedback type="invalid">{errors.password}</Form.Control.Feedback>
                        </div>
                    </Form.Group>

                    <Button type="submit" variant="primary" className="w-100 login-btn" disabled={processing}>
                        {processing ? (
                            <><span className="spinner-border spinner-border-sm me-2" role="status"></span>Signing in...</>
                        ) : (
                            <><i className="bi bi-box-arrow-in-right me-2"></i>Sign In</>
                        )}
                    </Button>
                </Form>

                <div className="login-footer">
                    <p>Master Timetable — College Timetable Management System</p>
                </div>
            </div>
        </GuestLayout>
    )
}
