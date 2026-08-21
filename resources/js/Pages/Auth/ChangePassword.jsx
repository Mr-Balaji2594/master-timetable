import { Head, useForm, usePage, router } from '@inertiajs/react'
import { useState } from 'react'
import { Card, Button, Form } from 'react-bootstrap'
import FlashAlert from '../../Components/FlashAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

function FlashAlerts() {
    const { flash } = usePage().props
    return (
        <>
            <FlashAlert message={flash?.success} />
            {flash?.error && (
                <div className="alert alert-danger d-flex align-items-start gap-2 mb-4" style={{ fontSize: '14px', borderRadius: '10px' }}>
                    <i className="bi bi-shield-exclamation fs-5 flex-shrink-0"></i>
                    <div>{flash.error.message || flash.error}</div>
                </div>
            )}
        </>
    )
}

function evaluateStrength(password) {
    let score = 0
    if (!password) return { score: 0, label: '', color: '' }
    if (password.length >= 8) score++
    if (password.length >= 12) score++
    if (/[A-Za-z]/.test(password) && /[0-9]/.test(password)) score++
    if (/[^A-Za-z0-9\s]/.test(password)) score++

    const levels = [
        { label: 'Weak', color: '#dc2626', width: '25%' },
        { label: 'Fair', color: '#f59e0b', width: '50%' },
        { label: 'Good', color: '#10b981', width: '75%' },
        { label: 'Strong', color: '#059669', width: '100%' },
    ]
    const level = levels[Math.min(score, 4) - 1] || levels[0]
    return { score, ...level }
}

const requirements = [
    { key: 'length', label: 'At least 8 characters', test: (v) => v.length >= 8 },
    { key: 'letter', label: 'At least one letter', test: (v) => /[A-Za-z]/.test(v) },
    { key: 'number', label: 'At least one number', test: (v) => /[0-9]/.test(v) },
    { key: 'special', label: 'At least one special character (!@#$%^&*)', test: (v) => /[^A-Za-z0-9\s]/.test(v) },
]

export default function ChangePassword() {
    const { data, setData, post, processing, errors } = useForm({
        current_password: '', new_password: '', new_password_confirmation: ''
    })
    const [showCurrent, setShowCurrent] = useState(false)
    const [showNew, setShowNew] = useState(false)
    const [showConfirm, setShowConfirm] = useState(false)

    const mustChange = usePage().props.auth?.user?.must_change_password

    const strength = evaluateStrength(data.new_password)

    const submit = (e) => {
        e.preventDefault()
        post('/change-password')
    }

    const passwordToggle = (shown, setShown) => (
        <button
            type="button"
            className="btn btn-outline-secondary"
            onClick={() => setShown(!shown)}
            tabIndex={-1}
            aria-label={shown ? 'Hide password' : 'Show password'}
        >
            <i className={`bi ${shown ? 'bi-eye-slash' : 'bi-eye'}`}></i>
        </button>
    )

    return (
        <AuthenticatedLayout>
            <Head title="Change Password - Master Timetable" />
            <FlashAlerts />

            <div className="row justify-content-center">
                <div className="col-md-6">
                    <Card>
                        <Card.Body>
                            {mustChange && (
                                <div className="alert alert-warning d-flex align-items-start gap-2" style={{ fontSize: '14px', borderRadius: '10px' }}>
                                    <i className="bi bi-shield-exclamation fs-5 flex-shrink-0"></i>
                                    <div>
                                        <div className="fw-semibold mb-1">Password Change Required</div>
                                        For security, you must set a new strong password before continuing.
                                    </div>
                                </div>
                            )}
                            <h5 className="mb-3">Change Password</h5>
                            <Form onSubmit={submit}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Current Password</Form.Label>
                                    <div className="input-group">
                                        <Form.Control type={showCurrent ? 'text' : 'password'} value={data.current_password}
                                            onChange={e => setData('current_password', e.target.value)} required
                                            autoComplete="current-password"
                                            isInvalid={!!errors.current_password} />
                                        {passwordToggle(showCurrent, setShowCurrent)}
                                        <Form.Control.Feedback type="invalid">{errors.current_password}</Form.Control.Feedback>
                                    </div>
                                </Form.Group>
                                <Form.Group className="mb-2">
                                    <Form.Label>New Password</Form.Label>
                                    <div className="input-group">
                                        <Form.Control type={showNew ? 'text' : 'password'} value={data.new_password}
                                            onChange={e => setData('new_password', e.target.value)} required
                                            autoComplete="new-password"
                                            isInvalid={!!errors.new_password} />
                                        {passwordToggle(showNew, setShowNew)}
                                        <Form.Control.Feedback type="invalid">{errors.new_password}</Form.Control.Feedback>
                                    </div>
                                </Form.Group>

                                {data.new_password && (
                                    <div className="password-strength mb-3">
                                        <div className="d-flex justify-content-between align-items-center mb-1">
                                            <span className="password-strength-label">Password strength</span>
                                            <span className="password-strength-text" style={{ color: strength.color }}>{strength.label}</span>
                                        </div>
                                        <div className="password-strength-bar">
                                            <div className="password-strength-fill" style={{ width: strength.width, background: strength.color }}></div>
                                        </div>
                                        <ul className="password-requirements">
                                            {requirements.map((req) => {
                                                const ok = req.test(data.new_password)
                                                return (
                                                    <li key={req.key} className={ok ? 'met' : ''}>
                                                        <i className={`bi ${ok ? 'bi-check-circle-fill' : 'bi-circle'} me-1`}></i>
                                                        {req.label}
                                                    </li>
                                                )
                                            })}
                                        </ul>
                                    </div>
                                )}

                                <Form.Group className="mb-3">
                                    <Form.Label>Confirm New Password</Form.Label>
                                    <div className="input-group">
                                        <Form.Control type={showConfirm ? 'text' : 'password'} value={data.new_password_confirmation}
                                            onChange={e => setData('new_password_confirmation', e.target.value)} required
                                            autoComplete="new-password"
                                            isInvalid={!!errors.new_password_confirmation} />
                                        {passwordToggle(showConfirm, setShowConfirm)}
                                        <Form.Control.Feedback type="invalid">{errors.new_password_confirmation}</Form.Control.Feedback>
                                    </div>
                                </Form.Group>
                                <Button type="submit" variant="primary" disabled={processing}>
                                    <i className="bi bi-key me-1"></i>Change Password
                                </Button>
                            </Form>
                        </Card.Body>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    )
}