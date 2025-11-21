// Password Toggle Function
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const toggle = input.nextElementSibling;
    if (!toggle) return;
    
    const icon = toggle.querySelector('.toggle-icon');
    if (!icon) return;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🙈';
    } else {
        input.type = 'password';
        icon.textContent = '👁️';
    }
}

// Password Strength Checker
function checkPasswordStrength(password) {
    const requirements = {
        length: password.length >= 8,
        upper: /[A-Z]/.test(password),
        lower: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
    };
    
    let strength = 0;
    let count = 0;
    
    Object.keys(requirements).forEach(req => {
        const element = document.getElementById('req-' + req);
        if (element) {
            if (requirements[req]) {
                element.querySelector('.req-icon').textContent = '✅';
                element.style.color = '#10b981';
                strength++;
            } else {
                element.querySelector('.req-icon').textContent = '❌';
                element.style.color = '#ef4444';
            }
            count++;
        }
    });
    
    // Update strength bar
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    
    if (strengthBar && strengthText) {
        const percentage = (strength / count) * 100;
        strengthBar.style.width = percentage + '%';
        
        if (strength === 0) {
            strengthBar.style.backgroundColor = '#ef4444';
            strengthText.textContent = 'Password strength: Very Weak';
            strengthText.style.color = '#ef4444';
        } else if (strength === 1) {
            strengthBar.style.backgroundColor = '#f59e0b';
            strengthText.textContent = 'Password strength: Weak';
            strengthText.style.color = '#f59e0b';
        } else if (strength === 2) {
            strengthBar.style.backgroundColor = '#f59e0b';
            strengthText.textContent = 'Password strength: Fair';
            strengthText.style.color = '#f59e0b';
        } else if (strength === 3) {
            strengthBar.style.backgroundColor = '#3b82f6';
            strengthText.textContent = 'Password strength: Good';
            strengthText.style.color = '#3b82f6';
        } else if (strength === 4) {
            strengthBar.style.backgroundColor = '#10b981';
            strengthText.textContent = 'Password strength: Strong';
            strengthText.style.color = '#10b981';
        } else if (strength === 5) {
            strengthBar.style.backgroundColor = '#10b981';
            strengthText.textContent = 'Password strength: Very Strong';
            strengthText.style.color = '#10b981';
        }
    }
    
    return strength >= 5;
}

// Initialize password strength checker ONLY on register page (not login)
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    // Only initialize on registration pages (which have confirm_password field)
    if (passwordInput && confirmPasswordInput) {
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
    }
});

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form.auth-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            // ONLY check password strength on REGISTRATION pages (when confirm_password field exists)
            // Login pages should NOT have password strength requirements
            if (confirmPasswordInput && passwordInput) {
                // This is a registration form - check password strength
                if (!checkPasswordStrength(passwordInput.value)) {
                    e.preventDefault();
                    alert('Please ensure all password requirements are met.');
                    return false;
                }
                
                // Check if passwords match
                if (passwordInput.value !== confirmPasswordInput.value) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
            }
            
            // Show loading state
            const submitBtn = form.querySelector('.auth-submit');
            if (submitBtn) {
                submitBtn.disabled = true;
                const btnText = submitBtn.querySelector('.btn-text');
                const btnLoader = submitBtn.querySelector('.btn-loader');
                if (btnText) btnText.textContent = 'Processing...';
                if (btnLoader) btnLoader.style.display = 'inline-block';
            }
        });
    });
});

