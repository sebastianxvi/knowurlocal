/* ========================
   KNOWURLOCAL DASHBOARD ENGINE
   Clean • Modular • Secure
======================== */
function printDashboard(){

    const overview = document.getElementById('overview');
    const analytics = document.getElementById('analytics');

    // 🔐 safety check (professional practice)
    if (!overview || !analytics) {
        console.error('Print sections not found');
        return;
    }

    overview.style.display = 'block';
    analytics.style.display = 'block';

    window.print();

    location.reload();
}

document.addEventListener('DOMContentLoaded', () => {

    

    /* =====================================================
       1. TAB SYSTEM (SAFE + URL SYNC)
    ===================================================== */
    const tabs = document.querySelectorAll('.dashboard-tabs .tab');
    const sections = document.querySelectorAll('.dashboard-section');

    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get('tab') || 'overview';

    function activateTab(tabName) {
        const button = document.querySelector(`.tab[data-tab="${tabName}"]`);
        const section = document.getElementById(tabName);

        if (!button || !section) return;

        tabs.forEach(t => t.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));

        button.classList.add('active');
        section.classList.add('active');
    }

    activateTab(tabFromUrl);

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {

            const target = tab.dataset.tab;
            const next = document.getElementById(target);

            if (!next) return;

            const newUrl = `${window.location.pathname}?tab=${target}`;
            window.history.pushState({}, '', newUrl);

            activateTab(target);
        });
    });

    /* =====================================================
       2. RANGE SWITCH (SAFE NAVIGATION)
    ===================================================== */
    document.querySelectorAll('.range-btn').forEach(btn => {
        btn.addEventListener('click', () => {

            const range = btn.dataset.range;
            if (!range) return;

            const url = new URL(window.location.href);
            url.searchParams.set('range', range);

            window.location.href = url.toString();
        });
    });

    /* =====================================================
       3. ANALYTICS ENGINE (APEXCHARTS)
    ===================================================== */

    const hasAnalyticsData =
        typeof chatTrend !== 'undefined' &&
        typeof responseLabels !== 'undefined' &&
        typeof responseValues !== 'undefined';

    if (hasAnalyticsData) {

        /* ================= DATA TRANSFORM ================= */
        const trendLabels = chatTrend.map(i => {
            const date = new Date(i.date);
            return date.toLocaleDateString('en-US', { weekday: 'short' });
        });

        const trendValues = chatTrend.map(i => i.count);

        const agencyLabels = (typeof agencyData !== 'undefined')
            ? agencyData.map(i => i.agency_name)
            : [];

        const agencyValues = (typeof agencyData !== 'undefined')
            ? agencyData.map(i => i.count)
            : [];

        const hasFeatureData =
            typeof featureLabels !== 'undefined' &&
            typeof featureValues !== 'undefined';

        /* ================= USAGE TREND ================= */
        new ApexCharts(usageEl, {
            chart: {
                type: 'area',
                height: 260,
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 600
                }
            },

            series: [{
                name: "Queries",
                data: trendValues
            }],

            stroke: {
                curve: 'smooth',
                width: 3
            },

            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05
                }
            },

            colors: ['#2563eb'],

            markers: {
                size: 4,
                hover: {
                    size: 6
                }
            },

            xaxis: {
                categories: trendLabels,
                labels: {
                    style: {
                        fontSize: '11px'
                    }
                }
            },

            yaxis: {
                labels: {
                    style: {
                        fontSize: '11px'
                    }
                }
            },

            grid: {
                borderColor: 'rgba(0,0,0,0.05)',
                strokeDashArray: 4
            },

            tooltip: {
                y: {
                    formatter: val => val + " queries"
                }
            }

        }).render();

        /* ================= RESPONSE ================= */
        const responseEl = document.querySelector("#accuracyChart");

        if (responseEl) {
            new ApexCharts(responseEl, {
                chart: { type: 'donut' },
                series: responseValues,
                labels: responseLabels,
                colors: ['#22c55e','#ef4444','#f59e0b','#3b82f6'],
                legend: { position: 'bottom', fontSize: '12px' }
            }).render();
        }

        /* ================= FEATURE ================= */
        const featureEl = document.querySelector("#featureChart");

        if (featureEl && hasFeatureData) {
            new ApexCharts(featureEl, {
                chart: { type: 'bar', height: 240, toolbar: { show: false } },
                series: [{ data: featureValues }],
                xaxis: { categories: featureLabels },
                colors: ['#10b981'],
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        columnWidth: '40%'
                    }
                }
            }).render();
        }

        /* ================= AGENCIES ================= */
        const agencyEl = document.querySelector("#agencyChart");

        if (agencyEl && agencyValues.length) {
            new ApexCharts(agencyEl, {
                chart: { type: 'bar', height: 240, toolbar: { show: false } },
                series: [{ data: agencyValues }],
                xaxis: { categories: agencyLabels },
                colors: ['#7c3aed'],
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        columnWidth: '40%'
                    }
                }
            }).render();
        }

       

    } else {
        console.warn('Analytics data missing');
    }

     /* ================= HEATMAP ================= */
    const heatmapEl = document.querySelector("#heatmapChart");

    if (heatmapEl && Array.isArray(window.heatmapData)) {

        new ApexCharts(heatmapEl, {
            chart: {
                type: 'heatmap',
                height: 260,
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 500
                }
            },

            series: window.heatmapData,

            dataLabels: {
                enabled: false // 🔐 prevents clutter (security of UI clarity)
            },

            colors: ["#1d4ed8"],

            plotOptions: {
                heatmap: {
                    shadeIntensity: 0.9, // stronger contrast

                    radius: 6, // smoother modern blocks

                    colorScale: {
                        ranges: [
                            { from: 0, to: 2, color: "#e0e7ff", name: "Low" },
                            { from: 3, to: 5, color: "#93c5fd", name: "Medium" },
                            { from: 6, to: 10, color: "#3b82f6", name: "High" },
                            { from: 11, to: 999, color: "#1d4ed8", name: "Peak" }
                        ]
                    }
                }
            },

            stroke: {
                width: 2,
                colors: ['#ffffff'] // gives grid separation (GitHub style)
            },

            xaxis: {
                categories: [
                    "12AM","1AM","2AM","3AM","4AM","5AM",
                    "6AM","7AM","8AM","9AM","10AM","11AM",
                    "12PM","1PM","2PM","3PM","4PM","5PM",
                    "6PM","7PM","8PM","9PM","10PM","11PM"
                ],
                labels: {
                    style: {
                        fontSize: '11px'
                    }
                }
            },

            yaxis: {
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },

            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + " queries";
                    }
                }
            },

            states: {
                hover: {
                    filter: {
                        type: 'darken',
                        value: 0.4
                    }
                }
            }

        }).render();
    }


    /* =====================================================
       4. UI POLISH (SKELETON + COUNT)
    ===================================================== */

    const statCards = document.querySelectorAll('.dashboard-card h2');

    statCards.forEach(el => {
        const original = el.innerText;

        el.classList.add('skeleton');
        // Keep original value intact
        el.classList.add('skeleton');

        setTimeout(() => {
            el.classList.remove('skeleton');
        }, 600 + Math.random() * 400);

        setTimeout(() => {
            el.classList.remove('skeleton');
            el.innerText = original;
        }, 600 + Math.random() * 400);
    });

    function animateValue(el, end) {
        let start = 0;
        const duration = 800;
        const startTime = performance.now();

        function update(currentTime) {
            const progress = Math.min((currentTime - startTime) / duration, 1);
            el.innerText = Math.floor(progress * end);

            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                el.innerText = end;
            }
        }

        requestAnimationFrame(update);
    }

    document.querySelectorAll('.dashboard-card h2').forEach(el => {

        const raw = el.innerText.replace(/[^\d.]/g, '');
        const value = parseFloat(raw);

        if (!value || isNaN(value)) return; // 🔐 safety

        el.innerText = '0'; // start animation from 0
        animateValue(el, value);
    });

});