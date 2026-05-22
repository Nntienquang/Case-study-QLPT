(function () {
    'use strict';

    var dashboard = document.querySelector('[data-admin-dashboard]');
    if (!dashboard) {
        return;
    }

    var endpoints = {
        stats: dashboard.dataset.statsEndpoint,
        revenue: dashboard.dataset.revenueEndpoint,
        bookings: dashboard.dataset.bookingEndpoint,
        rooms: dashboard.dataset.roomEndpoint,
        pageViews: dashboard.dataset.pageViewsEndpoint
    };
    var money = new Intl.NumberFormat('vi-VN');
    var palette = {
        blue: '#0d6efd',
        green: '#198754',
        amber: '#f59e0b',
        red: '#dc3545',
        cyan: '#0891b2',
        muted: '#98a2b3'
    };

    function getJson(url) {
        return window.fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'fetch'
            }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Dashboard request failed: ' + response.status);
            }
            return response.json();
        });
    }

    function setLoading(card, loading) {
        if (card) {
            card.classList.toggle('is-loading', Boolean(loading));
        }
    }

    function moneyLabel(value) {
        return money.format(Math.round(Number(value) || 0)) + ' VNĐ';
    }

    function renderCounters(stats) {
        document.querySelectorAll('[data-stat-key]').forEach(function (element) {
            var key = element.dataset.statKey;
            var fallback = Number(element.dataset.target || 0);
            var target = stats && Object.prototype.hasOwnProperty.call(stats, key) ? stats[key] : fallback;
            element.dataset.target = String(target || 0);
            window.animateCounter(element, target, element.dataset.duration || 1100, element.dataset.currency === '1');
        });
    }

    renderCounters(null);
    getJson(endpoints.stats).then(renderCounters).catch(function () {
        renderCounters(null);
    });

    if (!window.ApexCharts) {
        return;
    }

    var revenueElement = document.getElementById('adminRevenueChart');
    var bookingElement = document.getElementById('adminBookingStatusChart');
    var roomElement = document.getElementById('adminRoomStatusChart');
    var pageViewsElement = document.getElementById('adminPageViewsChart');
    var revenueCard = revenueElement ? revenueElement.closest('.admin-chart-card') : null;
    var pageViewsCard = pageViewsElement ? pageViewsElement.closest('.admin-chart-card') : null;
    var revenueChart = null;
    var pageViewsChart = null;

    if (revenueElement) {
        revenueChart = new window.ApexCharts(revenueElement, {
            chart: {
                type: 'area',
                height: 340,
                toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 750 }
            },
            colors: [palette.blue, palette.green],
            dataLabels: { enabled: false },
            fill: {
                type: 'gradient',
                gradient: { opacityFrom: .3, opacityTo: .04, stops: [0, 92, 100] }
            },
            grid: { borderColor: '#e7eefb', strokeDashArray: 4 },
            legend: { position: 'top', horizontalAlign: 'left' },
            markers: { size: 3, hover: { size: 5 } },
            series: [],
            stroke: { curve: 'smooth', width: 2.5 },
            tooltip: { y: { formatter: moneyLabel } },
            xaxis: { categories: [], labels: { style: { colors: '#667085' } } },
            yaxis: { labels: { formatter: moneyLabel } }
        });
        revenueChart.render();
    }

    function loadRevenue(query) {
        if (!revenueChart) {
            return;
        }

        setLoading(revenueCard, true);
        getJson(endpoints.revenue + '?' + query).then(function (payload) {
            revenueChart.updateOptions({
                xaxis: { categories: payload.labels || [] }
            }, false, true);
            revenueChart.updateSeries(payload.series || [], true);
        }).catch(function () {
            revenueChart.updateOptions({ xaxis: { categories: [] } }, false, true);
            revenueChart.updateSeries([], true);
        }).finally(function () {
            setLoading(revenueCard, false);
        });
    }

    function activateRangeButton(button) {
        document.querySelectorAll('[data-revenue-range]').forEach(function (item) {
            item.classList.toggle('btn-primary', item === button);
            item.classList.toggle('btn-outline-primary', item !== button);
        });
    }

    document.querySelectorAll('[data-revenue-range]').forEach(function (button) {
        button.addEventListener('click', function () {
            activateRangeButton(button);
            loadRevenue('range=' + window.encodeURIComponent(button.dataset.revenueRange));
        });
    });

    var customRangeForm = document.querySelector('[data-revenue-custom]');
    if (customRangeForm) {
        customRangeForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var formData = new window.FormData(customRangeForm);
            var start = formData.get('start');
            var end = formData.get('end');
            if (!start || !end) {
                return;
            }
            document.querySelectorAll('[data-revenue-range]').forEach(function (item) {
                item.classList.remove('btn-primary');
                item.classList.add('btn-outline-primary');
            });
            loadRevenue('range=custom&start=' + window.encodeURIComponent(start) + '&end=' + window.encodeURIComponent(end));
        });
    }

    loadRevenue('range=7');

    function renderDonut(element, payload, colors) {
        if (!element) {
            return;
        }

        new window.ApexCharts(element, {
            chart: {
                type: 'donut',
                height: 300,
                animations: { enabled: true, easing: 'easeinout', speed: 700 }
            },
            colors: colors,
            dataLabels: { enabled: false },
            labels: payload.labels || [],
            legend: { position: 'bottom' },
            plotOptions: { pie: { donut: { size: '60%' } } },
            series: payload.series || [],
            stroke: { colors: ['#fff'], width: 2 },
            tooltip: { y: { formatter: function (value) { return money.format(Number(value) || 0); } } }
        }).render();
    }

    getJson(endpoints.bookings).then(function (payload) {
        renderDonut(bookingElement, payload, [palette.amber, palette.green, palette.blue, palette.cyan, palette.red, palette.muted]);
    }).catch(function () {
        renderDonut(bookingElement, { labels: [], series: [] }, []);
    });

    if (roomElement) {
        getJson(endpoints.rooms).then(function (payload) {
            new window.ApexCharts(roomElement, {
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: { show: false },
                    animations: { enabled: true, easing: 'easeinout', speed: 700 }
                },
                colors: [palette.amber, palette.green, palette.red, palette.muted],
                dataLabels: { enabled: false },
                grid: { borderColor: '#e7eefb', strokeDashArray: 4 },
                plotOptions: { bar: { borderRadius: 5, columnWidth: '44%', distributed: true } },
                series: [{ name: 'Phòng', data: payload.series || [] }],
                tooltip: { y: { formatter: function (value) { return money.format(Number(value) || 0) + ' phòng'; } } },
                xaxis: { categories: payload.labels || [] },
                yaxis: { min: 0, forceNiceScale: true }
            }).render();
        }).catch(function () {
            roomElement.textContent = '';
        });
    }

    if (pageViewsElement) {
        pageViewsChart = new window.ApexCharts(pageViewsElement, {
            chart: {
                type: 'area',
                height: 300,
                toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 700 }
            },
            colors: [palette.cyan],
            dataLabels: { enabled: false },
            fill: {
                type: 'gradient',
                gradient: { opacityFrom: .28, opacityTo: .05, stops: [0, 92, 100] }
            },
            grid: { borderColor: '#e7eefb', strokeDashArray: 4 },
            markers: { size: 3 },
            series: [],
            stroke: { curve: 'smooth', width: 2.5 },
            tooltip: { y: { formatter: function (value) { return money.format(Number(value) || 0) + ' lượt'; } } },
            xaxis: { categories: [] },
            yaxis: { min: 0, forceNiceScale: true }
        });
        pageViewsChart.render();
    }

    function loadPageViews(range) {
        if (!pageViewsChart || !endpoints.pageViews) {
            return;
        }
        setLoading(pageViewsCard, true);
        getJson(endpoints.pageViews + '?range=' + window.encodeURIComponent(range)).then(function (payload) {
            pageViewsChart.updateOptions({ xaxis: { categories: payload.labels || [] } }, false, true);
            pageViewsChart.updateSeries(payload.series || [], true);
        }).catch(function () {
            pageViewsChart.updateOptions({ xaxis: { categories: [] } }, false, true);
            pageViewsChart.updateSeries([], true);
        }).finally(function () {
            setLoading(pageViewsCard, false);
        });
    }

    document.querySelectorAll('[data-traffic-range]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('[data-traffic-range]').forEach(function (item) {
                item.classList.toggle('btn-primary', item === button);
                item.classList.toggle('btn-outline-primary', item !== button);
            });
            loadPageViews(button.dataset.trafficRange);
        });
    });

    loadPageViews('7');
}());
