export class PasswordToggle {
    constructor() {
        this.init();
    }

    init() {
        this.passwordToggleHandler();
    }

    passwordToggleHandler() {
        const formPasswordToggle = document.querySelectorAll('.form-password-toggle');

        formPasswordToggle.forEach(formPasswordToggle => {
            formPasswordToggle.addEventListener('click', () => {
                const input = formPasswordToggle.parentElement.querySelector('.form-input');
                input.type = input.type === 'password' ? 'text' : 'password';

                const isShowPassword = input.type === 'text';
                formPasswordToggle.innerHTML = isShowPassword ? '<i class="iconoir-eye-closed"></i>' : '<i class="iconoir-eye-solid"></i>';
            });
        });
    }
}
