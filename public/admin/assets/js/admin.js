(function () {
    'use strict';

    var body = document.body;
    var toggle = document.querySelector('[data-admin-sidebar-toggle]');
    var sidebarKey = 'qlpt-admin-sidebar-collapsed';

    function isDesktop() {
        return window.matchMedia('(min-width: 992px)').matches;
    }

    function applySavedSidebarState() {
        if (!isDesktop()) {
            body.classList.remove('admin-sidebar-collapsed');
            return;
        }

        body.classList.toggle('admin-sidebar-collapsed', window.localStorage.getItem(sidebarKey) === '1');
        body.classList.remove('admin-sidebar-open');
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            if (isDesktop()) {
                var collapsed = !body.classList.contains('admin-sidebar-collapsed');
                body.classList.toggle('admin-sidebar-collapsed', collapsed);
                window.localStorage.setItem(sidebarKey, collapsed ? '1' : '0');
                return;
            }

            body.classList.toggle('admin-sidebar-open');
        });
    }

    document.addEventListener('click', function (event) {
        if (isDesktop() || !body.classList.contains('admin-sidebar-open')) {
            return;
        }

        if (event.target.closest('[data-admin-sidebar]') || event.target.closest('[data-admin-sidebar-toggle]')) {
            return;
        }

        body.classList.remove('admin-sidebar-open');
    });

    window.addEventListener('resize', applySavedSidebarState);
    applySavedSidebarState();

    function formatCounterValue(value, isCurrency) {
        var rounded = Math.round(Number(value) || 0);
        var formatted = new Intl.NumberFormat('vi-VN').format(rounded);
        return isCurrency ? formatted + ' VNĐ' : formatted;
    }

    window.animateCounter = function animateCounter(element, target, duration, isCurrency) {
        if (!element) {
            return;
        }

        var end = Math.max(0, Number(target) || 0);
        var runTime = Math.min(1500, Math.max(800, Number(duration) || 1100));
        var startAt = window.performance.now();

        function frame(now) {
            var progress = Math.min(1, (now - startAt) / runTime);
            var eased = 1 - Math.pow(1 - progress, 3);
            element.textContent = formatCounterValue(end * eased, Boolean(isCurrency));
            if (progress < 1) {
                window.requestAnimationFrame(frame);
            }
        }

        element.textContent = formatCounterValue(0, Boolean(isCurrency));
        window.requestAnimationFrame(frame);
    };

    var confirmModalElement = document.getElementById('adminConfirmModal');
    var pendingForm = null;

    document.querySelectorAll('form[onsubmit]').forEach(function (form) {
        var inlineHandler = form.getAttribute('onsubmit') || '';
        var confirmMatch = inlineHandler.match(/confirm\((['"])(.*?)\1\)/);
        if (!confirmMatch || inlineHandler.indexOf('prompt(') !== -1) {
            return;
        }

        form.dataset.adminConfirm = confirmMatch[2];
        form.removeAttribute('onsubmit');
    });

    function requestConfirmation(form) {
        if (!confirmModalElement || !window.bootstrap) {
            return window.confirm(form.dataset.adminConfirm || 'Xác nhận thao tác này?');
        }

        pendingForm = form;
        var bodyText = confirmModalElement.querySelector('[data-admin-confirm-body]');
        if (bodyText) {
            bodyText.textContent = form.dataset.adminConfirm || 'Thao tác này sẽ thay đổi dữ liệu hiện tại.';
        }
        window.bootstrap.Modal.getOrCreateInstance(confirmModalElement).show();
        return false;
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.adminConfirmed === '1') {
            return;
        }

        if (form.dataset.adminReject) {
            var reason = window.prompt(form.dataset.adminReject);
            var input = form.querySelector('[name="rejection_reason"]');
            if (!reason || !input) {
                event.preventDefault();
                return;
            }
            input.value = reason.trim();
        }

        if (form.dataset.adminConfirm) {
            event.preventDefault();
            requestConfirmation(form);
        }
    });

    var confirmSubmit = document.querySelector('[data-admin-confirm-submit]');
    if (confirmSubmit) {
        confirmSubmit.addEventListener('click', function () {
            if (!pendingForm) {
                return;
            }
            pendingForm.dataset.adminConfirmed = '1';
            pendingForm.requestSubmit();
        });
    }
}());
