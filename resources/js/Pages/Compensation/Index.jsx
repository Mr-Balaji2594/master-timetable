import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, Badge } from 'react-bootstrap'
import DataTable from '../../Components/DataTable'
import FlashAlert from '../../Components/FlashAlert'
import { showConfirmCustom } from '../../Helpers/sweetAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const statusColors = { pending: 'warning', completed: 'info', approved: 'success', cancelled: 'danger' }

const dayNames = ['I', 'II', 'III', 'IV', 'V', 'VI']

export default function Index({ compensations }) {
    const { auth, flash } = usePage().props
    const user = auth?.user

    const confirm = (opts) => showConfirmCustom({
        title: opts.title,
        text: opts.text,
        confirmText: opts.confirmText || 'Yes',
        confirmColor: opts.color || '#198754'
    })

    return (
        <AuthenticatedLayout>
            <Head title="Compensations - Master Timetable" />
            <FlashAlert message={flash?.success} />
            <FlashAlert message={flash?.error} variant="danger" />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">Compensations</h5>
                    </div>
                    <DataTable data={compensations} columns={[
                        { header: 'Original Staff', accessorKey: 'original_employee.name' },
                        { header: 'Substitute', accessorKey: 'substitute_employee.name' },
                        { header: 'Class', accessorKey: 'class.name' },
                        { header: 'Subject', accessorKey: 'subject.name' },
                        { header: 'Day/Period', cell: ({ row }) => `${dayNames[row.original.day_of_week - 1] || row.original.day_of_week}/${row.original.period_no}` },
                        { header: 'Leave Date', accessorKey: 'leave_date' },
                        { header: 'Status', accessorKey: 'status', cell: ({ getValue }) => {
                            const s = getValue() || 'pending'
                            return <Badge bg={statusColors[s] || 'secondary'}>{s.charAt(0).toUpperCase() + s.slice(1)}</Badge>
                        }},
                        { header: 'Actions', id: 'actions', enableSorting: false, cell: ({ row }) => {
                            const c = row.original
                            const isSubstitute = String(c.substitute_employee_id) === String(user?.id)
                            const canManage = ['admin', 'super_admin', 'principal', 'hod'].includes(user?.role)
                            return (
                                <>
                                    {c.status === 'pending' && isSubstitute && (
                                        <Button size="sm" variant="outline-success" className="me-1" onClick={async () => {
                                            const r = await confirm({ title: 'Complete Compensation?', text: 'Confirm that the compensation duty has been completed?', confirmText: 'Complete', color: '#198754' })
                                            if (r.isConfirmed) router.post(`/compensations/${c.id}/complete`)
                                        }}>
                                            <i className="bi bi-check"></i> Complete
                                        </Button>
                                    )}
                                    {c.status === 'completed' && canManage && (
                                        <Button size="sm" variant="outline-primary" className="me-1" onClick={async () => {
                                            const r = await confirm({ title: 'Final Approve Compensation?', text: 'Give final approval for this compensation?', confirmText: 'Approve', color: '#0d6efd' })
                                            if (r.isConfirmed) router.post(`/compensations/${c.id}/approve`)
                                        }}>
                                            <i className="bi bi-check-all"></i> Approve
                                        </Button>
                                    )}
                                    {['pending', 'completed'].includes(c.status) && canManage && (
                                        <Button size="sm" variant="outline-danger" onClick={async () => {
                                            const r = await confirm({ title: 'Cancel Compensation?', text: 'Cancel this compensation?', confirmText: 'Cancel', color: '#dc3545' })
                                            if (r.isConfirmed) router.post(`/compensations/${c.id}/cancel`)
                                        }}>
                                            <i className="bi bi-x"></i> Cancel
                                        </Button>
                                    )}
                                </>
                            )
                        }},
                    ]} searchable />
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    )
}