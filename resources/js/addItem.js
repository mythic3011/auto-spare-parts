// Form submission and ID generation belong to addItem.html.
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('file');
    const fileChosen = document.getElementById('file-chosen');
    fileInput?.addEventListener('change', () => {
        if (fileChosen) fileChosen.textContent = fileInput.files?.[0]?.name || 'No file chosen';
    });
});
