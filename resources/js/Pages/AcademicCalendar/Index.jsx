import { Head, router } from '@inertiajs/react'
import { Card, Row, Col } from 'react-bootstrap'
import { useMemo, useRef } from 'react'
import FullCalendar from '@fullcalendar/react'
import dayGridPlugin from '@fullcalendar/daygrid'
import listPlugin from '@fullcalendar/list'
import interactionPlugin from '@fullcalendar/interaction'
import Select2 from '../../Components/Select2'
import AuthenticatedLayout from '../../Layouts/Authenticated'

const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']

const isMobile = () => typeof window !== 'undefined' && window.innerWidth <= 768

export default function Index({ years, academicYear, session, calendar }) {
    const calRef = useRef(null)

    const events = useMemo(() => {
        return (calendar || []).flatMap(d => {
            const hasRemark = d.remarks && !/^(sunday|saturday)$/i.test(d.remarks)
            const out = []
            if (d.is_working_day) {
                out.push({
                    start: d.calendar_date,
                    allDay: true,
                    title: d.day_order ? `Working Day ${d.day_order}` : 'Working Day',
                    color: '#16a34a',
                    textColor: '#fff',
                    extendedProps: { ...d, label: 'working' },
                })
            }
            if (hasRemark) {
                out.push({
                    start: d.calendar_date,
                    allDay: true,
                    title: d.remarks,
                    color: '#f59e0b',
                    textColor: '#431407',
                    extendedProps: { ...d, label: 'remark' },
                })
            }
            if (!d.is_working_day && !hasRemark) {
                out.push({
                    start: d.calendar_date,
                    allDay: true,
                    title: 'Non-working',
                    color: '#fca5a5',
                    textColor: '#7f1d1d',
                    extendedProps: { ...d, label: 'nonworking' },
                })
            }
            return out
        })
    }, [calendar])

    const firstDate = calendar?.[0]?.calendar_date
    const lastDate = calendar?.[calendar.length - 1]?.calendar_date

    const firstMonth = firstDate ? Number(firstDate.slice(5, 7)) : 1
    const firstYear = firstDate ? Number(firstDate.slice(0, 4)) : new Date().getFullYear()
    const lastMonth = lastDate ? Number(lastDate.slice(5, 7)) : 12
    const lastYear = lastDate ? Number(lastDate.slice(0, 4)) : new Date().getFullYear()

    const monthOptions = useMemo(() => {
        const options = []
        let y = firstYear
        let m = firstMonth
        while (y < lastYear || (y === lastYear && m <= lastMonth)) {
            options.push({ value: `${y}-${String(m).padStart(2, '0')}`, label: `${MONTH_NAMES[m - 1]} ${y}` })
            m++
            if (m > 12) { m = 1; y++ }
        }
        return options
    }, [firstYear, firstMonth, lastYear, lastMonth])

    const today = new Date()
    const todayYm = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`
    const inRange = monthOptions.some(o => o.value === todayYm)
    const openYm = inRange ? todayYm : `${String(firstYear).padStart(4, '0')}-${String(firstMonth).padStart(2, '0')}`

    const applyFilters = (key, value) => {
        const data = { academic_year: academicYear, session }
        if (key) data[key] = value || ''
        router.get('/academic-calendar', data, { preserveState: true, replace: true })
    }

    const goToMonth = (ym) => {
        const [y, m] = String(ym).split('-').map(Number)
        const api = calRef.current?.getApi()
        if (api) api.gotoDate(new Date(y, m - 1, 1))
    }

    const summary = useMemo(() => {
        let working = 0, nonWorking = 0, holidays = 0
        ;(calendar || []).forEach(d => {
            if (d.is_working_day) working++
            else {
                nonWorking++
                if (d.remarks && !/sunday/i.test(d.remarks || '')) holidays++
            }
        })
        return { working, nonWorking, holidays }
    }, [calendar])

    return (
        <AuthenticatedLayout>
            <Head title="Academic Calendar - Master Timetable" />

            <Card className="border-0 shadow-sm">
                <Card.Body>
                    <div className="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h5 className="mb-1">
                                <i className="bi bi-calendar2-week me-2"></i>Academic Calendar
                            </h5>
                            <span className="text-muted small">
                                {academicYear ? `${academicYear} — ${session}` : 'No calendar data available'}
                            </span>
                        </div>
                    </div>

                    <Row className="mb-3 g-2 no-print">
                        <Col md={3}>
                            <Select2
                                value={academicYear || ''}
                                onChange={v => applyFilters('academic_year', v)}
                                options={[...new Set((years || []).map(y => y.academic_year))].map(y => ({ value: y, label: y }))}
                                placeholder="Academic Year"
                            />
                        </Col>
                        <Col md={3}>
                            <Select2
                                value={session || ''}
                                onChange={v => applyFilters('session', v)}
                                options={[...new Set((years || []).map(y => y.session))].map(s => ({ value: s, label: s }))}
                                placeholder="Session"
                            />
                        </Col>
                        <Col md={3}>
                            <Select2
                                value={openYm}
                                onChange={v => goToMonth(v)}
                                options={monthOptions}
                                placeholder="Jump to Month"
                            />
                        </Col>
                        <Col md={3}>
                            <div className="d-flex gap-3 align-items-center h-100" style={{ fontSize: '12.5px' }}>
                                <span><span className="ac-cal-dot ac-working"></span> Working day</span>
                                <span><span className="ac-cal-dot ac-nonworking"></span> Non-working</span>
                                <span><span className="ac-cal-dot ac-holiday"></span> Holiday / event</span>
                            </div>
                        </Col>
                    </Row>

                    <Row className="mb-3 g-3">
                        <Col md={4}>
                            <Card className="ac-summary-card border-0">
                                <Card.Body className="d-flex align-items-center gap-3 py-3">
                                    <div className="ac-summary-icon ac-working"><i className="bi bi-check2-circle"></i></div>
                                    <div>
                                        <div className="text-muted small">Working Days</div>
                                        <div className="ac-summary-value">{summary.working}</div>
                                    </div>
                                </Card.Body>
                            </Card>
                        </Col>
                        <Col xs={6} md={4}>
                            <Card className="ac-summary-card border-0">
                                <Card.Body className="d-flex align-items-center gap-3 py-3">
                                    <div className="ac-summary-icon ac-nonworking"><i className="bi bi-slash-circle"></i></div>
                                    <div>
                                        <div className="text-muted small">Non-Working Days</div>
                                        <div className="ac-summary-value">{summary.nonWorking}</div>
                                    </div>
                                </Card.Body>
                            </Card>
                        </Col>
                        <Col xs={6} md={4}>
                            <Card className="ac-summary-card border-0">
                                <Card.Body className="d-flex align-items-center gap-3 py-3">
                                    <div className="ac-summary-icon ac-holiday"><i className="bi bi-stars"></i></div>
                                    <div>
                                        <div className="text-muted small">Holidays / Events</div>
                                        <div className="ac-summary-value">{summary.holidays}</div>
                                    </div>
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>

                    <div className="ac-fullcalendar">
                        <FullCalendar
                            ref={calRef}
                            plugins={[dayGridPlugin, listPlugin, interactionPlugin]}
                            initialView={isMobile() ? 'listMonth' : 'dayGridMonth'}
                            initialDate={`${openYm}-01`}
                            events={events}
                            height="auto"
                            headerToolbar={{
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,listMonth',
                            }}
                            buttonText={{ today: 'Today', month: 'Month', list: 'List' }}
                            dayMaxEvents={2}
                            eventDisplay="block"
                            eventDidMount={(info) => {
                                const r = info.event.extendedProps?.remarks
                                if (r) info.el.title = r
                            }}
                        />
                    </div>
                </Card.Body>
            </Card>

            <style>{`
                .ac-fullcalendar .fc { font-size: 13px; }
                .ac-fullcalendar .fc .fc-toolbar-title { font-size: 17px; font-weight: 700; color: #1e293b; }
                .ac-fullcalendar .fc .fc-button-primary { background: #4f46e5; border-color: #4f46e5; }
                .ac-fullcalendar .fc .fc-button-primary:not(:disabled):hover { background: #4338ca; border-color: #4338ca; }
                .ac-fullcalendar .fc .fc-button-primary.fc-button-active { background: #4338ca; border-color: #4338ca; }
                .ac-fullcalendar .fc .fc-daygrid-day.fc-day-today { background: #eef2ff; }
                .ac-fullcalendar .fc .fc-daygrid-event { border-radius: 4px; padding: 1px 4px; font-size: 11px; cursor: default; }
                .ac-fullcalendar .fc .fc-event-title { font-weight: 600; }
                .ac-summary-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
                .ac-summary-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #fff; flex-shrink: 0; }
                .ac-summary-icon.ac-working { background: #16a34a; }
                .ac-summary-icon.ac-nonworking { background: #dc2626; }
                .ac-summary-icon.ac-holiday { background: #f59e0b; }
                .ac-summary-value { font-size: 22px; font-weight: 800; color: #1e293b; }
                .ac-cal-dot { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 4px; vertical-align: middle; }
                .ac-cal-dot.ac-working { background: #16a34a; }
                .ac-cal-dot.ac-nonworking { background: #fca5a5; }
                .ac-cal-dot.ac-holiday { background: #f59e0b; }
                .ac-fullcalendar { overflow-x: auto; }
                .ac-fullcalendar .fc { min-width: 560px; }
                @media (max-width: 768px) {
                    .ac-summary-icon { width: 36px; height: 36px; font-size: 16px; border-radius: 8px; }
                    .ac-summary-value { font-size: 18px; }
                    .ac-summary-card .text-muted { font-size: 11.5px; }
                    .ac-fullcalendar { overflow-x: auto; -webkit-overflow-scrolling: touch; }
                    .ac-fullcalendar .fc { min-width: 520px; font-size: 12px; }
                    .ac-fullcalendar .fc .fc-toolbar { flex-wrap: wrap; gap: 6px; }
                    .ac-fullcalendar .fc .fc-toolbar-title { font-size: 14px; }
                    .ac-fullcalendar .fc .fc-button { font-size: 11px; padding: 4px 8px; }
                    .ac-fullcalendar .fc .fc-daygrid-day-frame { min-height: 70px; }
                    .ac-fullcalendar .fc .fc-daygrid-event { font-size: 10px; padding: 0 3px; }
                }
            `}</style>
        </AuthenticatedLayout>
    )
}