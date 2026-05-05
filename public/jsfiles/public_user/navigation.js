// ================= SAFE INIT =================
document.addEventListener('DOMContentLoaded', () => {

    // ================= DATA =================
    const mapEl = document.getElementById('navMap');
    if (!mapEl) return; // 🔒 prevent crashes

    const lat = Number(mapEl.dataset.lat);
    const lng = Number(mapEl.dataset.lng);
    const name = mapEl.dataset.name;

    // ================= ICONS =================
    const userIcon = L.divIcon({
        className: '',
        html: '<div class="user-marker"></div>',
        iconSize: [18, 18]
    });

    const destIcon = L.divIcon({
        className: '',
        html: '<div class="dest-marker"></div>',
        iconSize: [26, 26],
        iconAnchor: [13, 13] // center
    });

    // ================= MAP (MUST COME FIRST) =================
    const navMap = L.map('navMap', { zoomControl: false })
        .setView([lat, lng], 16);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png")
        .addTo(navMap);

    // ================= DESTINATION =================
    const destMarker = L.marker([lat, lng], { icon: destIcon })
        .addTo(navMap);

    const label = L.tooltip({
        permanent: true,
        direction: 'top',
        offset: [0, -18],
        className: 'dest-tooltip'
    })
    .setContent(name)
    .setLatLng([lat, lng])
    .addTo(navMap);

    // ================= ROUTING =================
    let routingControl;
let userMarker;
let isFirstUpdate = true;

navigator.geolocation.watchPosition(

    function (pos) {

        const userLat = pos.coords.latitude;
        const userLng = pos.coords.longitude;

        // ================= FIRST LOAD =================
        if (isFirstUpdate) {

            userMarker = L.marker([userLat, userLng], { icon: userIcon })
                .addTo(navMap);

            navMap.flyTo([userLat, userLng], 16, {
                animate: true,
                duration: 1.5
            });

            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(userLat, userLng),
                    L.latLng(lat, lng)
                ],
                addWaypoints: false,
                draggableWaypoints: false,

                lineOptions: {
                    styles: [{
                        color: '#7c3aed',
                        weight: 6,
                        opacity: 0.9
                    }]
                },

                createMarker: () => null

            }).addTo(navMap);

            // 🔥 MOVE PANEL ONLY ONCE
            setTimeout(() => {
                const panel = document.querySelector('.leaflet-routing-container');
                const container = document.getElementById('routeContainer');

                if (panel && container && !container.hasChildNodes()) {
                    container.appendChild(panel);
                }
            }, 300);

            isFirstUpdate = false;

        } else {

            // ================= UPDATE ONLY =================
            userMarker.setLatLng([userLat, userLng]);

            // 🔥 ONLY UPDATE WAYPOINTS (DO NOT CREATE NEW CONTROL)
            routingControl.setWaypoints([
                L.latLng(userLat, userLng),
                L.latLng(lat, lng)
            ]);

            navMap.panTo([userLat, userLng], {
                animate: true
            });
        }

    },

    function () {
        alert("Please enable GPS");
    },

    {
        enableHighAccuracy: true
    }

);

    // ================= MODAL =================
    const modal = document.getElementById('routeModal');
    const openBtn = document.getElementById('openRoute');
    const closeBtn = document.getElementById('closeRoute');

    if (modal && openBtn && closeBtn) {

        openBtn.onclick = () => modal.classList.add('active');

        closeBtn.onclick = () => modal.classList.remove('active');

        modal.onclick = (e) => {
            if (e.target.id === 'routeModal') {
                modal.classList.remove('active');
            }
        };
    }


    // ================= BOTTOM SHEET (POINTER EVENTS) =================
const sheet = document.getElementById('sheet');
const handle = document.getElementById('sheetHandle');

if (sheet && handle) {

    let startY = 0;
    let currentY = 0;
    let isDragging = false;

    const PEEK_HEIGHT = 70;

const MAX = window.innerHeight - PEEK_HEIGHT;
const MIN = 0;

let currentTranslate = MAX;

sheet.style.transform = `translateY(${currentTranslate}px)`; // 🔥 initial state

    // 🔥 pointer down (mouse + touch)
    handle.addEventListener('pointerdown', (e) => {
        startY = e.clientY;
        isDragging = true;

        sheet.style.transition = 'none';

        // 🔒 capture pointer (important for desktop drag)
        handle.setPointerCapture(e.pointerId);
    });

    // 🔥 pointer move
    document.addEventListener('pointermove', (e) => {
        if (!isDragging) return;

        currentY = e.clientY;
        const diff = currentY - startY;

        let next = currentTranslate + diff;

        // clamp
        next = Math.max(MIN, Math.min(next, MAX));

        sheet.style.transform = `translateY(${next}px)`;
    });

    // 🔥 pointer up
    document.addEventListener('pointerup', () => {
        if (!isDragging) return;

        isDragging = false;

        sheet.style.transition = 'transform 0.35s cubic-bezier(0.22, 1, 0.36, 1)';

        if (currentY - startY > 50) {
            currentTranslate = MAX; // collapse
        } else {
            currentTranslate = MIN; // expand
        }

        sheet.style.transform = `translateY(${currentTranslate}px)`;
    });

    // 👇 smooth multi-bounce (physics-like)
setTimeout(() => {

    let start = null;

    const amplitude = 70;   // 👈 how strong bounce is
    const decay = 0.65;      // 👈 how fast it fades (lower = more bounces)
    const duration = 1300;   // total time

    function animate(timestamp) {

        if (!start) start = timestamp;
        const elapsed = timestamp - start;

        // progress (0 → 1)
        const t = elapsed / duration;

        if (t >= 1) {
            sheet.style.transform = `translateY(${currentTranslate}px)`;
            return;
        }

        // 🔥 damped oscillation (REAL bounce math)
        const bounce =
            amplitude *
            Math.exp(-decay * t * 5) * // decay
            Math.cos(6 * t * Math.PI); // oscillation

        sheet.style.transform =
            `translateY(${currentTranslate - bounce}px)`;

        requestAnimationFrame(animate);
    }

    requestAnimationFrame(animate);

}, 900);

}



// 🔥 Prevent map from being blocked
sheet.addEventListener('touchstart', (e) => {
    if (!e.target.closest('.sheet-handle')) {
        e.stopPropagation(); // allow map drag outside handle
    }
});


// ================= COPY CONTACT =================
document.querySelectorAll('.copy-btn').forEach(btn => {

    btn.addEventListener('click', async () => {

        const text = btn.dataset.copy;

        try {
            await navigator.clipboard.writeText(text);

            // feedback (icon swap)
            btn.innerHTML = '<i class="ph-light ph-check"></i>';

            setTimeout(() => {
                btn.innerHTML = '<i class="ph-light ph-copy"></i>';
            }, 1200);

        } catch (err) {
            console.error(err);
            alert("Copy failed");
        }

    });

});
});