document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = document.querySelectorAll('.prod-toggle-checkbox');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const imageId = this.dataset.id;
            const inProd = this.checked;

            fetch(`/api/launcherImages/${imageId}/toggle-prod`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ in_prod: inProd })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Success:', data.message);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. The status could not be updated.');
                    this.checked = !inProd;
                });
        });
    });
});
