/*
 * Shared admin trash-reason viewer.
 *
 * Active records never render this control. On trashed datasets,
 * long reasons are truncated in the table and can be opened in the
 * existing application alert modal without introducing another modal
 * implementation.
 */
document.addEventListener('click', function (event) {
    const button = event.target.closest('.admin-trash-reason-view');

    if (!button) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const reason = String(button.dataset.trashReason || '').trim();

    if (!reason || typeof window.showAlertModal !== 'function') {
        return;
    }

    window.showAlertModal({
        title: 'Trash Reason',
        text: reason,
        icon: 'ph-light ph-info',
        variant: 'info',
        confirmText: 'Close',
        showCancel: false,
        showConfirm: true,
        loading: false,
    });
});
