// Toggle Availability
document.getElementById('toggle-availability').addEventListener('click', function () {
    fetch('toggle_availability.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const statusElement = document.getElementById('availability-status');
                const buttonElement = document.getElementById('toggle-availability');
                if (statusElement.textContent === 'Available') {
                    statusElement.textContent = 'Unavailable';
                    buttonElement.textContent = 'Set Available';
                } else {
                    statusElement.textContent = 'Available';
                    buttonElement.textContent = 'Set Unavailable';
                }
            }
        });
});

// Task Actions
document.querySelectorAll('.accept-btn, .reject-btn, .complete-btn').forEach(button => {
    button.addEventListener('click', function () {
        const taskId = this.getAttribute('data-task-id');
        const action = this.classList.contains('accept-btn') ? 'accept' :
                       this.classList.contains('reject-btn') ? 'reject' : 'complete';
        fetch('update_task_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ task_id: taskId, action: action })
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  location.reload();
              }
          });
    });
});