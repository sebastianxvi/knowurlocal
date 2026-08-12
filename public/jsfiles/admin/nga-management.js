document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("modal-back");
    const form = document.getElementById("agencyForm");

    const uploadBox = document.getElementById('agency-upload-box');
    const fileInput = document.getElementById('agency_image');
    const previewImg = document.getElementById('agency-preview');
    const placeholder = document.getElementById('agency-upload-placeholder');
    let currentMode = "add";


    window.map = null;
    window.marker = null;

    // ================= FLASH SUCCESS HANDLER =================
    if (window.__FLASH_SUCCESS__) {

        showAlertModal({
            title: "Success",
            text: window.__FLASH_SUCCESS__,
            icon: "✓",
            variant: "success",
            confirmText: "OK",
            showCancel: false,

            onConfirm: () => {
                closeAlertModal();
            }
        });

        // 🔥 Auto close after 1.5s (SaaS feel)
        setTimeout(() => {
            closeAlertModal();
        }, 1500);

        // 🔒 Clear to prevent repeat
        window.__FLASH_SUCCESS__ = null;
    }



    /* ================= IMAGE UPLOAD ================= */
    if (uploadBox && fileInput) {

        // OPEN FILE PICKER
        uploadBox.addEventListener('click', () => {
            fileInput.click();
        });

        // HANDLE FILE CHANGE
        fileInput.addEventListener('change', function () {

            const file = this.files[0];
            if (!file) return;

            /* 🔐 SECURITY VALIDATION */
            if (!file.type.startsWith('image/')) {
                alert('Only image files allowed.');
                fileInput.value = '';
                return;
            }

            /* 🔐 SIZE LIMIT (2MB) */
            if (file.size > 2 * 1024 * 1024) {
                alert('Max file size is 2MB.');
                fileInput.value = '';
                return;
            }

            /* SAFE PREVIEW */
            const reader = new FileReader();

            reader.onload = function (e) {
                previewImg.src = e.target.result;

                previewImg.style.display = 'block';
                placeholder.style.display = 'none';
            };

            reader.readAsDataURL(file);
        });
    }



    function fillForm(data) {
        document.getElementById('agency_name').value = data.name || '';
        document.getElementById('agency_abbreviation').value = data.abbreviation || '';
        const typeSelect = document.getElementById('agency_type_id');

        if (typeSelect) {
            [...typeSelect.options].forEach(option => {
                option.selected = option.value == data.type_id;
            });
        }

        const categorySelect = document.getElementById('category_id');

        if (categorySelect) {
            [...categorySelect.options].forEach(option => {
                option.selected = option.value == data.category_id;
            });
        }
        document.getElementById('agency_description').value = data.description || '';
        document.getElementById('services_offered').value = data.services_offered || '';
        document.getElementById('agency_location').value = data.location || '';

        document.getElementById('agency_email').value = data.email || '';
        document.getElementById('agency_hotline').value = data.hotline || '';
        document.getElementById('agency_landline').value = data.landline || '';

        document.getElementById('agency_website').value = data.website || '';
        document.getElementById('agency_fb').value = data.fb || '';
        document.getElementById('office_hours').value = data.office || '';

        document.getElementById('lat').value = data.lat || '';
        document.getElementById('lng').value = data.lng || '';
    }

    function enableInputs(enable = true) {
    const inputs = form.querySelectorAll("input, textarea, select");

    inputs.forEach(input => {
        if (input.name !== '_method' && input.type !== 'hidden') {

            if (enable) {
                input.removeAttribute('readonly');
                input.disabled = false;

                if (input.tagName === "SELECT") {
                    input.style.pointerEvents = "auto"; // ✅ enable interaction
                }

            } else {
                input.setAttribute('readonly', true);

                if (input.tagName === "SELECT") {
                    input.style.pointerEvents = "none"; // 🔒 LOCK IT
                }
            }

        }
    });
}

