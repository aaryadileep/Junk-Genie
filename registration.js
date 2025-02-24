document.getElementById('email').addEventListener('blur', function () {
    checkAvailability('email', this.value);
});

document.getElementById('phone').addEventListener('blur', function () {
    checkAvailability('phone', this.value);
});

function checkAvailability(field, value) {
    const formData = new FormData();
    formData.append(field, value);

    fetch('check_availability.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "exists") {
            document.getElementById(field + 'Error').textContent = field.charAt(0).toUpperCase() + field.slice(1) + " is already in use!";
        } else {
            document.getElementById(field + 'Error').textContent = '';
        }
    });
}
