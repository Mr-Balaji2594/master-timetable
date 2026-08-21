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
    headerDept: {
        fontSize: 10,
        fontWeight: 'bold',
        color: '#fff',
    },
    headerDeptLabel: {
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
    snoCol: {
        width: '8%',
        padding: '8 4',
        borderRightWidth: 1,
        borderRightColor: BORDER_COLOR,
        fontWeight: 'bold',
        textAlign: 'center',
        justifyContent: 'center',
        fontSize: 9,
        color: TEXT_PRIMARY,
    },
    dataCol: {
        width: '18.4%',
        padding: '8 4',
        borderRightWidth: 1,
        borderRightColor: BORDER_COLOR,
        justifyContent: 'center',
    },
    lastDataCol: {
        width: '18.4%',
        padding: '8 4',
        justifyContent: 'center',
    },
    headerSnoCell: {
        width: '8%',
        padding: '8 4',
        fontWeight: 'bold',
        textAlign: 'center',
        color: '#fff',
        fontSize: 9,
        borderRightWidth: 1,
        borderRightColor: '#6366f1',
    },
    headerDataCell: {
        width: '18.4%',
        padding: '8 4',
        fontWeight: 'bold',
        textAlign: 'center',
        color: '#fff',
        fontSize: 9,
        borderRightWidth: 1,
        borderRightColor: '#6366f1',
    },
    headerLastDataCell: {
        width: '18.4%',
        padding: '8 4',
        fontWeight: 'bold',
        textAlign: 'center',
        color: '#fff',
        fontSize: 9,
    },
    subjectName: {
        fontWeight: 'bold',
        fontSize: 8,
        color: ACCENT,
    },
    cellText: {
        fontSize: 8,
        color: TEXT_PRIMARY,
    },
    totalRow: {
        flexDirection: 'row',
        backgroundColor: '#fef3c7',
        borderTopWidth: 1.5,
        borderTopColor: HEADER_BG,
    },
    totalLabel: {
        width: '63.2%',
        padding: '8 4',
        justifyContent: 'flex-end',
        flexDirection: 'row',
        fontWeight: 'bold',
        fontSize: 9,
        color: TEXT_PRIMARY,
    },
    totalValue: {
        width: '36.8%',
        padding: '8 4',
        justifyContent: 'center',
        flexDirection: 'row',
        fontWeight: 'bold',
        fontSize: 9,
        color: HEADER_BG,
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

export default function WorkloadPDF({ workloads, departmentName, totalHours }) {
    const generatedDate = new Date().toLocaleDateString('en-IN', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })

    const rows = workloads || []

    return (
        <Document>
            <Page size="A4" orientation="portrait" style={styles.page}>
                <View style={styles.border} />

                <Image src={HEADING_URL} style={styles.heading} />

                <View style={styles.headerBar}>
                    <View style={styles.headerLeft}>
                        <Text style={styles.headerTitle}>Subject Workload</Text>
                        <Text style={styles.headerSub}>Master Timetable — College Management System</Text>
                    </View>
                    {departmentName && (
                        <View style={styles.headerRight}>
                            <Text style={styles.headerDept}>{departmentName}</Text>
                            <Text style={styles.headerDeptLabel}>Department Workload</Text>
                        </View>
                    )}
                </View>

                <View style={styles.infoSection}>
                    <View style={styles.infoItem}>
                        <Text style={styles.infoLabel}>Department</Text>
                        <Text style={styles.infoValue}>{departmentName || '—'}</Text>
                    </View>
                    <View style={styles.infoItem}>
                        <Text style={styles.infoLabel}>Entries</Text>
                        <Text style={styles.infoValue}>{rows.length}</Text>
                    </View>
                    <View style={styles.infoItem}>
                        <Text style={styles.infoLabel}>Total Hours</Text>
                        <Text style={styles.infoValue}>{Number(totalHours || 0).toFixed(1)}</Text>
                    </View>
                </View>

                <View style={styles.table}>
                    <View style={styles.headerRow}>
                        <Text style={styles.headerSnoCell}>S.No</Text>
                        <Text style={styles.headerDataCell}>Year</Text>
                        <Text style={styles.headerDataCell}>Sem</Text>
                        <Text style={styles.headerDataCell}>Department</Text>
                        <Text style={styles.headerLastDataCell}>Subject Name</Text>
                        <Text style={styles.headerLastDataCell}>Hours</Text>
                    </View>
                    {rows.map((w, i) => (
                        <View key={w.id || i} style={i % 2 === 1 ? styles.altRow : styles.row}>
                            <View style={styles.snoCol}><Text>{i + 1}</Text></View>
                            <View style={styles.dataCol}><Text style={styles.cellText}>{w.year}</Text></View>
                            <View style={styles.dataCol}><Text style={styles.cellText}>{w.sem_mode}</Text></View>
                            <View style={styles.dataCol}><Text style={styles.cellText}>{w.department?.name || '-'}</Text></View>
                            <View style={styles.lastDataCol}><Text style={styles.subjectName}>{w.subject_name}</Text></View>
                            <View style={styles.lastDataCol}><Text style={styles.cellText}>{Number(w.total_hours).toFixed(1)}</Text></View>
                        </View>
                    ))}
                    <View style={styles.totalRow}>
                        <View style={styles.totalLabel}><Text>Total Hours</Text></View>
                        <View style={styles.totalValue}><Text>{Number(totalHours || 0).toFixed(1)}</Text></View>
                    </View>
                </View>

                <View style={styles.signatureSection}>
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