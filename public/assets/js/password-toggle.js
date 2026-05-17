class PasswordInput {
    constructor(input) {
        this.input = input;
        this.root = input.closest('[data-password-input]') || input.closest('.input-group') || input.parentElement;
        this.button = this.root.querySelector('[data-password-toggle]') || this.createButton();
        this.bind();
        this.sync(false);
    }

    createButton() {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.passwordToggle = 'true';
        this.root.appendChild(button);
        return button;
    }

    bind() {
        if (!this.input.id) {
            const baseName = this.input.name || 'password';
            this.input.id = `password_${baseName.replace(/[^a-zA-Z0-9_-]/g, '_')}`;
        }

        this.root.classList.add('password-input');
        this.root.dataset.passwordInput = 'true';
        this.input.dataset.passwordField = 'true';
        this.input.setAttribute('autocomplete', this.input.getAttribute('autocomplete') || 'current-password');

        this.button.className = 'password-toggle password-input__toggle';
        this.button.setAttribute('aria-controls', this.input.id);
        this.button.addEventListener('click', () => {
            this.input.type = this.input.type === 'password' ? 'text' : 'password';
            this.sync(true);
            this.input.focus({ preventScroll: true });
        });
    }

    sync(animated) {
        const visible = this.input.type === 'text';
        this.button.setAttribute('aria-label', visible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        this.button.setAttribute('aria-pressed', visible ? 'true' : 'false');
        this.button.setAttribute('title', visible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        this.button.innerHTML = visible ? PasswordInput.icons.hidden : PasswordInput.icons.visible;

        if (animated) {
            this.button.classList.add('is-toggling');
            window.setTimeout(() => this.button.classList.remove('is-toggling'), 180);
        }
    }

    static mountAll(scope = document) {
        scope.querySelectorAll('input[type="password"], input[data-password-field="true"]').forEach((input) => {
            if (!input.dataset.passwordMounted) {
                input.dataset.passwordMounted = 'true';
                new PasswordInput(input);
            }
        });
    }
}

PasswordInput.icons = {
    visible: `
        <svg data-password-icon viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M2.75 12s3.25-6.25 9.25-6.25S21.25 12 21.25 12 18 18.25 12 18.25 2.75 12 2.75 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 15.25a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>`,
    hidden: `
        <svg data-password-icon viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="m4.75 4.75 14.5 14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M9.1 5.95A9.85 9.85 0 0 1 12 5.5c6 0 9.25 6.5 9.25 6.5a16.15 16.15 0 0 1-3.07 3.88" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M14.12 14.12A3 3 0 0 1 9.88 9.88" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M6.42 7.28C4.02 8.86 2.75 12 2.75 12s3.25 6.5 9.25 6.5c1.35 0 2.57-.33 3.66-.84" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>`,
};

document.addEventListener('DOMContentLoaded', () => PasswordInput.mountAll());
