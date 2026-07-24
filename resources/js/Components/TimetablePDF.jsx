import { Document, Page, Text, View, StyleSheet } from '@react-pdf/renderer'

const HEADER_BG = '#1a1a2e'
const ALT_ROW = '#f3f4f6'

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
        borderColor: '#cbd5e1',
        borderRadius: 6,
    },
    headerBar: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        backgroundColor: HEADER_BG,
        padding: '14 20',
        marginBottom: 18,
        borderRadius: 4,
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
        color: '#94a3b8',
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
        color: '#94a3b8',
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
    },
    infoLabel: {
        fontSize: 6.5,
        color: '#94a3b8',
        textTransform: 'uppercase',
        letterSpacing: 0.5,
        marginBottom: 2,
    },
    infoValue: {
        fontSize: 9,
        fontWeight: 'bold',
        color: '#1e293b',
    },
    table: {
        width: '100%',
        borderStyle: 'solid',
        borderWidth: 1,
        borderColor: '#cbd5e1',
    },
    headerRow: {
        flexDirection: 'row',
        backgroundColor: HEADER_BG,
    },
    row: {
        flexDirection: 'row',
        borderBottomWidth: 1,
        borderBottomColor: '#e2e8f0',
    },
    altRow: {
        flexDirection: 'row',
        backgroundColor: ALT_ROW,
        borderBottomWidth: 1,
        borderBottomColor: '#e2e8f0',
    },
    dayCol: {
        width: '12%',
        padding: '8 4',
        borderRightWidth: 1,
        borderRightColor: '#cbd5e1',
        fontWeight: 'bold',
        textAlign: 'center',
        justifyContent: 'center',
        fontSize: 10,
    },
    periodCol: {
        width: '17.6%',
        padding: '6 4',
        borderRightWidth: 1,
        borderRightColor: '#cbd5e1',
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
        borderRightColor: '#2d2d4e',
    },
    headerPeriodCell: {
        width: '17.6%',
        padding: '8 4',
        fontWeight: 'bold',
        textAlign: 'center',
        color: '#fff',
        fontSize: 10,
        borderRightWidth: 1,
        borderRightColor: '#2d2d4e',
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
        color: '#1e40af',
        marginBottom: 1,
    },
    classInfo: {
        fontSize: 6.5,
        color: '#475569',
        marginBottom: 1,
    },
    teacher: {
        fontSize: 6.5,
        color: '#64748b',
    },
    empty: {
        color: '#cbd5e1',
        fontSize: 7,
        textAlign: 'center',
        marginTop: 6,
    },
    footer: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginTop: 16,
        paddingTop: 10,
        borderTopWidth: 1,
        borderTopColor: '#e2e8f0',
        fontSize: 6.5,
        color: '#94a3b8',
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
        borderTopWidth: 1,
        borderTopColor: '#334155',
        marginBottom: 6,
    },
    signatureLabel: {
        fontSize: 9,
        fontWeight: 'bold',
        color: '#1e293b',
        textTransform: 'uppercase',
        letterSpacing: 1,
    },
    signatureSub: {
        fontSize: 7,
        color: '#64748b',
        marginTop: 2,
    },
})

const dayNames = ['I', 'II', 'III', 'IV', 'V', 'VI']

export default function TimetablePDF({ slots, employeeName, className }) {
    const getSlot = (dow, pno) => slots?.find(s => s.day_of_week === dow && s.period_no === pno)

    const generatedDate = new Date().toLocaleDateString('en-IN', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })

    return (
        <Document>
            <Page size="A4" orientation="portrait" style={styles.page}>
                <View style={styles.border} />

                <View style={styles.headerBar}>
                    <View style={styles.headerLeft}>
                        <Text style={styles.headerTitle}>Timetable</Text>
                        <Text style={styles.headerSub}>Master Timetable — College Management System</Text>
                    </View>
                    {employeeName && (
                        <View style={styles.headerRight}>
                            <Text style={styles.headerStaff}>{employeeName}</Text>
                            <Text style={styles.headerStaffLabel}>Staff Timetable</Text>
                        </View>
                    )}
                </View>

                <View style={styles.infoSection}>
                    {employeeName && (
                        <View style={styles.infoItem}>
                            <Text style={styles.infoLabel}>Staff</Text>
                            <Text style={styles.infoValue}>{employeeName}</Text>
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
                                    const s = getSlot(dow, pno)
                                    const colStyle = j === 4 ? styles.lastPeriodCol : styles.periodCol
                                    return (
                                        <View key={pno} style={colStyle}>
                                            {s ? (
                                                <>
                                                    <Text style={styles.subject}>{s.subject?.name} ({s.subject?.code})</Text>
                                                    <Text style={styles.classInfo}>{s.class?.dept_code} - {s.class?.name} - {s.class?.year}</Text>
                                                    <Text style={styles.teacher}>{s.employee?.name}</Text>
                                                </>
                                            ) : (
                                                <Text style={styles.empty}>—</Text>
                                            )}
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