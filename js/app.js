(function() {
    'use strict';

    // ── Form Submission Helper (avoids HTML escaping issues in onclick) ──
    window.submitForm = function(data) {
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = window.location.href;
        var csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = window.csrfToken || '';
        f.appendChild(csrf);
        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = key;
                inp.value = data[key];
                f.appendChild(inp);
            }
        }
        document.body.appendChild(f);
        f.submit();
    };

    // ── Confirm Helper using submitForm ──
    window.confirmDelete = function(message, data) {
        window.confirmAction(message, function() {
            window.submitForm(data);
        });
    };

    // ── Toast Notification System ──
    window.showToast = function(message, type) {
        type = type || 'success';
        var container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px';
            document.body.appendChild(container);
        }
        var colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#667eea' };
        var icons = { success: 'bi-check-circle-fill', error: 'bi-exclamation-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
        var toast = document.createElement('div');
        toast.style.cssText = 'display:flex;align-items:center;gap:10px;padding:14px 20px;border-radius:10px;color:#fff;font-weight:500;font-size:14px;box-shadow:0 10px 30px rgba(0,0,0,0.15);transform:translateX(120%);opacity:0;transition:all 0.4s cubic-bezier(0.68,-0.55,0.265,1.55);min-width:300px;max-width:450px';
        toast.style.background = colors[type] || colors.success;
        toast.innerHTML = '<i class="bi ' + (icons[type] || icons.success) + '" style="font-size:18px"></i> ' + message;
        container.appendChild(toast);
        requestAnimationFrame(function() {
            toast.style.transform = 'translateX(0)';
            toast.style.opacity = '1';
        });
        setTimeout(function() {
            toast.style.transform = 'translateX(120%)';
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 400);
        }, 4000);
    };

    // ── DataTables Initialization ──
    function initDataTables() {
        var tables = document.querySelectorAll('.table-dt');
        tables.forEach(function(table) {
            if (table.id && $.fn.DataTable.isDataTable('#' + table.id)) return;
            if ($.fn.DataTable.isDataTable(table)) return;

            var hasSerial = table.getAttribute('data-serial') !== 'false';
            if (hasSerial) {
                $(table).find('thead tr').prepend('<th class="dt-sno" style="width:50px;min-width:50px">#</th>');
                $(table).find('tbody tr').each(function() {
                    $(this).prepend('<td class="dt-sno text-center"></td>');
                });
            }

            var config = {
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                order: [],
                responsive: true,
                dom: '<"dt-top"fB>rt<"dt-bottom"lip>',
                buttons: [
                    {
                        extend: 'collection',
                        text: '<i class="bi bi-download"></i> Export',
                        buttons: [
                            { extend: 'copy', text: '<i class="bi bi-clipboard"></i> Copy' },
                            { extend: 'csv', text: '<i class="bi bi-filetype-csv"></i> CSV' },
                            { extend: 'excel', text: '<i class="bi bi-file-earmark-excel"></i> Excel' },
                            { extend: 'print', text: '<i class="bi bi-printer"></i> Print' }
                        ]
                    },
                    {
                        extend: 'colvis',
                        text: '<i class="bi bi-eye"></i> Columns'
                    }
                ],
                language: {
                    search: '<i class="bi bi-search"></i>',
                    searchPlaceholder: 'Search...',
                    lengthMenu: '_MENU_ per page',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries',
                    infoFiltered: '(filtered from _MAX_ total)',
                    loadingRecords: '<div class="dt-loader"><div class="spinner"></div></div>',
                    processing: '<div class="dt-loader"><div class="spinner"></div>Processing...</div>'
                },
                columnDefs: hasSerial ? [{ targets: 0, orderable: false, searchable: false }] : [],
                initComplete: function() {
                    var wrapper = $(this).closest('.dataTables_wrapper');
                    var searchInput = wrapper.find('.dataTables_filter input');
                    searchInput.addClass('form-control form-control-sm dt-search-input');
                    searchInput.attr('placeholder', 'Search...');
                    var lengthSelect = wrapper.find('.dataTables_length select');
                    lengthSelect.addClass('form-select form-select-sm');
                },
                drawCallback: function() {
                    var api = this.api();
                    var wrapper = $(this).closest('.dataTables_wrapper');
                    wrapper.find('.dataTables_empty').html('<div class="empty-state"><i class="bi bi-inbox"></i><p>No data available</p></div>');
                    if (hasSerial) {
                        api.rows({ page: 'current' }).every(function() {
                            var idx = this.index('row');
                            $(this.node()).find('td.dt-sno').text(api.page.info().start + idx + 1);
                        });
                    }
                }
            };

            if (table.getAttribute('data-sort') === 'false') {
                config.order = [];
                config.ordering = false;
            } else {
                var sortCol = table.getAttribute('data-sort-col');
                var sortDir = table.getAttribute('data-sort-dir') || 'asc';
                if (sortCol !== null) {
                    config.order = [[parseInt(sortCol) + (hasSerial ? 1 : 0), sortDir]];
                }
            }

            $(table).DataTable(config);
        });
    }

    // ── Modal Helpers ──
    window.openModal = function(modalId, title, content) {
        var modal = document.getElementById(modalId);
        if (!modal) return;
        var modalTitle = modal.querySelector('.modal-title');
        var modalBody = modal.querySelector('.modal-body');
        if (modalTitle && title) modalTitle.innerHTML = title;
        if (modalBody && content !== undefined) modalBody.innerHTML = content;
        var bsModal = new bootstrap.Modal(modal, { backdrop: 'static', keyboard: false });
        bsModal.show();
    };

    window.closeModal = function(modalId) {
        var modalEl = document.getElementById(modalId);
        if (modalEl) {
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
    };

    function initModalForms() {
        document.addEventListener('click', function(e) {
            var btn = e.target && typeof e.target.closest === 'function' ? e.target.closest('[data-modal]') : null;
            if (!btn) return;
            var target = btn.getAttribute('data-modal');
            var modalEl = document.getElementById(target);
            if (!modalEl) return;
            var form = modalEl.querySelector('form.modal-form');
            if (form) {
                var action = btn.getAttribute('data-action') || form.getAttribute('action') || '';
                form.action = action;
                var inputs = form.querySelectorAll('[data-fill]');
                inputs.forEach(function(inp) {
                    var key = inp.getAttribute('data-fill');
                    var val = btn.getAttribute('data-' + key) || '';
                    if (inp.type === 'checkbox') {
                        inp.checked = val === '1' || val === 'true';
                    } else {
                        inp.value = val;
                    }
                    if (inp.tagName === 'SELECT' && window.$ && $.fn.select2) {
                        $(inp).val(val).trigger('change');
                    }
                });
            }
            var titleEl = modalEl.querySelector('.modal-title');
            var title = btn.getAttribute('data-title') || titleEl?.textContent || '';
            if (titleEl) titleEl.textContent = title;
            initSelect2(modalEl);
            var bsModal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
            bsModal.show();
        });
    }

    // ── Confirm Dialog ──
    window.confirmAction = function(message, callback) {
        var modal = document.getElementById('confirmModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'confirmModal';
            modal.innerHTML = '<div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content"><div class="modal-body text-center py-4"><i class="bi bi-exclamation-triangle" style="font-size:48px;color:#f59e0b;display:block;margin-bottom:12px"></i><p id="confirmMsg" style="font-size:16px;font-weight:500;margin:0"></p></div><div class="modal-footer border-0 justify-content-center pt-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" id="confirmOk" class="btn btn-danger">Confirm</button></div></div></div>';
            document.body.appendChild(modal);
        }
        modal.querySelector('#confirmMsg').textContent = message;
        var bsModal = new bootstrap.Modal(modal, { backdrop: 'static' });
        var okBtn = modal.querySelector('#confirmOk');
        var newOk = okBtn.cloneNode(true);
        okBtn.parentNode.replaceChild(newOk, okBtn);
        newOk.addEventListener('click', function() {
            bsModal.hide();
            if (typeof callback === 'function') callback();
        });
        bsModal.show();
    };

    // ── Form Validation Enhancement ──
    function initFormValidation() {
        document.querySelectorAll('form.needs-validation').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    }

    // ── Sidebar Enhancement ──
    function initSidebar() {
        var sidebar = document.getElementById('sidebar');
        if (!sidebar) return;
        if (window.innerWidth > 768) {
            var activeLink = sidebar.querySelector('a.active');
            if (activeLink) {
                activeLink.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        }
    }

    // ── Expose for HTMX re-init ──
    window.initDataTables = initDataTables;
    window.initSelect2 = initSelect2;

    // ── Select2 Global Init ──
    function initSelect2(container) {
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') return;
        var ctx = container || document;
        $(ctx).find('select.form-select:not(.no-select2)').each(function() {
            var $this = $(this);
            if ($this.hasClass('select2-hidden-accessible') || $this.data('select2')) return;
            var placeholder = $this.attr('placeholder') || $this.find('option:first').text() || 'Select...';
            var parent = $this.closest('.modal').length ? $this.closest('.modal') : undefined;
            $this.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: placeholder,
                allowClear: true,
                dropdownParent: parent
            });
        });
    }

    // Re-init Select2 on modal show
    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('shown.bs.modal', '.modal', function() {
            initSelect2(this);
        });
    });

    // ── Loading Overlay ──
    function initLoadingOverlay() {
        document.querySelectorAll('form').forEach(function(form) {
            if (form.querySelector('button[type="submit"]') && !form.classList.contains('no-loading')) {
                form.addEventListener('submit', function() {
                    if (!form.checkValidity || form.checkValidity()) {
                        var overlay = document.createElement('div');
                        overlay.className = 'loading-overlay';
                        overlay.innerHTML = '<div class="spinner"></div>';
                        form.appendChild(overlay);
                    }
                });
            }
        });
    }



    // ── Initialize on DOM Ready ──
    document.addEventListener('DOMContentLoaded', function() {
        initDataTables();
        initModalForms();
        initFormValidation();
        initSidebar();
        initSelect2();

        // Auto-hide alerts
        document.querySelectorAll('.alert-auto').forEach(function(alert) {
            setTimeout(function() {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() { alert.remove(); }, 500);
            }, 4000);
        });

        // CSRF token refresh for AJAX
        if (window.csrfToken) {
            document.querySelectorAll('input[name="csrf_token"]').forEach(function(inp) {
                if (!inp.value) inp.value = window.csrfToken;
            });
        }
    });

})();
