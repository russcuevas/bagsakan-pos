/**
 * Bagsakan POS & Inventory - Global Client Logic
 * Handles Modals, AJAX form validation, Brand Toasts, Select2, DataTables, and Image validation
 */

// Toast notification helper
window.showToast = function (type = 'success', title = 'Notification', message = '') {
    let container = document.getElementById('globalToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'globalToastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-item toast-${type}`;

    let iconHtml = '<i class="bi bi-check-circle-fill"></i>';
    if (type === 'error') iconHtml = '<i class="bi bi-exclamation-octagon-fill"></i>';
    if (type === 'warning') iconHtml = '<i class="bi bi-exclamation-triangle-fill"></i>';
    if (type === 'info') iconHtml = '<i class="bi bi-info-circle-fill"></i>';

    toast.innerHTML = `
        <div class="toast-icon">${iconHtml}</div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button type="button" class="modal-close-btn" style="font-size: 0.9rem;" onclick="this.closest('.toast-item').remove()">
            <i class="bi bi-x"></i>
        </button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        if (toast && toast.parentNode) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(120%)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, 4500);
};

// Date / Time Formatting Helpers (e.g. 2026-10-02 / 12:26 PM)
window.formatDateTime = function (dateVal, withTime = true) {
    if (!dateVal) return 'N/A';
    try {
        const d = new Date(dateVal);
        if (isNaN(d.getTime())) return dateVal;

        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const ymd = `${year}-${month}-${day}`;

        if (!withTime) return ymd;

        let hours = d.getHours();
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const timeStr = `${hours}:${minutes} ${ampm}`;

        return `${ymd} / ${timeStr}`;
    } catch (e) {
        return dateVal;
    }
};

window.formatDateOnly = function (dateVal) {
    return window.formatDateTime(dateVal, false);
};

// Modal helpers
window.openModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
};

window.closeModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
        // Clear previous validation errors inside modal
        modal.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        modal.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    }
};

// Global DOM Ready Handlers
document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const mobileBtn = document.getElementById('mobileSidebarToggle');
    const sidebar = document.querySelector('.app-sidebar');
    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show-mobile');
        });
    }

    // 2. Initialize DataTables with Clean Theme & 10 Entries Default
    if (window.jQuery && $.fn.DataTable) {
        $('.datatable-init').each(function () {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    language: {
                        lengthMenu: "Show _MENU_ entries",
                        search: "_INPUT_",
                        searchPlaceholder: "Search records...",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                        paginate: {
                            next: '<i class="bi bi-chevron-right"></i>',
                            previous: '<i class="bi bi-chevron-left"></i>'
                        }
                    }
                });
            }
        });
    }

    // 3. Initialize Select2
    if (window.jQuery && $.fn.select2) {
        $('.select2-init').each(function () {
            const $this = $(this);
            const parentModal = $this.closest('.modal-overlay');
            $this.select2({
                dropdownParent: parentModal.length ? parentModal : $(document.body),
                width: '100%',
                placeholder: $this.data('placeholder') || 'Select an option'
            });
        });
    }

    // 4. Modal Dismiss Listeners
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                closeModal(overlay.id);
            }
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-close-modal');
            if (target) closeModal(target);
            else {
                const overlay = this.closest('.modal-overlay');
                if (overlay) closeModal(overlay.id);
            }
        });
    });

    // 5. Image Size Client Validation and Preview
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
        input.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                // Validate Max 2MB (2048 KB)
                const maxSizeInBytes = 2 * 1024 * 1024;
                if (file.size > maxSizeInBytes) {
                    showToast('error', 'File Too Large', `Image size (${(file.size / (1024 * 1024)).toFixed(2)} MB) exceeds maximum allowed limit of 2MB.`);
                    this.value = '';
                    return;
                }

                // Image Preview if target preview element exists
                const previewId = this.getAttribute('data-preview');
                if (previewId) {
                    const previewEl = document.getElementById(previewId);
                    if (previewEl) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            previewEl.src = e.target.result;
                            previewEl.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    }
                }
            }
        });
    });

    // 6. Generic AJAX Form Submit Handler for Modals
    // When validation fails: KEEP MODAL OPEN, show validation feedback on inputs.
    // When success: CLOSE MODAL, show brand toast, reload or callback.
    document.querySelectorAll('form.ajax-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Clear existing errors
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...';
            }

            const formData = new FormData(form);
            const url = form.action;
            const method = form.method || 'POST';

            fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    // Validation or business logic failure
                    if (response.status === 422 && data.errors) {
                        // Keep modal open, render validation errors directly on form fields
                        Object.keys(data.errors).forEach(field => {
                            let input = form.querySelector(`[name="${field}"]`) || form.querySelector(`[name="${field}[]"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'invalid-feedback';
                                errorDiv.innerText = data.errors[field][0];
                                input.parentNode.appendChild(errorDiv);
                            }
                        });
                        showToast('error', 'Validation Error', data.message || 'Please check the highlighted form errors.');
                    } else {
                        showToast('error', 'Action Failed', data.message || 'An error occurred while processing the request.');
                    }
                    throw new Error(data.message || 'Validation failed');
                }
                return data;
            })
            .then(data => {
                // Success: Close modal and trigger Toast
                const modal = form.closest('.modal-overlay');
                if (modal) {
                    closeModal(modal.id);
                }
                form.reset();

                showToast('success', 'Success!', data.message || 'Saved successfully.');

                // Reload page or execute callback after brief pause
                setTimeout(() => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.reload();
                    }
                }, 800);
            })
            .catch(err => {
                console.error(err);
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            });
        });
    });
});
