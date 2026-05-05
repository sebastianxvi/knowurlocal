document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const form = this.closest('.delete-form');

      Swal.fire({
        icon: 'warning',
        title: 'Confirm delete',
        text: 'This will delete the FAQ.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
          popup: 'swal-delete'
        }
      }).then((result) => {
        if (result.isConfirmed) form.submit();
      });
    });
  });
});