function setMapToCoordinates(lat, lng) {

    const latitude = parseFloat(lat);
    const longitude = parseFloat(lng);

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return;
    }

    if (latitude < -90 || latitude > 90) {
        return;
    }

    if (longitude < -180 || longitude > 180) {
        return;
    }

    if (!window.map || !window.marker) {
        return;
    }

    window.marker.setLatLng([latitude, longitude]);

    window.map.setView(
        [latitude, longitude],
        17
    );
}


    /* ================= OPEN MODAL ================= */
    function openModal(mode = 'add', data = null) {
        currentMode = mode;

        modal.style.display = "flex";
        setTimeout(() => modal.classList.add("active"), 10);

         const title = document.getElementById('modal-title');

        if (mode === 'add') title.textContent = "Add Agency";
        if (mode === 'edit') title.textContent = "Edit Agency";
        if (mode === 'view') title.textContent = "View Agency";


        const saveBtn = document.querySelector('.btn-save');

        if (mode === 'view') {
            saveBtn.disabled = true;
        } else {
            saveBtn.disabled = false;
        }

        if (!window.map) {
            initMap();
        } else {
            window.map.invalidateSize();
        }

        // ================= ADD =================
        if (mode === 'add') {

            // 🔥 FULL RESET (VALUES + VALIDATION UI)
            if (window.resetFormValidation) {
                resetFormValidation(form);
            } else {
                form.reset(); // fallback safety
            }

            form.action = "/admin/agencies";

            const methodInput = document.getElementById('form-method');
            if (methodInput) methodInput.value = 'POST';

            previewImg.src = "https://via.placeholder.com/150";
            previewImg.style.display = 'none';
            placeholder.style.display = 'flex';

            document.getElementById('lat').value = '';
document.getElementById('lng').value = '';

if (window.map && window.marker) {
    window.marker.setLatLng([12.354, 121.065]);
    window.map.setView([12.354, 121.065], 15);
}

enableInputs(true);
        }

        // ================= EDIT =================
        if (mode === 'edit' && data) {
            form.action = `/admin/agencies/${data.id}`;

            const methodInput = document.getElementById('form-method');
            if (methodInput) {
                methodInput.value = 'PUT';
            }

            fillForm(data);

            setMapToCoordinates(data.lat, data.lng);

            if (data && data.image) {
                previewImg.src = `/storage/${data.image}`;
                previewImg.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                previewImg.src = "https://via.placeholder.com/150";
                previewImg.style.display = 'block';
                placeholder.style.display = 'none';
            }

            enableInputs(true);
        }

        // ================= VIEW =================
        if (mode === 'view' && data) {
            form.action = "#"; // or just leave empty
            fillForm(data);

            setMapToCoordinates(data.lat, data.lng);

            if (data && data.image) {
                previewImg.src = `/storage/${data.image}`;
                previewImg.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                previewImg.src = "https://via.placeholder.com/150";
                previewImg.style.display = 'block';
                placeholder.style.display = 'none';
            }

            enableInputs(false);

            
        }
    }
    window.openModal = openModal;


    document.addEventListener('click', function (e) {

        const row = e.target.closest('.agency-row');
        if (!row) return;

        // 🔒 prevent conflicts
        if (
            e.target.closest('button') ||
            e.target.closest('form')
        ) return;

        openModal('view', {
            id: row.dataset.id,
            name: row.dataset.name,
            abbreviation: row.dataset.abbreviation, // ✅ FIX
            type_id: row.dataset.type_id,
            category_id: row.dataset.category_id,
            description: row.dataset.description,
            services_offered: row.dataset.services_offered,
            location: row.dataset.location,
            email: row.dataset.email,
            hotline: row.dataset.hotline,
            landline: row.dataset.landline,
            website: row.dataset.website,
            fb: row.dataset.fb,
            office: row.dataset.office,
            lat: row.dataset.lat,
            lng: row.dataset.lng,
            image: row.dataset.image
        });
    });

    /* ================= CLOSE MODAL ================= */
    function closeModal() {

        modal.classList.remove("active");

        setTimeout(() => {
            modal.style.display = "none";

            // 🔥 RESET FORM AFTER CLOSE ANIMATION
            if (window.resetFormValidation) {
                resetFormValidation(form);
            } else {
                form.reset();
            }

        }, 250);
    }
    window.closeModal = closeModal;

    /* ================= MAP ================= */
    function initMap(lat = 12.354, lng = 121.065) {
        window.map = L.map('map', {
            zoomControl: false
        }).setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(window.map);

        window.marker = L.marker([lat, lng], { draggable: true }).addTo(window.map);

        window.marker.on('dragend', function () {
            const pos = window.marker.getLatLng();
            updateCoords(pos.lat, pos.lng);
        });

        window.map.on('click', function (e) {
            window.marker.setLatLng(e.latlng);
            updateCoords(e.latlng.lat, e.latlng.lng);
        });
    }

    async function searchLocation() {

        const query = document.getElementById('searchLocation').value.trim();
        const btn = document.getElementById('searchBtn');

        if (!query) {
            alert("Please enter a location.");
            return;
        }

        if (!window.map || !window.marker) {
            initMap();
        }

        // 🔥 START LOADING
        btn.disabled = true;
        btn.textContent = "Searching...";

        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`);
            const data = await res.json();

            if (!data.length) {
                alert("Location not found.");
                return;
            }

            const place = data[0];

            const lat = parseFloat(place.lat);
            const lng = parseFloat(place.lon);

            window.map.setView([lat, lng], 15);
            window.marker.setLatLng([lat, lng]);

            updateCoords(lat, lng);

        } catch (err) {
            console.error("Search error:", err);
            alert("Something went wrong while searching.");
        } finally {
            // 🔥 RESET BUTTON
            btn.disabled = false;
            btn.textContent = "Search";
        }
    }
    window.searchLocation = searchLocation;

    function updateCoords(lat, lng) {
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;
        getAddress(lat, lng);
    }


    // ================= MANUAL COORDINATE SYNC =================

function updateMapFromCoordinates() {

    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');

    if (!latInput || !lngInput) {
        return;
    }

    const lat = parseFloat(latInput.value.trim());
    const lng = parseFloat(lngInput.value.trim());

    // Wait until both coordinates are valid numbers.
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return;
    }

    // Validate latitude range.
    if (lat < -90 || lat > 90) {
        return;
    }

    // Validate longitude range.
    if (lng < -180 || lng > 180) {
        return;
    }

    // Make sure Leaflet has already been initialized.
    if (!window.map || !window.marker) {
        return;
    }

    // Move the existing marker.
    window.marker.setLatLng([lat, lng]);

    // Center the map on the new coordinates.
    window.map.setView([lat, lng], 17);

    // Reverse-geocode the new location.
    getAddress(lat, lng);
}


// Update after the administrator finishes editing
// either coordinate field.
const latInput = document.getElementById('lat');
const lngInput = document.getElementById('lng');

let coordinateUpdateTimer = null;

function scheduleCoordinateUpdate() {

    // Cancel the previous timer.
    // This prevents the map from updating while the admin
    // is still typing.
    clearTimeout(coordinateUpdateTimer);

    // Wait 5 seconds after the last edit.
    coordinateUpdateTimer = setTimeout(() => {

        updateMapFromCoordinates();

    }, 5000);
}

if (latInput) {
    latInput.addEventListener(
        'input',
        scheduleCoordinateUpdate
    );
}

if (lngInput) {
    lngInput.addEventListener(
        'input',
        scheduleCoordinateUpdate
    );
}


    async function getAddress(lat, lng) {
        try {

            
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
            const data = await res.json();

            if (data?.display_name) {
                document.getElementById('agency_location').value = data.display_name;
            }
        } catch (err) {
            console.error("Geocoding error:", err);
        }
    }

    window.getAddress = getAddress;

    /* ================= EDIT BUTTON ================= */
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.btn-primary');
        if(!btn) return;

        openModal('edit', {
            id: btn.dataset.id,
            name: btn.dataset.name,
            abbreviation: btn.dataset.abbreviation,
            type_id: btn.dataset.type_id,
            category_id: btn.dataset.category_id,
            description: btn.dataset.description,
            services_offered: btn.dataset.services_offered,
            location: btn.dataset.location,
            email: btn.dataset.email,
            hotline: btn.dataset.hotline,
            landline: btn.dataset.landline,
            website: btn.dataset.website,
            fb: btn.dataset.fb,
            office: btn.dataset.office,
            lat: btn.dataset.lat,
            lng: btn.dataset.lng
        });

        previewImg.src = btn.dataset.image
            ? `/storage/${btn.dataset.image}`
            : "https://via.placeholder.com/150";
    });

    /* ================= CLOSE EVENTS ================= */
    modal.addEventListener("click", e => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener("keydown", e => {
        if (e.key === "Escape") closeModal();
    });


    const searchBtn = document.getElementById('searchBtn');

        if (searchBtn) {
            searchBtn.addEventListener('click', searchLocation);
        }


        

        // ================= DELETE WITH MODAL =================
        document.addEventListener('click', function(e){

            const btn = e.target.closest('.delete-btn');
            if (!btn) return;

            const form = btn.closest('form');
            if (!form) return;

            e.preventDefault();

            showAlertModal({
                title: "Delete this agency?",
                text: "This action cannot be undone.",
                icon: "!",
                variant: "danger",
                confirmText: "Delete",
                showCancel: true,

                onConfirm: () => {
                    form.submit();
                }
            });

        });

        /**
 * ================= CONFIRM SAVE =================
 */
form.addEventListener("submit", function(e){

    // Creating a new agency? Submit immediately.
    if (currentMode === "add") {
        return;
    }

    // Editing an existing agency? Ask for confirmation.
    e.preventDefault();

    showAlertModal({
        title: "Save changes?",
        text: "Make sure all information is correct.",
        icon: "✓",
        variant: "success",
        confirmText: "Save",
        showCancel: true,

        onConfirm: () => {
            form.submit();
        }
    });
});
});
