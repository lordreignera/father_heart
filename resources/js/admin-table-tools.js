// These controls intentionally operate only on rows already rendered on this page.
document.querySelectorAll('table.admin-table:not([data-table-tools="off"])').forEach((table, index) => {
    const headers = [...table.querySelectorAll('thead th')];
    const body = table.tBodies[0];
    if (!body || !headers.length) return;

    const columns = headers.map((header, column) => ({ title: header.textContent.trim(), column }))
        .filter(({ title }) => title && !/^(action|actions)$/i.test(title));
    const rows = [...body.rows].filter((row) => row.cells.length === headers.length && ![...row.cells].some((cell) => cell.colSpan > 1));
    if (!columns.length || !rows.length) return;

    const wrapper = table.closest('.admin-table-wrap');
    if (!wrapper) return;
    const tools = document.createElement('div');
    tools.className = 'admin-table-tools';
    const label = document.createElement('label');
    label.textContent = 'Filter this page';
    const search = document.createElement('input');
    search.type = 'search';
    search.placeholder = 'Search visible rows';
    label.appendChild(search);
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = 'Export this page CSV (Excel)';
    const note = document.createElement('small');
    const canPageExport = table.dataset.tablePageExport === 'true' || document.body.dataset.tablePageExport === 'true';
    note.textContent = canPageExport ? 'Only rows on this page are filtered and exported.' : 'Only rows on this page are filtered.';
    const status = document.createElement('span');
    status.className = 'admin-table-tools-status';
    status.setAttribute('aria-live', 'polite');
    tools.append(label);
    if (canPageExport) tools.append(button);
    tools.append(note, status);
    wrapper.before(tools);

    const cellText = (row, column) => row.cells[column].innerText.trim();
    const update = () => {
        const query = search.value.trim().toLocaleLowerCase();
        let shown = 0;
        rows.forEach((row) => {
            const match = !query || columns.some(({ column }) => cellText(row, column).toLocaleLowerCase().includes(query));
            row.style.display = match ? '' : 'none';
            if (match) shown++;
        });
        status.textContent = `${shown} of ${rows.length} rows on this page`;
    };
    search.addEventListener('input', update);
    update();

    button.addEventListener('click', () => {
        const csvCell = (value) => {
            let text = String(value ?? '');
            if (/^[\s\x00-\x1f]*[=+\-@]/u.test(text) || /^0\d+$/.test(text)) text = `'${text}`;
            return `"${text.replaceAll('"', '""')}"`;
        };
        const csvRows = [columns.map(({ title }) => title), ...rows.filter((row) => row.style.display !== 'none').map((row) => columns.map(({ column }) => cellText(row, column)))];
        const blob = new Blob(['\ufeff', csvRows.map((row) => row.map(csvCell).join(',')).join('\r\n')], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        const name = document.title.split('|')[0].trim().replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase() || 'table';
        link.href = url;
        link.download = `${name}-page-table-${index + 1}.csv`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    });
});
