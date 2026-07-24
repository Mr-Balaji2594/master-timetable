import { Head, usePage, router } from '@inertiajs/react'
import { Card, Button, ProgressBar } from 'react-bootstrap'
import { useMemo } from 'react'
import DataTable from '../../Components/DataTable'
import FlashAlert from '../../Components/FlashAlert'
import AuthenticatedLayout from '../../Layouts/Authenticated'

export default function Index({ workloads }) {
    const { auth } = usePage().props
    const user = auth?.user
    const canCalculate = ['admin', 'super_admin', 'principal'].includes(user?.role)
    const maxLoad = 24

    const calculate = () => router.post('/workload/calculate')

    const columns = useMemo(() => [
        { header: 'Employee', accessorKey: 'employee.name' },
        { header: 'Department', accessorKey: 'employee.department.name' },
        { header: 'Total Hours', accessorKey: 'total_hours' },
        { header: 'Max Load', cell: () => maxLoad },
        { header: '% Load', id: 'pct', enableSorting: true, accessorFn: row => Math.min(100, Math.round((row.total_hours / maxLoad) * 100)), cell: ({ getValue }) => `${getValue()}%` },
        { header: 'Progress', id: 'progress', enableSorting: false, cell: ({ row }) => {
            const pct = Math.min(100, Math.round((row.original.total_hours / maxLoad) * 100))
            const variant = pct > 90 ? 'danger' : pct > 70 ? 'warning' : 'success'
            return <ProgressBar now={pct} variant={variant} label={pct > 15 ? `${pct}%` : ''} style={{ minWidth: 150 }} />
        }},
    ], [])

    return (
        <AuthenticatedLayout>
            <Head title="Workload - Master Timetable" />
            <FlashAlert message={usePage().props.flash?.success} />

            <Card>
                <Card.Body>
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="mb-0">Staff Workload</h5>
                        {canCalculate && <Button onClick={calculate}><i className="bi bi-calculator me-1"></i>Calculate</Button>}
                    </div>
                    <DataTable data={workloads} columns={columns} searchable />
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    )
}
