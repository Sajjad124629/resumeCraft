import { ref, reactive, toRaw } from 'vue';
import { safeFetch } from './safeFetch';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

export function useDatatable<T extends Record<string, any> = Record<string, any>>(fetchUrl: string, defaultSort: string = 'id', defaultSortDirection: string = 'desc', additionalParams?: T) {
const loading = ref(true);
const totalRows = ref(0);
const rows = ref<any[]>([]);
    const controller = ref<AbortController | null>(null);
        let timer: any = null;

        const params = reactive({
        current_page: 0,
        pagesize: 10,
        sort_column: defaultSort,
        sort_direction: defaultSortDirection,
        search: '',
        column_filters: [] as any[],
        ...(additionalParams || {} as T)
        });

        const getData = async () => {
        try {
        if (controller.value) controller.value.abort();
        controller.value = new AbortController();
        const signal = controller.value.signal;

        loading.value = true;
        const response = await safeFetch(route(fetchUrl), {
        method: 'POST',
        headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
        },
        body: JSON.stringify(toRaw(params)),
        signal
        });

        const data = await response.json();
        rows.value = data?.data || [];
        totalRows.value = data?.recordsFiltered || 0;
        } catch (error) {
        console.error('Error fetching data:', error);
        } finally {
        loading.value = false;
        }
        };

        const filter = () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
        getData();
        }, 300);
        };

        const changeServer = (data: any) => {
        params.current_page = (data.current_page - 1) * data.pagesize;
        params.pagesize = data.pagesize;
        params.column_filters = data.column_filters;
        params.search = data.search;
        if (data.sort_column !== 'action') {
        params.sort_column = data.sort_column;
        params.sort_direction = data.sort_direction;
        }
        if (data.change_type === 'search') {
        filter();
        } else {
        getData();
        }
        };
        const exportTable = (
        type: 'csv' | 'excel' | 'print' | 'pdf',
        cols: any[],
        filename = 'table',
        customRecords?: any[]
        ) => {

        const columns = cols
        .filter(col => col.export !== false)
        .map(col => ({
        field: col.field,
        title: col.title,
        }));

        const records = customRecords || rows.value;

        const exportRows = records.map(record => {
        const row: Record<string, any> = {};

            columns.forEach(col => {
            row[col.title] = record[col.field];
            });

            return row;
            });

            // CSV
            if (type === 'csv') {

            const headers = columns.map(col => col.title);

            const csvContent = [
            headers.join(','),
            ...exportRows.map(row =>
            headers
            .map(header => `"${row[header] ?? ''}"`)
            .join(',')
            ),
            ].join('\n');

            const blob = new Blob(
            [csvContent],
            { type: 'text/csv;charset=utf-8;' }
            );

            const link = document.createElement('a');

            link.href = URL.createObjectURL(blob);
            link.download = `${filename}.csv`;
            link.click();

            URL.revokeObjectURL(link.href);

            return;
            }

            // EXCEL
            if (type === 'excel') {

            const worksheet = XLSX.utils.json_to_sheet(exportRows);

            const workbook = XLSX.utils.book_new();

            XLSX.utils.book_append_sheet(
            workbook,
            worksheet,
            'Sheet1'
            );

            XLSX.writeFile(
            workbook,
            `${filename}.xlsx`
            );

            return;
            }

            // PDF
            if (type === 'pdf') {

            const doc = new jsPDF();

            autoTable(doc, {
            head: [
            columns.map(col => col.title)
            ],
            body: records.map(record =>
            columns.map(col =>
            record[col.field] ?? ''
            )
            ),
            });

            doc.save(`${filename}.pdf`);

            return;
            }

            // PRINT
            if (type === 'print') {

            const html = `
            <html>

            <head>
                <title>${filename}</title>
                <style>
    @page {
        size: A4 portrait;
        margin: 12mm;
    }

    body {
        margin: 0;
        padding: 25px;
        font-family: Arial, Helvetica, sans-serif;
        color: #374151;
        background: #fff;
    }

    h2 {
        text-align: center;
        margin-bottom: 22px;
        font-size: 30px;
        font-weight: 700;
        color: #374151;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    thead th {
        background: #eef4ff;
        color: #4b5563;
        font-size: 14px;
        font-weight: 600;
        text-align: left;
        padding: 10px 12px;

        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    tbody td {
        padding: 9px 12px;
        font-size: 14px;
        color: #4b5563;
    }

    tbody tr:nth-child(even) {
        background: #fafafa;

        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    tbody tr:nth-child(odd) {
        background: white;
    }

    th:first-child,
    td:first-child {
        width: 40px;
    }

    @media print {
        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        body {
            padding: 12mm;
        }
    }
</style>
            </head>

            <body>

                <h2>${filename}</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                ${columns.map(col => `<th>${col.title}</th>`).join('')}
                            </tr>
                        </thead>

                        <tbody>
                            ${records.map(record => `
                            <tr>
                                ${columns.map(col => `
                                <td>${record[col.field] ?? ''}</td>
                                `).join('')}
                            </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </body>

            </html>
            `;

            const printWindow = window.open(
            '',
            '_blank',
            'width=1000,height=700'
            );

            if (!printWindow) return;

            printWindow.document.write(html);
            printWindow.document.close();

            printWindow.onafterprint = () => {
                printWindow.close();
            };

            setTimeout(() => {
                printWindow.print();
            }, 300);

            return;
            }
            };
            return {
            loading,
            totalRows,
            rows,
            params,
            getData,
            changeServer,
            exportTable
            };
            }
