// Keep the full navigation visible on desktop and collapse it on small screens.
const mobileNav = document.querySelector('[data-admin-mobile-nav]');
if (mobileNav && window.matchMedia) {
    const mobileViewport = window.matchMedia('(max-width: 720px)');
    const updateNavigation = () => { mobileNav.open = !mobileViewport.matches; };
    updateNavigation();
    if (mobileViewport.addEventListener) mobileViewport.addEventListener('change', updateNavigation);
    else mobileViewport.addListener(updateNavigation);
}

// Shared district → subcounty selector for the intake and distribution forms.
document.querySelectorAll('[data-dialog-open]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        const dialog = document.querySelector(trigger.dataset.dialogOpen);
        if (dialog instanceof HTMLDialogElement) {
            dialog.showModal();
            dialog.dispatchEvent(new Event('admin:opened'));
        }
    });
});

document.querySelectorAll('.admin-dialog').forEach((dialog) => {
    if (dialog.open) {
        dialog.close();
        dialog.showModal();
    }
    dialog.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
});

const assessmentForm = document.querySelector('[data-assessment-form]');
if (assessmentForm) {
    const sections = [...assessmentForm.querySelectorAll('[data-assessment-step]')];
    const guide = assessmentForm.querySelector('[data-assessment-guide]');
    const progress = assessmentForm.querySelector('[data-assessment-progress]');
    const stepButtons = [...assessmentForm.querySelectorAll('[data-assessment-go]')];
    const modeButtons = [...assessmentForm.querySelectorAll('[data-assessment-mode]')];
    const previous = assessmentForm.querySelector('[data-assessment-prev]');
    const next = assessmentForm.querySelector('[data-assessment-next]');
    const save = assessmentForm.querySelector('[data-assessment-save]');
    let currentStep = 0;
    let mode = assessmentForm.dataset.hasErrors === 'true' ? 'full' : 'steps';

    function renderAssessment() {
        sections.forEach((section, index) => { section.hidden = mode === 'steps' && index !== currentStep; });
        modeButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.assessmentMode === mode)));
        stepButtons.forEach((button, index) => {
            button.classList.toggle('is-current', mode === 'steps' && index === currentStep);
            if (mode === 'steps' && index === currentStep) button.setAttribute('aria-current', 'step');
            else button.removeAttribute('aria-current');
        });
        progress.textContent = mode === 'steps' ? `Step ${currentStep + 1} of ${sections.length}` : 'Full form';
        previous.hidden = mode === 'full' || currentStep === 0;
        next.hidden = mode === 'full' || currentStep === sections.length - 1;
        if (save) save.hidden = mode === 'steps' && currentStep !== sections.length - 1;
    }

    function showStep(index) {
        currentStep = index;
        mode = 'steps';
        renderAssessment();
        guide.scrollIntoView({ behavior: 'auto', block: 'start' });
    }

    modeButtons.forEach((button) => button.addEventListener('click', () => {
        mode = button.dataset.assessmentMode;
        renderAssessment();
    }));
    stepButtons.forEach((button, index) => button.addEventListener('click', () => showStep(index)));
    previous.addEventListener('click', () => showStep(currentStep - 1));
    next.addEventListener('click', () => {
        const invalid = [...sections[currentStep].querySelectorAll('input, select, textarea')].find((field) => !field.checkValidity());
        if (invalid) { invalid.reportValidity(); return; }
        showStep(currentStep + 1);
    });
    assessmentForm.addEventListener('invalid', (event) => {
        const index = sections.findIndex((section) => section.contains(event.target));
        if (mode === 'steps' && index !== -1 && index !== currentStep) showStep(index);
    }, true);
    guide.hidden = false;
    renderAssessment();
}

