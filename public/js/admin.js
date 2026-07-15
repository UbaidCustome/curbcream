window.AdminUI = {
    busy: false,

    toast(message, type = 'success') {
        const stack = document.getElementById('toast-stack');
        if (!stack || !message) return;
        const el = document.createElement('div');
        el.className = `admin-toast ${type}`;
        el.textContent = message;
        stack.appendChild(el);
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transition = 'opacity .25s ease';
            setTimeout(() => el.remove(), 250);
        }, 3200);
    },

    setLoading(btn, loading) {
        if (!btn) return;
        const label = btn.querySelector('.btn-label');
        const loader = btn.querySelector('.btn-loader');
        btn.disabled = loading;
        if (label && loader) {
            label.classList.toggle('d-none', loading);
            loader.classList.toggle('d-none', !loading);
        }
    },

    lockAllActions(activeBtn) {
        this.busy = true;
        document.querySelectorAll('.js-action-form button, .btn-action').forEach((btn) => {
            btn.disabled = true;
        });
        if (activeBtn) {
            this.setLoading(activeBtn, true);
            activeBtn.disabled = true;
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-action-form').forEach((form) => {
        form.addEventListener('submit', function (event) {
            if (AdminUI.busy) {
                event.preventDefault();
                return;
            }

            const submitter = event.submitter || form.querySelector('.btn-action') || form.querySelector('button[type="submit"]');
            AdminUI.lockAllActions(submitter);
        });
    });

    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }
});
