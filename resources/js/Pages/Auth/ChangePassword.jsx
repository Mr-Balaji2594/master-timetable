import { Head, useForm, usePage, router } from '@inertiajs/react'
import { Card, Button, Form } from 'react-bootstrap'
import FlashAlert from '../../Components/FlashAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

export default function ChangePassword() {
    const { data, setData, post, processing, errors } = useForm({
        current_password: '', new_password: '', new_password_confirmation: ''
    })

    const submit = (e) => {
        e.preventDefault()
        post('/change-password')
    }

    return (
        <AuthenticatedLayout>
            <Head title="Change Password - Master Timetable" />
            <FlashAlert message={usePage().props.flash?.success} />

            <div className="row justify-content-center">
                <div className="col-md-6">
                    <Card>
                        <Card.Body>
                            <h5 className="mb-3">Change Password</h5>
                            <Form onSubmit={submit}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Current Password</Form.Label>
                                    <Form.Control type="password" value={data.current_password}
                                        onChange={e => setData('current_password', e.target.value)} required
                                        isInvalid={!!errors.current_password} />
                                    <Form.Control.Feedback type="invalid">{errors.current_password}</Form.Control.Feedback>
                                </Form.Group>
                                <Form.Group className="mb-3">
                                    <Form.Label>New Password</Form.Label>
                                    <Form.Control type="password" value={data.new_password}
                                        onChange={e => setData('new_password', e.target.value)} required
                                        isInvalid={!!errors.new_password} />
                                    <Form.Control.Feedback type="invalid">{errors.new_password}</Form.Control.Feedback>
                                </Form.Group>
                                <Form.Group className="mb-3">
                                    <Form.Label>Confirm New Password</Form.Label>
                                    <Form.Control type="password" value={data.new_password_confirmation}
                                        onChange={e => setData('new_password_confirmation', e.target.value)} required
                                        isInvalid={!!errors.new_password_confirmation} />
                                    <Form.Control.Feedback type="invalid">{errors.new_password_confirmation}</Form.Control.Feedback>
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