document.querySelectorAll('[data-location-district]').forEach((districtSelect) => {
    const subcountySelect = document.getElementById(districtSelect.dataset.locationDistrict);
    if (!subcountySelect) return;
    let requestNumber = 0;

    async function loadSubcounties() {
        const selected = subcountySelect.dataset.selected;
        const district = districtSelect.value;
        const currentRequest = ++requestNumber;
        if (!district) {
            subcountySelect.replaceChildren(new Option('Select district first', ''));
            return;
        }

        try {
            const url = new URL(districtSelect.dataset.subcountyUrl, window.location.origin);
            url.searchParams.set('district_id', district);
            if (selected) url.searchParams.set('selected_id', selected);
            const response = await fetch(url, { credentials: 'same-origin' });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const items = await response.json();
            if (currentRequest !== requestNumber) return;
            subcountySelect.replaceChildren(new Option('Select subcounty', ''));
            for (const item of items) {
                subcountySelect.add(new Option(`${item.name} (${item.kind.replace('_', ' ')})`, item.id, false, String(item.id) === selected));
            }
        } catch {
            if (currentRequest === requestNumber && !selected) subcountySelect.replaceChildren(new Option('Unable to load subcounties', ''));
        }
    }

    districtSelect.addEventListener('change', () => {
        subcountySelect.dataset.selected = '';
        subcountySelect.replaceChildren(new Option('Loading subcounties...', ''));
        loadSubcounties();
    });
    const parentDialog = districtSelect.closest('dialog');
    if (parentDialog && !parentDialog.open) parentDialog.addEventListener('admin:opened', loadSubcounties, { once: true });
    else loadSubcounties();
});

// Enhance the assessment district select without changing the submitted district ID.
// The original select remains usable if JavaScript is unavailable.
document.querySelectorAll('select[data-searchable-district]').forEach((select) => {
    const wrapper = document.createElement('span');
    wrapper.className = 'district-combobox';
    const input = document.createElement('input');
    input.type = 'text';
    input.placeholder = 'Search or select a district or city';
    input.autocomplete = 'off';
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');
    const list = document.createElement('span');
    list.className = 'district-combobox-list';
    list.id = `${select.id}-choices`;
    list.setAttribute('role', 'listbox');
    list.hidden = true;
    input.setAttribute('aria-controls', list.id);
    const choices = [...select.options].filter((option) => option.value);
    let highlighted = -1;
    let filtered = [];
    input.value = select.selectedOptions[0]?.value ? select.selectedOptions[0].textContent.trim() : '';
    select.insertAdjacentElement('afterend', wrapper);
    wrapper.append(input, list);
    select.hidden = true;

    function close() { list.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); }
    function render() {
        const term = input.value.trim().toLocaleLowerCase();
        filtered = choices.filter((option) => option.textContent.toLocaleLowerCase().includes(term));
        list.replaceChildren();
        highlighted = -1;
        filtered.forEach((option, index) => {
            const item = document.createElement('span');
            item.id = `${list.id}-${index}`;
            item.setAttribute('role', 'option');
            item.textContent = option.textContent.trim();
            item.addEventListener('mousedown', (event) => event.preventDefault());
            item.addEventListener('click', () => choose(index));
            list.append(item);
        });
        if (!filtered.length) { const message = document.createElement('span'); message.className = 'district-no-results'; message.textContent = 'No matching districts'; list.append(message); }
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }
    function choose(index) {
        const option = filtered[index];
        if (!option) return;
        select.value = option.value;
        input.value = option.textContent.trim();
        input.setCustomValidity('');
        select.dispatchEvent(new Event('change', { bubbles: true }));
        close();
    }
    input.addEventListener('focus', () => { if (select.value) { input.select(); } render(); });
    input.addEventListener('input', () => {
        if (select.value) { select.value = ''; select.dispatchEvent(new Event('change', { bubbles: true })); }
        input.setCustomValidity(input.value.trim() ? 'Choose a district from the list.' : '');
        render();
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') { close(); return; }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (list.hidden) render();
            if (!filtered.length) return;
            highlighted = (highlighted + (event.key === 'ArrowDown' ? 1 : -1) + filtered.length) % filtered.length;
            [...list.children].forEach((item, index) => item.setAttribute('aria-selected', String(index === highlighted)));
            const active = list.children[highlighted];
            input.setAttribute('aria-activedescendant', active.id);
            active.scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'Enter' && !list.hidden && highlighted >= 0) { event.preventDefault(); choose(highlighted); }
    });
    input.addEventListener('blur', close);
});

document.querySelectorAll('[data-district-search]').forEach((input) => {
    const select = document.getElementById(input.dataset.districtSearch);
    if (!select) return;
    const options = [...select.options];
    input.addEventListener('input', () => {
        const term = input.value.trim().toLocaleLowerCase();
        const current = select.value;
        select.replaceChildren(...options.filter((option) => option.value === '' || option.value === current || option.textContent.toLocaleLowerCase().includes(term)));
        select.value = current;
    });
});
