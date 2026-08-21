import { Document, Page, Text, View, StyleSheet, Image } from '@react-pdf/renderer'

const HEADING_URL = typeof window !== 'undefined'
    ? `${window.location.origin}/images/heading.png`
    : '/images/heading.png'

const HEADER_BG = '#4f46e5'
const HEADER_BG2 = '#1e1b4b'
const ALT_ROW = '#eef2ff'
const BORDER_COLOR = '#c7d2fe'
const TEXT_PRIMARY = '#111827'
const TEXT_SECONDARY = '#6b7280'
const ACCENT = '#4f46e5'

const styles = StyleSheet.create({
    page: {
        padding: '35 30',
        fontSize: 8,
        fontFamily: 'Helvetica',
    },
    border: {
        position: 'absolute',
        top: 15,
        left: 15,
        right: 15,
        bottom: 15,
        borderWidth: 1.5,
        borderColor: '#c7d2fe',
        borderRadius: 6,
    },
    heading: {
        width: '88%',
        alignSelf: 'center',
        marginBottom: 12,
    },
    headerBar: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        backgroundColor: HEADER_BG2,
        padding: '14 20',
        marginBottom: 16,
        borderRadius: 6,
        borderLeftWidth: 5,
        borderLeftColor: '#f59e0b',
    },
    headerLeft: {},
    headerTitle: {
        fontSize: 16,
        fontWeight: 'bold',
        color: '#fff',
        letterSpacing: 0.5,
    },
    headerSub: {
        fontSize: 8,
        color: '#c7d2fe',
        marginTop: 2,
    },
    headerRight: {
        alignItems: 'flex-end',
    },
    headerStaff: {
        fontSize: 10,
        fontWeight: 'bold',
        color: '#fff',
    },
    headerStaffLabel: {
        fontSize: 7,
        color: '#c7d2fe',
    },
    infoSection: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginBottom: 16,
        borderBottomWidth: 1,
        borderBottomColor: '#e2e8f0',
        paddingBottom: 10,
    },
    infoItem: {
        flexDirection: 'column',
        paddingRight: 6,
    },
    infoLabel: {
        fontSize: 6.5,
        color: '#9ca3af',
        textTransform: 'uppercase',
        letterSpacing: 0.5,
        marginBottom: 2,
    },
    infoValue: {
        fontSize: 9,
        fontWeight: 'bold',
        color: TEXT_PRIMARY,
    },
    table: {
        width: '100%',
        borderStyle: 'solid',
        borderWidth: 1,
        borderColor: BORDER_COLOR,
        borderRadius: 4,
        overflow: 'hidden',
    },
    headerRow: {
        flexDirection: 'row',
        backgroundColor: HEADER_BG,
    },
    row: {
        flexDirection: 'row',
        borderBottomWidth: 1,
        borderBottomColor: '#e5e7eb',
    },
    altRow: {
        flexDirection: 'row',
        backgroundColor: ALT_ROW,
        borderBottomWidth: 1,
        borderBottomColor: '#e5e7eb',
    },
    dayCol: {
        width: '12%',
        padding: '8 4',
        borderRightWidth: 1,
        borderRightColor: BORDER_COLOR,
        fontWeight: 'bold',
        textAlign: 'center',
        justifyContent: 'center',
        fontSize: 10,
        color: TEXT_PRIMARY,
    },
    periodCol: {
        width: '17.6%',
        padding: '6 4',
        borderRightWidth: 1,
        borderRightColor: BORDER_COLOR,
        justifyContent: 'center',
    },
    lastPeriodCol: {
        width: '17.6%',
        padding: '6 4',
        justifyContent: 'center',
    },
    headerDayCell: {
        width: '12%',
        padding: '8 4',
        fontWeight: 'bold',
        textAlign: 'center',
        color: '#fff',
        fontSize: 10,
        borderRightWidth: 1,
        borderRightColor: '#6366f1',
    },
    headerPeriodCell: {
        width: '17.6%',
        padding: '8 4',
        fontWeight: 'bold',
        textAlign: 'center',
        color: '#fff',
        fontSize: 10,
        borderRightWidth: 1,
        borderRightColor: '#6366f1',
    },
    headerLastPeriodCell: {
        width: '17.6%',
        padding: '8 4',
        fontWeight: 'bold',
        textAlign: 'center',
        color: '#fff',
        fontSize: 10,
    },
    subject: {
        fontWeight: 'bold',
        fontSize: 7.5,
        color: ACCENT,
        marginBottom: 1,
    },
    classInfo: {
        fontSize: 6.5,
        color: TEXT_SECONDARY,
        marginBottom: 1,
    },
    teacher: {
        fontSize: 6.5,
        color: '#9ca3af',
    },
    empty: {
        color: '#cbd5e1',
        fontSize: 7,
        textAlign: 'center',
        marginTop: 6,
    },
    combinedBadge: {
        fontSize: 5.5,
        fontWeight: 'bold',
        color: '#ffffff',
        backgroundColor: '#f59e0b',
        textAlign: 'center',
        padding: '1 4',
        marginBottom: 3,
        borderRadius: 2,
    },
    combinedSlotItem: {
        marginBottom: 4,
        paddingBottom: 3,
        borderBottomWidth: 0.5,
        borderBottomColor: '#e5e7eb',
    },
    footer: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginTop: 16,
        paddingTop: 10,
        borderTopWidth: 1,
        borderTopColor: '#e5e7eb',
        fontSize: 6.5,
        color: '#9ca3af',
    },
    signatureSection: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginTop: 80,
        paddingHorizontal: 10,
    },
    signatureBlock: {
        alignItems: 'center',
        width: '30%',
    },
    signatureLine: {
        width: '100%',
        borderTopWidth: 1.5,
        borderTopColor: TEXT_PRIMARY,
        marginBottom: 6,
    },
    signatureLabel: {
        fontSize: 9,
        fontWeight: 'bold',
        color: TEXT_PRIMARY,
        textTransform: 'uppercase',
        letterSpacing: 1,
    },
    signatureSub: {
        fontSize: 7,
        color: TEXT_SECONDARY,
        marginTop: 2,
    },
})

