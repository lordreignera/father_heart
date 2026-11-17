document.querySelectorAll('[data-permission-editor]').forEach((editor) => {
    const tools = editor.querySelector('[data-permission-tools]');
    const search = editor.querySelector('[data-permission-search]');
    const globalSelect = editor.querySelector('[data-permission-select-all]');
    const groups = [...editor.querySelectorAll('[data-permission-module]')];
    const empty = editor.querySelector('[data-permission-empty]');
    const status = editor.querySelector('[data-permission-status]');
    if (!tools || !search) return;
    tools.hidden = false;

    const visibleRows = (group) => [...group.querySelectorAll('[data-permission-row]')].filter((row) => !row.hidden);
    const checkbox = (row) => row.querySelector('input[type="checkbox"]');
    const updateSelect = (control, rows) => {
        if (!control) return;
        const enabled = rows.map(checkbox).filter((input) => input && !input.disabled);
        const checked = enabled.filter((input) => input.checked).length;
        control.disabled = enabled.length === 0;
        control.checked = enabled.length > 0 && checked === enabled.length;
        control.indeterminate = checked > 0 && checked < enabled.length;
    };
    const refresh = () => {
        const term = search.value.trim().toLocaleLowerCase();
        let count = 0;
        groups.forEach((group) => {
            const moduleMatch = group.dataset.moduleName.toLocaleLowerCase().includes(term);
            const rows = [...group.querySelectorAll('[data-permission-row]')];
            rows.forEach((row) => {
                row.hidden = !!term && !moduleMatch && !row.textContent.toLocaleLowerCase().includes(term);
                if (!row.hidden) count++;
            });
            const visible = visibleRows(group);
            group.hidden = visible.length === 0;
            if (term && visible.length) group.open = true;
            updateSelect(group.querySelector('[data-module-select]'), visible);
        });
        updateSelect(globalSelect, groups.flatMap(visibleRows));
        if (empty) empty.hidden = count !== 0;
        if (status) status.textContent = `${count} matching permission${count === 1 ? '' : 's'}`;
    };
    const setSelected = (rows, checked) => rows.forEach((row) => {
        const input = checkbox(row);
        if (input && !input.disabled) input.checked = checked;
    });
    groups.forEach((group) => {
        const wrap = group.querySelector('[data-module-select-wrap]');
        if (wrap) wrap.hidden = false;
        group.querySelector('[data-module-select]')?.addEventListener('change', (event) => {
            setSelected(visibleRows(group), event.target.checked);
            refresh();
        });
    });
    globalSelect?.addEventListener('change', () => {
        setSelected(groups.flatMap(visibleRows), globalSelect.checked);
        refresh();
    });
    search.addEventListener('input', refresh);
    editor.addEventListener('change', (event) => {
        if (event.target.matches('[data-permission-row] input')) refresh();
    });
    refresh();
});
