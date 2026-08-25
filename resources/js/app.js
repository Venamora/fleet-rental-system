import '../css/app.css';

const toggle = document.querySelector('[data-nav-toggle]');
const sidebar = document.querySelector('[data-sidebar]');
const backdrop = document.querySelector('[data-nav-backdrop]');
let activeDialog = null;
let dialogReturnFocus = null;
const closeNav = () => {
    sidebar?.classList.remove('is-open');
    backdrop?.classList.remove('is-visible');
    toggle?.setAttribute('aria-expanded', 'false');
};
toggle?.addEventListener('click', () => {
    const open = sidebar.classList.toggle('is-open');
    backdrop?.classList.toggle('is-visible', open);
    toggle.setAttribute('aria-expanded', String(open));
    if (open) sidebar.querySelector('a, button')?.focus();
});
backdrop?.addEventListener('click', closeNav);
document.querySelectorAll('[data-dismiss]').forEach((button) => button.addEventListener('click', () => button.parentElement.remove()));
const closeDialog = (dialog) => {
    if (!dialog) return;
    dialog.hidden = true;
    dialog.setAttribute('aria-hidden', 'true');
    if (activeDialog === dialog) {
        activeDialog = null;
        dialogReturnFocus?.focus();
        dialogReturnFocus = null;
    }
};
document.querySelectorAll('[data-dialog-open]').forEach((button) => button.addEventListener('click', () => {
    const dialog = document.getElementById(button.dataset.dialogOpen);
    if (dialog) {
        activeDialog = dialog;
        dialogReturnFocus = button;
        dialog.hidden = false;
        dialog.setAttribute('aria-hidden', 'false');
        (dialog.querySelector('input, textarea, select, button') || dialog).focus();
    }
}));
document.querySelectorAll('[data-dialog-close]').forEach((button) => button.addEventListener('click', () => closeDialog(button.closest('.dialog'))));
document.querySelectorAll('.dialog').forEach((dialog) => dialog.addEventListener('click', (event) => { if (event.target === dialog) closeDialog(dialog); }));
document.addEventListener('keydown', (event) => { if (event.key === 'Escape') { closeDialog(activeDialog); closeNav(); } if (event.key === 'Tab' && activeDialog && !activeDialog.hidden) { const focusable = [...activeDialog.querySelectorAll('button, input, select, textarea, a[href], [tabindex]:not([tabindex="-1"])')]; if (!focusable.length) return; const first = focusable[0]; const last = focusable[focusable.length - 1]; if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); } else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); } } });

const revealItems = document.querySelectorAll('.metric-card, .table-card, .form-card, .insight-card, .today-card');
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
    revealItems.forEach((item) => item.classList.add('reveal-on-scroll'));
    const observer = new IntersectionObserver((entries, currentObserver) => entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        currentObserver.unobserve(entry.target);
    }), { threshold: 0.12 });
    revealItems.forEach((item) => observer.observe(item));
}

document.querySelectorAll('[data-availability-form]').forEach((form) => {
    const dates = [...form.querySelectorAll('[data-availability-date]')];
    const preview = form.querySelector('[data-availability-preview]');
    const vehicleSelect = form.querySelector('[data-availability-vehicle]');
    let timer;
    const check = async () => {
        const start = dates[0]?.value;
        const end = dates[1]?.value;
        if (!start || !end) return;
        preview.textContent = 'Ketersediaan sedang diperiksa…';
        clearTimeout(timer);
        timer = setTimeout(async () => {
            try {
                const response = await fetch(`${form.dataset.availabilityUrl}?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('availability');
                const data = await response.json();
                const vehicles = data.vehicles || [];
                const available = vehicles.filter((vehicle) => vehicle.available);
                const unavailable = vehicles.filter((vehicle) => !vehicle.available);
                [...(vehicleSelect?.options || [])].forEach((option, index) => {
                    if (index > 0) option.disabled = false;
                });
                unavailable.forEach((vehicle) => {
                    const id = vehicle.id ?? vehicle.vehicle?.id;
                    const option = [...(vehicleSelect?.options || [])].find((item) => item.value === String(id));
                    if (option) option.disabled = true;
                });
                if (vehicleSelect?.value && unavailable.some((vehicle) => String(vehicle.id ?? vehicle.vehicle?.id) === vehicleSelect.value)) vehicleSelect.value = '';
                preview.innerHTML = `<strong>${available.length} unit dapat dipilih</strong> dari ${vehicles.length} unit${unavailable.length ? `<span class="availability-unavailable">Tidak tersedia: ${unavailable.map((vehicle) => `${vehicle.plate || vehicle.vehicle?.plate || 'Unit'} — ${vehicle.reason || 'Tidak tersedia'}`).join(' · ')}</span>` : '<span>Semua unit dapat dipilih.</span>'}`;
            } catch { preview.textContent = 'Preview belum dapat dimuat. Data akan tetap divalidasi saat disimpan.'; }
        }, 220);
    };
    dates.forEach((input) => input.addEventListener('change', check));

    const pricePreview = form.querySelector('[data-price-preview]');
    const previewPrice = async () => {
        if (!pricePreview || !vehicleSelect?.value || !dates[0]?.value || !dates[1]?.value) return;
        try {
            const response = await fetch(`/rentals/price-preview?vehicle_id=${vehicleSelect.value}&start_date=${dates[0].value}&end_date=${dates[1].value}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('price');
            const data = await response.json();
            const money = (cents) => `$${(cents / 100).toFixed(2)}`;
            pricePreview.innerHTML = `<strong>Rincian harga</strong><span>Durasi: ${data.duration_days} hari</span><span>Tarif harian: ${money(data.daily_rate_cents)}</span><span>Subtotal: ${money(data.subtotal_cents)}</span><span>Diskon: ${money(data.discount_cents)}</span><strong>Total: ${money(data.total_cents)}</strong>`;
        } catch { pricePreview.textContent = 'Rincian harga belum dapat dimuat.'; }
    };
    dates.forEach((input) => input.addEventListener('change', previewPrice));
    vehicleSelect?.addEventListener('change', previewPrice);
});

// Vehicle forms use only persisted Brand/Type associations supplied by the server.
// Changing Brand intentionally clears Type so a stale combination cannot be submitted.
document.querySelectorAll('[data-brand-type-form]').forEach((form) => {
    const brand = form.querySelector('[data-brand-select]');
    const type = form.querySelector('[data-type-select]');
    if (!brand || !type) return;

    let typeBrandMap = {};
    try { typeBrandMap = JSON.parse(form.dataset.typeBrandMap || '{}'); } catch { typeBrandMap = {}; }

    const filterTypes = (preserve = false) => {
        const allowed = new Set((typeBrandMap[brand.value] || []).map(String));
        const current = preserve ? (type.dataset.initialValue || type.value) : '';
        [...type.options].forEach((option, index) => {
            if (index === 0) return;
            option.hidden = !allowed.has(String(option.value));
            option.disabled = !allowed.has(String(option.value));
        });
        type.disabled = !brand.value;
        type.options[0].textContent = brand.value ? 'Pilih tipe' : 'Pilih merk terlebih dahulu';
        if (current && allowed.has(String(current))) type.value = current;
        else type.value = '';
    };

    filterTypes(true);
    brand.addEventListener('change', () => filterTypes(false));
});
