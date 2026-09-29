<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | eLINGAP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<div
    x-data="reportsPage()"
    x-init="init()"
    @keydown.escape.window="closeReportModal()"
    class="min-h-screen"
>
    <div class="flex min-h-screen">
        <x-sidebar active="Reports" />
        <main class="min-w-0 flex-1 pl-16">
            <header class="flex h-[76px] items-center justify-between border-b border-gray-200 bg-white px-6 sm:px-8">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Analytics &amp; Exports</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Reports</h1>
                </div>
                <div class="flex items-center gap-4 text-gray-500">
                    <button type="button" class="relative flex h-9 w-9 items-center justify-center rounded-lg hover:bg-gray-50" aria-label="Notifications">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 1-5.714 0M18.75 10.5c0 3.142.75 3.142 1.5 4.5H3.75c.75-1.358 1.5-1.358 1.5-4.5a6.75 6.75 0 1 1 13.5 0ZM9 19.5h6"/><path stroke-linecap="round" stroke-linejoin="round" d="M10 21h4"/></svg>
                        <span class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white">5</span>
                    </button>
                    <button type="button" class="hidden items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-700 sm:flex" aria-label="Scan QR code">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h2m2 0h2m-6 3h2m2 0h2m-6 3h6"/></svg>
                        Scan QR
                    </button>
                </div>
            </header>

            <section class="px-6 py-8 sm:px-8">
                <p class="mb-6 text-sm text-gray-500">Select a report template. The system automatically applies the required filters.</p>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <template x-for="report in reports" :key="report.id">
                        <button type="button" @click="openReportModal(report.id)" class="group flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-6 text-left shadow-2xs transition-all hover:border-blue-400 hover:shadow-sm">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl" :class="report.badgeClass">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h7.5L18.75 8.25v12H6.75v-16.5Zm7.5 0v4.5h4.5M9.75 12h6m-6 3h6m-6-6h1.5"/></svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-[15px] font-bold text-gray-900" x-text="report.title"></span>
                                <span class="mt-1 block text-sm leading-5 text-gray-500" x-text="report.subtitle"></span>
                                <span class="mt-3 flex gap-2"><span class="rounded-md bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">PDF</span><span class="rounded-md bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">Excel</span></span>
                            </span>
                        </button>
                    </template>
                </div>
            </section>
        </main>
    </div>

    <div x-cloak x-show="reportModalOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-[1px]">
        <div class="flex max-h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl" role="dialog" aria-modal="true" aria-label="Report configuration">
            <header class="flex flex-none items-center justify-between border-b border-gray-200 bg-white px-6 py-4">
                <div class="flex items-center gap-3.5">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl" :class="selectedReport.badgeClass">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h7.5L18.75 8.25v12H6.75v-16.5Zm7.5 0v4.5h4.5M9.75 12h6m-6 3h6m-6-6h1.5"/></svg>
                    </span>
                    <div><h2 class="text-lg font-bold text-gray-900" x-text="selectedReport.modalTitle"></h2><p class="text-xs text-gray-500">Choose the filters for the report you want to generate.</p></div>
                </div>
                <button type="button" @click="closeReportModal()" class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-900" aria-label="Close report modal"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/></svg></button>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto bg-gray-50/30 p-6">
                <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                    <aside class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 lg:col-span-4">
                        <div class="border-b border-gray-100 pb-3"><h3 class="text-base font-bold text-gray-900">Report Filters</h3><p class="text-xs text-gray-500">Adjust the options to update the preview.</p></div>
                        <label class="block"><span class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-gray-500">Barangay</span><select x-model="filters.barangay" class="w-full min-w-0 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"><option>All Barangays</option><template x-for="barangay in barangays" :key="barangay"><option x-text="barangay"></option></template></select></label>
                        <label class="block"><span class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-gray-500">Status / Category</span><select x-model="filters.status" class="w-full min-w-0 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"><template x-for="status in statusOptions" :key="status"><option x-text="status"></option></template></select></label>
                        <label class="block"><span class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-gray-500">Date Period</span><select x-model="filters.datePeriod" class="w-full min-w-0 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"><option>This Month</option><option>This Quarter</option><option>This Year (2026)</option><option>Custom Date Range</option></select></label>
                        <div x-show="filters.datePeriod === 'Custom Date Range'" x-transition class="grid grid-cols-2 gap-3">
                            <label class="min-w-0"><span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-gray-500">From</span><div class="grid min-w-0 grid-cols-2 gap-2"><select x-model="filters.fromMonth" class="min-w-0 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"><template x-for="month in months" :key="month"><option x-text="month"></option></template></select><select x-model="filters.fromYear" class="min-w-0 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"><option>2026</option><option>2025</option></select></div></label>
                            <label class="min-w-0"><span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-gray-500">To</span><div class="grid min-w-0 grid-cols-2 gap-2"><select x-model="filters.toMonth" class="min-w-0 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"><template x-for="month in months" :key="month"><option x-text="month"></option></template></select><select x-model="filters.toYear" class="min-w-0 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"><option>2026</option><option>2025</option></select></div></label>
                        </div>
                        <div class="flex items-start gap-2 rounded-xl border border-blue-100 bg-blue-50/60 p-3 text-xs text-gray-600"><svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5m0-8h.01"/></svg><span><strong>Auto-filtered:</strong> Only filters relevant to this report are shown.</span></div>
                    </aside>

                    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white lg:col-span-8">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4"><div><h3 class="text-base font-bold text-gray-900">Live Report Preview</h3><p class="text-xs text-gray-500">Preview updates automatically when filters change.</p></div><span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-bold uppercase text-blue-600">Official Preview</span></div>
                        <div class="space-y-0.5 border-b border-gray-100 px-6 py-5 text-center"><p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Republic of the Philippines</p><p class="text-xs font-extrabold uppercase tracking-wide text-gray-800">Municipality of Santa Maria</p><p class="text-[11px] font-medium uppercase text-gray-500">Office for Senior Citizens Affairs</p><p class="pt-2 text-sm font-extrabold uppercase text-gray-900" x-text="selectedReport.heading"></p><p class="pt-0.5 text-xs text-gray-500" x-text="filterSummary"></p></div>
                        <div class="overflow-x-auto"><table class="w-full min-w-[760px] border-collapse text-left text-[11px]"><thead class="bg-slate-50 text-[10px] font-bold text-gray-600"><tr><template x-for="column in selectedReport.columns" :key="column"><th class="border-b border-gray-900 px-2 py-2" x-text="column"></th></template></tr></thead><tbody><template x-for="row in filteredPreviewRows" :key="row.join('-')"><tr class="border-b border-gray-100 text-gray-500 last:border-0"><template x-for="cell in row" :key="cell"><td class="px-2 py-2.5 align-top" x-text="cell"></td></template></tr></template><tr x-show="filteredPreviewRows.length === 0"><td class="px-4 py-8 text-center text-sm text-gray-500" :colspan="selectedReport.columns.length">No preview records match these filters.</td></tr></tbody></table></div>
                        <footer class="flex items-center justify-between bg-gray-50/50 px-6 py-3 text-xs text-gray-400"><span x-text="'Showing up to 5 preview records • ' + filteredPreviewRows.length + ' total'"></span><span>Veronica B. Ramos / Mayor Bartolome R. Ramos</span></footer>
                    </section>
                </div>
            </div>

            <footer class="flex flex-none items-center justify-end gap-3 border-t border-gray-200 bg-white px-6 py-4"><button type="button" @click="closeReportModal()" class="min-h-11 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-all hover:bg-gray-50">Cancel</button><button type="button" @click="triggerFrontendExport('excel')" class="min-h-11 rounded-xl border border-emerald-300 bg-white px-5 py-2.5 text-sm font-semibold text-emerald-700 transition-all hover:bg-emerald-50">Export Excel / CSV</button><button type="button" @click="window.print()" class="min-h-11 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-700">Export PDF Report</button></footer>
        </div>
    </div>
