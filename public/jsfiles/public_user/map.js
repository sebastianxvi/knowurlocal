document.addEventListener("DOMContentLoaded", () => {

    
const navigateBaseUrl = window.APP_CONFIG.navigateBaseUrl;
        
        const sanJose = [12.353984, 121.067504];

var map = L.map('map', { zoomControl: false })
    .setView(sanJose, 14); // 👈 start zoomed OUT

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // ================= INTRO ZOOM ANIMATION =================
map.whenReady(() => {

    setTimeout(() => {
        map.flyTo(sanJose, 15, {
            duration: 1.8,
            easeLinearity: 0.15
        });
    }, 400); // slight delay for smoother feel

});

        // Object to store markers keyed by agency name
        var markers = {};

        fetch('/api/agencies')
        .then(res => res.json())
        .then(data => {
            data.forEach(agency => {
                var icon = L.icon({
                    iconUrl: window.APP_CONFIG.markerIcon,
                    iconSize: [40, 60],
                    iconAnchor: [30, 60],
                    popupAnchor: [-9, -60]
                });

                var safeDescription = truncateText(
    escapeHtml(agency.agency_description),
    40 // max characters (adjust as needed)
);



const image = agency.agency_image 
    ? `/storage/${agency.agency_image}` 
    : `/images/default-agency.png`;


                var popupContent = `
                <a href="${navigateBaseUrl}/${agency.id}" class="agency-popup-link">
                    <div class="agency-popup">

                        <div class="popup-image">
                            <img src="${image}" alt="${agency.agency_name}">
                        </div>

                        <div class="popup-body">
                            <h4>${agency.agency_name}</h4>

                            <p class="description">
                    ${safeDescription}
                </p>


                            <div class="popup-info">
                                <span class="email">
                                    <i class="ph-light ph-envelope"></i>
                                    ${agency.agency_email}
                                </span>

                                <span class="phone">
                                    <i class="ph-light ph-phone"></i>
                                    ${agency.agency_hotline}
                                </span>
                            </div>

                        </div>

                    </div>
                </a>
                `;

                // Create marker and add to map
                var marker = L.marker([agency.lat, agency.lng], {icon: icon})
                            .addTo(map)
                            .bindPopup(popupContent);

                // Store marker in object for searching
                markers[agency.agency_name.toLowerCase()] = marker;
            });
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');

        function handleSearch(isSubmit = false) {
            const query = searchInput.value.toLowerCase().trim();

            if (!query) {
                resultsContainer.innerHTML = '';
                return;
            }

            const entries = Object.entries(markers);

            const matches = entries.filter(([name, marker]) => {
                const words = query.split(' ');
                return words.every(word => name.includes(word));
            });

            // No results
            if (matches.length === 0) {
                resultsContainer.innerHTML = '<div class="search-item">No results</div>';
                return;
            }

            // ONLY focus if user explicitly submitted
            if (isSubmit) {
                const [, marker] = matches[0];
                focusMarker(marker);
                resultsContainer.innerHTML = '';
                return;
            }

            // Otherwise just show options
            showResults(matches);
        }

        function focusMarker(marker) {

    const latlng = marker.getLatLng();

    // STEP 1: go to marker normally
    map.flyTo(latlng, 17, {
        duration: 1.2
    });

    // STEP 2: after animation → shift camera
    map.once('moveend', () => {

        const offsetY = 160; // tweak this

        const point = map.project(latlng, map.getZoom());
        const shiftedPoint = point.subtract([0, offsetY]);
        const shiftedLatLng = map.unproject(shiftedPoint, map.getZoom());

        map.panTo(shiftedLatLng, {
            animate: true,
            duration: 0.4
        });

        // open popup after positioning
        setTimeout(() => {
            marker.openPopup();
        }, 200);

    });
}

        const resultsContainer = document.getElementById('searchResults');

        function showResults(matches) {
            resultsContainer.innerHTML = '';

            matches.forEach(([name, marker]) => {
                const item = document.createElement('div');
item.classList.add('search-item');

// 🔥 structured UI
item.innerHTML = `
    <div class="search-content">

        <div class="search-top">
            <i class="ph-light ph-buildings"></i>

            <span class="search-name">${name}</span>
        </div>

    </div>
`;  

                item.addEventListener('click', () => {
                    focusMarker(marker);
                    resultsContainer.innerHTML = '';
                });

                resultsContainer.appendChild(item);
            });
        }

        // submit search when search button is clicked
        searchBtn.addEventListener('click', () => handleSearch(true));

        // when the user typed something, options would show
        searchInput.addEventListener('input', () => handleSearch(false));

        // auto search if user clicked enter
        searchInput.addEventListener('keyup', (e) => {
            if(e.key === "Enter"){
                searchBtn.click();
            }
        });







        // Utility function to safely trim text
function truncateText(text, maxLength = 100) {

    // 1. Ensure it's a string (prevents errors)
    if (!text) return '';

    // 2. Convert to string explicitly (defensive programming)
    text = text.toString();

    // 3. Trim whitespace
    text = text.trim();

    // 4. If text is longer than allowed length
    if (text.length > maxLength) {

        // 5. Cut the text and add ellipsis (...)
        return text.substring(0, maxLength) + '...';
    }

    // 6. Return original if short enough
    return text;
}


function escapeHtml(text) {
    const div = document.createElement('div');

    // This automatically escapes dangerous HTML
    div.textContent = text;

    return div.innerHTML;
}

});