const dayNames = ['I', 'II', 'III', 'IV', 'V', 'VI']

export default function TimetablePDF({ slots, employee, className }) {
    const cellMap = {}
    ;(slots || []).forEach(s => {
        const key = `${s.day_of_week}-${s.period_no}`
        if (!cellMap[key]) cellMap[key] = []
        cellMap[key].push(s)
    })
    const getCellSlots = (dow, pno) => cellMap[`${dow}-${pno}`] || []

    const generatedDate = new Date().toLocaleDateString('en-IN', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })

    const renderSlots = (cellSlots, pno) => {
        if (cellSlots.length === 0) {
            return <Text style={styles.empty}>—</Text>
        }
        const isCombined = cellSlots.length > 1 && cellSlots[0].combined_group_id
        return (
            <View>
                {isCombined && (
                    <Text style={styles.combinedBadge}>COMBINED</Text>
                )}
                {cellSlots.map((s, idx) => (
                    <View key={s.id} style={idx < cellSlots.length - 1 ? styles.combinedSlotItem : {}}>
                        <Text style={styles.subject}>{s.subject?.name} ({s.subject?.code})</Text>
                        <Text style={styles.classInfo}>{s.class?.dept_code} - {s.class?.name} - {s.class?.year}</Text>
                        {!isCombined && <Text style={styles.teacher}>{s.employee?.name}</Text>}
                    </View>
                ))}
            </View>
        )
    }

    return (
        <Document>
            <Page size="A4" orientation="portrait" style={styles.page}>
                <View style={styles.border} />

                <Image src={HEADING_URL} style={styles.heading} />

                <View style={styles.headerBar}>
                    <View style={styles.headerLeft}>
                        <Text style={styles.headerTitle}>Timetable</Text>
                        <Text style={styles.headerSub}>Master Timetable — College Management System</Text>
                    </View>
                    {employee && (
                        <View style={styles.headerRight}>
                            <Text style={styles.headerStaff}>{employee.name}</Text>
                            <Text style={styles.headerStaffLabel}>Staff Timetable</Text>
                        </View>
                    )}
                </View>

                <View style={styles.infoSection}>
                    {employee && (
                        <View style={styles.infoItem}>
                            <Text style={styles.infoLabel}>Staff</Text>
                            <Text style={styles.infoValue}>{employee.name}</Text>
                        </View>
                    )}
                    {employee?.emp_id && (
                        <View style={styles.infoItem}>
                            <Text style={styles.infoLabel}>Emp ID</Text>
                            <Text style={styles.infoValue}>{employee.emp_id}</Text>
                        </View>
                    )}
                    {employee?.designation && (
                        <View style={styles.infoItem}>
                            <Text style={styles.infoLabel}>Designation</Text>
                            <Text style={styles.infoValue}>{employee.designation}</Text>
                        </View>
                    )}
                    {employee?.department?.name && (
                        <View style={styles.infoItem}>
                            <Text style={styles.infoLabel}>Department</Text>
                            <Text style={styles.infoValue}>{employee.department.name}</Text>
                        </View>
                    )}
                    {className && (
                        <View style={styles.infoItem}>
                            <Text style={styles.infoLabel}>Class</Text>
                            <Text style={styles.infoValue}>{className}</Text>
                        </View>
                    )}
                    <View style={styles.infoItem}>
                        <Text style={styles.infoLabel}>Periods</Text>
                        <Text style={styles.infoValue}>1 – 5</Text>
                    </View>
                    <View style={styles.infoItem}>
                        <Text style={styles.infoLabel}>Days</Text>
                        <Text style={styles.infoValue}>I – VI</Text>
                    </View>
                </View>

                <View style={styles.table}>
                    <View style={styles.headerRow}>
                        <Text style={styles.headerDayCell}>Day</Text>
                        {[1, 2, 3, 4, 5].map((p, i) => (
                            <Text key={p} style={i === 4 ? styles.headerLastPeriodCell : styles.headerPeriodCell}>
                                Period {p}
                            </Text>
                        ))}
                    </View>
                    {dayNames.map((d, i) => {
                        const dow = i + 1
                        const rowStyle = i % 2 === 1 ? styles.altRow : styles.row
                        return (
                            <View key={dow} style={rowStyle}>
                                <View style={styles.dayCol}>
                                    <Text>{d}</Text>
                                </View>
                                {[1, 2, 3, 4, 5].map((pno, j) => {
                                    const colStyle = j === 4 ? styles.lastPeriodCol : styles.periodCol
                                    return (
                                        <View key={pno} style={colStyle}>
                                            {renderSlots(getCellSlots(dow, pno), pno)}
                                        </View>
                                    )
                                })}
                            </View>
                        )
                    })}
                </View>

                <View style={styles.signatureSection}>
                    <View style={styles.signatureBlock}>
                        <View style={styles.signatureLine} />
                        <Text style={styles.signatureLabel}>Staff</Text>
                        <Text style={styles.signatureSub}>Subject Teacher</Text>
                    </View>
                    <View style={styles.signatureBlock}>
                        <View style={styles.signatureLine} />
                        <Text style={styles.signatureLabel}>HOD</Text>
                        <Text style={styles.signatureSub}>Head of Department</Text>
                    </View>
                    <View style={styles.signatureBlock}>
                        <View style={styles.signatureLine} />
                        <Text style={styles.signatureLabel}>Principal</Text>
                        <Text style={styles.signatureSub}>Principal</Text>
                    </View>
                </View>

                <View style={styles.footer}>
                    <Text>Generated: {generatedDate}</Text>
                    <Text>Page 1</Text>
                </View>
            </Page>
        </Document>
    )
}