</div>

<script>
function reportsPage() {
    const baseRows = {
        masterlist: [['SM-2024-0001', 'Santos, Remedios C.', 'March 14, 1942', '82', 'Female', 'Poblacion', 'Active', 'Jan 10, 2024'], ['SM-2024-0002', 'Dela Cruz, Ernesto B.', 'June 2, 1933', '91', 'Male', 'Bagbaguin', 'Active', 'Feb 5, 2024'], ['SM-2024-0003', 'Reyes, Caridad M.', 'January 10, 1923', '103', 'Female', 'Cay Pombo', 'Active', 'Mar 1, 2024'], ['SM-2024-0004', 'Gomez, Florentino A.', 'September 5, 1949', '76', 'Male', 'Tumana', 'Active', 'Apr 15, 2024'], ['SM-2024-0020', 'Garcia, Lourdes R.', 'May 4, 1944', '82', 'Female', 'San Jose Patag', 'Bedridden', 'May 6, 2024']],
        registrations: [['APP-2024-0041', 'Santos, Remedios C.', 'Poblacion', 'Senior ID Renewal', '2024-10-01', 'For Printing', 'M. Angeles'], ['APP-2024-0042', 'Villanueva, Domingo R.', 'Balasing', 'New Senior ID', '2024-10-02', 'For Printing', 'J. Cruz'], ['APP-2024-0046', 'Dela Cruz, Ernesto B.', 'Bagbaguin', 'Social Pension Claim', '2024-10-01', 'For Printing', 'M. Angeles']],
        payouts: [['SM-2024-0001', 'Santos, Remedios C.', 'Poblacion', 'Social Pension Q4', '—', '—', 'Unclaimed'], ['SM-2024-0002', 'Dela Cruz, Ernesto B.', 'Bagbaguin', 'Social Pension Q4', '—', '—', 'Unclaimed'], ['SM-2024-0003', 'Reyes, Caridad M.', 'Cay Pombo', 'Centenarian Cash Gift (100+)', '—', '—', 'Unclaimed'], ['SM-2024-0005', 'Bautista, Natividad P.', 'Poblacion', 'Cash Gift (80+)', 'Bautista, Natividad P.', 'Oct 12, 2024', 'Claimed']],
        milestones: [['SM-2024-0001', 'Santos, Remedios C.', 'March 14, 1942', '80+', 'Poblacion', '09181234567', 'For Verification'], ['SM-2024-0002', 'Dela Cruz, Ernesto B.', 'June 2, 1933', '90+', 'Bagbaguin', '09231234567', 'Verified'], ['SM-2024-0003', 'Reyes, Caridad M.', 'January 10, 1923', '100+', 'Cay Pombo', '09281234567', 'Verified'], ['SM-2024-0005', 'Bautista, Natividad P.', 'December 20, 1940', '80+', 'Poblacion', '09361234567', 'For Verification']],
        bedridden: [['SM-2024-0009', 'Espiritu, Corazon F.', 'Bedridden', 'Purok 2, Lot 14, Guyong, Santa Maria', 'Fernando Espiritu', 'Son', '09611234567'], ['SM-2024-0010', 'Aquino, Virgilio N. Jr.', 'Confined', 'Zone 3, Blk 8, Longos, Santa Maria', 'Patricia Aquino', 'Wife', '09661234567']],
        deceased: [['SM-2024-0006', 'Villanueva, Domingo R.', 'Balasing', 'Relocated', 'October 2, 2026', 'Maria Angeles'], ['SM-2024-0008', 'Castillo, Pedro S.', 'Cay Pombo', 'Active', 'September 4, 2026', 'Maria Angeles']]
    };
    return {
        reportModalOpen: false, activeReport: 'masterlist', filters: { barangay: 'All Barangays', status: 'All', datePeriod: 'This Year (2026)', fromMonth: 'Jan', fromYear: '2026', toMonth: 'Dec', toYear: '2026' },
        months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        barangays: ['Bagbaguin', 'Balasing', 'Buenavista', 'Bulac', 'Camangyanan', 'Catmon', 'Cay Pombo', 'Caysio', 'Guyong', 'Lalakhan', 'Mag-asawang Sapa', 'Mahabang Parang', 'Manggahan', 'Parada', 'Poblacion', 'Pulong Buhangin', 'San Gabriel', 'San Jose Patag', 'San Vicente', 'Santa Clara', 'Santa Cruz', 'Silangan', 'Tabing Bakod', 'Tumana'],
        reports: [
            { id: 'masterlist', title: 'Senior Citizen Masterlist & Demographics', modalTitle: 'Senior Citizen Masterlist', heading: 'Senior Citizen Masterlist & Demographics', subtitle: 'Official registry of registered seniors by barangay.', badgeClass: 'bg-blue-50 text-blue-600', columns: ['OSCA ID No.', 'Full Name', 'Birthdate', 'Age', 'Sex', 'Barangay', 'Health/Life Status', 'Date Registered'], statuses: ['All', 'Active', 'Bedridden', 'Confined'] },
            { id: 'registrations', title: 'OSCA ID Registration & Issuance Log', modalTitle: 'OSCA ID Registration & Issuance', heading: 'OSCA ID Registration & Issuance Log', subtitle: 'Tracks ID applications, issuance, claiming, and rejected records.', badgeClass: 'bg-purple-50 text-purple-600', columns: ['App Ref #', 'Applicant Name', 'Barangay', 'Applicant Type', 'Date Submitted', 'Status', 'Reviewed By / Remarks'], statuses: ['All', 'For Printing', 'Ready for Claiming', 'Released', 'Rejected'] },
            { id: 'payouts', title: 'Benefits & Pension Payout Summary', modalTitle: 'Benefits & Pension Payout', heading: 'Benefits & Pension Payout Summary', subtitle: 'Summarizes benefit distribution and payout records.', badgeClass: 'bg-emerald-50 text-emerald-600', columns: ['OSCA ID No.', 'Beneficiary Name', 'Barangay', 'Benefit Type', 'Claimed By', 'Date Claimed', 'Payout Status'], statuses: ['All', 'Claimed', 'Unclaimed'] },
            { id: 'milestones', title: 'Milestone Age Qualifiers', modalTitle: 'Milestone Age Qualifiers', heading: 'Milestone Age Qualifiers', subtitle: 'Lists seniors eligible for milestone-age cash gifts.', badgeClass: 'bg-amber-50 text-amber-600', columns: ['OSCA ID No.', 'Full Name', 'Birthdate', 'Milestone Age', 'Barangay', 'Contact / Family No.', 'Document Verification Status'], statuses: ['All', '80+ (Octogenarian)', '90+ (Nonagenarian)', '100+ (Centenarian)'] },
            { id: 'bedridden', title: 'Bedridden, Confined & Proxy List', modalTitle: 'Bedridden, Confined & Proxy', heading: 'Bedridden, Confined & Proxy List', subtitle: 'Lists seniors requiring home visits or proxy assistance.', badgeClass: 'bg-cyan-50 text-cyan-600', columns: ['OSCA ID No.', 'Senior Name', 'Condition', 'Address & Barangay', 'Authorized Proxy Name', 'Relationship', 'Proxy Contact No.'], statuses: ['All', 'Bedridden', 'Confined'] },
            { id: 'deceased', title: 'Deceased / Delisted Seniors Log', modalTitle: 'Deceased / Delisted Seniors', heading: 'Deceased / Delisted Seniors Log', subtitle: 'Audit trail of inactive and deceased senior records.', badgeClass: 'bg-rose-50 text-rose-600', columns: ['OSCA ID No.', 'Full Name', 'Barangay', 'Previous Status', 'Date Marked Deceased / Inactive', 'Updated By (Staff)'], statuses: ['All', 'Deceased', 'Relocated / Inactive'] }
        ],
        init() { this.$watch('activeReport', () => { this.filters.status = 'All'; }); },
        openReportModal(id) { this.activeReport = id; this.filters.status = 'All'; this.reportModalOpen = true; },
        closeReportModal() { this.reportModalOpen = false; },
        triggerFrontendExport(format) { const report = this.selectedReport; const csv = [report.columns, ...this.filteredPreviewRows].map(row => row.map(value => `"${String(value).replaceAll('"', '""')}"`).join(',')).join('\n'); const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' }); const link = document.createElement('a'); link.href = URL.createObjectURL(blob); link.download = `${report.id}-preview.csv`; link.click(); URL.revokeObjectURL(link.href); },
        get selectedReport() { return this.reports.find(report => report.id === this.activeReport) || this.reports[0]; },
        get statusOptions() { return this.selectedReport.statuses; },
        get previewRows() { return baseRows[this.activeReport] || []; },
        statusMatches(row) { const status = this.filters.status; const aliases = { '80+ (Octogenarian)': '80+', '90+ (Nonagenarian)': '90+', '100+ (Centenarian)': '100+', 'Relocated / Inactive': 'Relocated' }; return status === 'All' || row.includes(status) || row.includes(aliases[status] || '') || (status === 'Deceased' && row.includes('Deceased')); },
        get filteredPreviewRows() { return this.previewRows.filter(row => (this.filters.barangay === 'All Barangays' || row.includes(this.filters.barangay)) && this.statusMatches(row)); },
        get filterSummary() { const period = this.filters.datePeriod === 'Custom Date Range' ? `${this.filters.fromMonth} ${this.filters.fromYear}–${this.filters.toMonth} ${this.filters.toYear}` : this.filters.datePeriod === 'This Year (2026)' ? 'January–December 2026' : this.filters.datePeriod; return `Barangay: ${this.filters.barangay} • Status: ${this.filters.status} • Period: ${period}`; }
    };
}
</script>
</body>
</html>
