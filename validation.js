function setValid(element, feedback = '') {
    element.classList.remove('is-invalid');
    element.classList.add('is-valid');
    const feedbackDiv = element.nextElementSibling;
    if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
        feedbackDiv.style.display = 'none';
    }
}

function setInvalid(element, message) {
    element.classList.remove('is-valid');
    element.classList.add('is-invalid');
    const feedbackDiv = element.nextElementSibling;
    if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
        feedbackDiv.textContent = message;
        feedbackDiv.style.display = 'block';
    }
}

function validateFullname(input) {
    const value = input.value.trim();
    const regex = /^[A-Z][a-zA-Z\s]{2,}$/;
    if (!regex.test(value)) {
        setInvalid(input, 'Full name must start with a capital letter, be at least 3 characters long, and contain only letters and spaces.');
        return false;
    }
    setValid(input);
    return true;
}

function validatePhone(input) {
    const value = input.value.trim();
    const regex = /^[6-9]\d{9}$/;
    if (!regex.test(value)) {
        setInvalid(input, 'Phone number must be 10 digits and start with 6, 7, 8, or 9.');
        return false;
    }
    setValid(input);
    return true;
}

function validatePassword(input) {
    const value = input.value;
    if (value.length < 8 || !/[A-Za-z]/.test(value) || !/[0-9]/.test(value)) {
        setInvalid(input, 'Password must be at least 8 characters long and contain both letters and numbers.');
        return false;
    }
    setValid(input);
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');

    form.fullname.addEventListener('input', () => validateFullname(form.fullname));
    form.phone.addEventListener('input', () => validatePhone(form.phone));
    form.password.addEventListener('input', () => validatePassword(form.password));
});