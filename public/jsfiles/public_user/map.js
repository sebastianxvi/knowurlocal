document.addEventListener("DOMContentLoaded", () => {

    
const navigateBaseUrl = window.APP_CONFIG.navigateBaseUrl;
const resultsContainer = document.getElementById('searchResults');
        
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

        // ================= CATEGORY FILTERS =================

// Stores the currently selected category.
// "all" means every agency should be visible.
let activeCategoryId = 'all';

// Reference to the category capsule container in the navbar.
const categoryFilters =
    document.getElementById('categoryFilters');

    // ================= CATEGORY MOUSE-WHEEL SCROLL =================

if (categoryFilters) {

    categoryFilters.addEventListener(
        'wheel',
        (event) => {

            // Prevent the normal page/map wheel behavior.
            event.preventDefault();

            // Prevent the event from reaching the navbar.
            event.stopPropagation();

            // Convert vertical mouse-wheel movement
            // into horizontal category scrolling.
            categoryFilters.scrollLeft += event.deltaY;
        },
        {
            passive: false
        }
    );
}


// ================= PREVENT NAVBAR PAGE SCROLL =================

const siteHeader = document.querySelector('.site-header');

if (siteHeader) {

    siteHeader.addEventListener(
        'wheel',
        (event) => {

            // The category row handles its own wheel behavior.
            if (event.target.closest('#categoryFilters')) {
                return;
            }

            // Prevent the page from scrolling while
            // the cursor is over the floating navbar.
            event.preventDefault();
        },
        {
            passive: false
        }
    );
}





        // ================= AGENCY MAP LABELS =================

function updateAgencyLabels() {

    const zoom = map.getZoom();

    Object.values(markers).forEach(marker => {

        const agency = marker.agencyData;

        // Safety check in case a marker has no agency data.
        if (!agency) {
            return;
        }

        // Full agency name for close zoom levels.
        const fullName = escapeHtml(
            agency.agency_name || ''
        );

        // Abbreviation for wider map views.
        // If no abbreviation exists, fall back to the agency name.
        const abbreviation = escapeHtml(
            agency.agency_abbreviation ||
            agency.agency_name ||
            ''
        );

        // Zoom 16+ shows the full name.
        // Zoom 15 and below shows the shorter abbreviation.
        const label =
            zoom >= 16
                ? fullName
                : abbreviation;

        marker.setTooltipContent(label);

    });

    repositionAgencyLabels();

    
}

// ================= BUILD CATEGORY FILTERS =================

function buildCategoryFilters(data) {

    if (!categoryFilters) {
        return;
    }

    const categories = new Map();

    data.forEach(agency => {

        const category = agency.category;

        if (!category || !category.id) {
            return;
        }

        if (!categories.has(category.id)) {
            categories.set(category.id, category);
        }

    });

    categoryFilters.innerHTML = '';

    // ================= ALL BUTTON =================

const allButton = document.createElement('button');

allButton.type = 'button';
allButton.className = 'category-filter active';
allButton.dataset.categoryId = 'all';
allButton.textContent = 'All';

// When "All" is clicked, show every agency.
allButton.addEventListener('click', () => {

    applyCategoryFilter('all');

});

categoryFilters.appendChild(allButton);

    
// ================= CATEGORY BUTTONS =================

categories.forEach(category => {

    const button = document.createElement('button');

    button.type = 'button';
    button.className = 'category-filter';

    button.dataset.categoryId = category.id;

    // Create the colored circle.
    const colorDot = document.createElement('span');

    colorDot.className = 'category-color-dot';

    // Reuse the category's existing marker color.
    const categoryColor =
        /^#[0-9A-Fa-f]{6}$/.test(category.display_color)
            ? category.display_color
            : '#3B82F6';

    colorDot.style.backgroundColor = categoryColor;

    // Create the category text separately.
    const categoryName = document.createElement('span');

    categoryName.textContent =
        category.category_name;

    // Put the circle first, then the category name.
    button.appendChild(colorDot);
    button.appendChild(categoryName);

    // Apply the category when clicked.
    button.addEventListener('click', () => {

        applyCategoryFilter(category.id);

    });

    categoryFilters.appendChild(button);

});

}



// ================= APPLY CATEGORY FILTER =================

function applyCategoryFilter(categoryId) {

    // Store the currently selected category.
    activeCategoryId = String(categoryId);

    // Close any currently visible search results.
    if (resultsContainer) {
        resultsContainer.innerHTML = '';
    }

    // Check every marker currently stored in our marker collection.
    Object.values(markers).forEach(marker => {

        // Retrieve the agency data attached to this marker.
        const agency = marker.agencyData;

        // Get the agency's category ID safely.
        const agencyCategoryId =
            agency?.category?.id;

        // "all" means every agency should be visible.
        const shouldShow =
            activeCategoryId === 'all' ||
            String(agencyCategoryId) === activeCategoryId;

        // Show or hide the actual Leaflet marker.
        marker.setOpacity(
            shouldShow ? 1 : 0
        );

        // Get the marker's permanent tooltip/label.
        const tooltip = marker.getTooltip();

        if (shouldShow) {

            // Restore the agency label.
            if (tooltip) {
                marker.openTooltip();
            }

        } else {

            // Remove the label from the map as well.
            if (tooltip) {
                marker.closeTooltip();
            }

            // Also close an open popup when its agency is filtered out.
            marker.closePopup();
        }

    });

    // Update which capsule looks active.
    updateActiveCategoryButton();

    // Recalculate label positions after filtering.
    repositionAgencyLabels();
}


// ================= ACTIVE CATEGORY BUTTON =================

function updateActiveCategoryButton() {

    if (!categoryFilters) {
        return;
    }

    // Find every category capsule.
    const buttons =
        categoryFilters.querySelectorAll(
            '.category-filter'
        );

    buttons.forEach(button => {

        // Read the category ID stored in data-category-id.
        const buttonCategoryId =
            button.dataset.categoryId;

        // Add "active" only to the selected category.
        button.classList.toggle(
            'active',
            buttonCategoryId === activeCategoryId
        );

    });
}


// ================= LABEL COLLISION AVOIDANCE =================

function repositionAgencyLabels() {

    const labels = Object.values(markers)
    .filter(marker => {

        const agency =
            marker.agencyData;

        const categoryId =
            agency?.category?.id;

        return (
            activeCategoryId === 'all' ||
            String(categoryId) === activeCategoryId
        );

    })
    .map(marker =>
        marker.getTooltip()?.getElement()
    )
    .filter(label => label);

    if (!labels.length) {
        return;
    }

    // Candidate positions around the marker.
    // The first position is the normal position.
    const positions = [
        { x: 0,   y: 0   },   // top
        { x: 0,   y: 22  },   // bottom
        { x: 35,  y: 0   },   // right
        { x: -35, y: 0   },   // left
        { x: 0,   y: -22 },   // further top
        { x: 0,   y: 42  },   // further bottom
        { x: 55,  y: 0   },   // further right
        { x: -55, y: 0   }    // further left
    ];

    const placed = [];

    labels.forEach(label => {

        // Reset previous positioning.
        label.style.marginLeft = '0px';
        label.style.marginTop = '0px';

        let selectedPosition = positions[0];

        for (const position of positions) {

            label.style.marginLeft = `${position.x}px`;
            label.style.marginTop = `${position.y}px`;

            const rect = label.getBoundingClientRect();

            const collision = placed.some(existing => {

                return !(
                    rect.right < existing.left ||
                    rect.left > existing.right ||
                    rect.bottom < existing.top ||
                    rect.top > existing.bottom
                );

            });

            if (!collision) {
                selectedPosition = position;
                break;
            }
        }

        // Apply the position that worked.
        label.style.marginLeft =
            `${selectedPosition.x}px`;

        label.style.marginTop =
            `${selectedPosition.y}px`;

        placed.push(
            label.getBoundingClientRect()
        );

    });
}

        fetch('/api/agencies')
        .then(res => res.json())
        .then(data => {

            // agency data we are already receiving.
        buildCategoryFilters(data);

            data.forEach(agency => {
                const rawCategoryColor = agency.category?.display_color;

                const categoryColor =
                    /^#[0-9A-Fa-f]{6}$/.test(rawCategoryColor)
                        ? rawCategoryColor
                        : '#3B82F6';

                const icon = L.divIcon({

                    className: 'category-map-marker',

                    html: `
                        <div
                            class="category-marker-circle"
                            style="--marker-color: ${categoryColor}">
                        </div>
                    `,

                    iconSize: [24, 24],

                    iconAnchor: [12, 12],

                    popupAnchor: [0, -16]

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

                // ================= CREATE MARKER =================

                const marker = L.marker(
                    [agency.lat, agency.lng],
                    {
                        icon: icon
                    }
                );

                // Store the agency object on the marker.
                // This lets our zoom-label function access the agency later.
                marker.agencyData = agency;

                // Add marker to the map and attach the existing popup.
                marker
                    .addTo(map)
                    .bindPopup(popupContent);

                // Attach the permanent label.
                // Its text will be updated whenever the map zoom changes.
                marker.bindTooltip(
                    '',
                    {
                        permanent: true,
                        direction: 'top',
                        offset: [0, -14],
                        className: 'agency-map-label'
                    }
                );

                // Store marker for the existing search functionality.
                markers[
                    agency.agency_name.toLowerCase()
                ] = marker;
                
            });

            updateAgencyLabels();
        });

        // IMPORTANT: also outside the fetch/forEach
map.on('zoomend', updateAgencyLabels);
map.on('moveend', repositionAgencyLabels);

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

    // Get the agency's category ID safely.
    const agencyCategoryId =
        marker.agencyData?.category?.id;

    // Check whether this agency belongs
    // to the currently selected category.
    const matchesCategory =
        activeCategoryId === 'all' ||
        String(agencyCategoryId) === String(activeCategoryId);

    // If the category doesn't match,
    // don't allow this agency into search results.
    if (!matchesCategory) {
        return false;
    }

    // Search only the agencies that belong
    // to the selected category.
    const words = query.split(/\s+/);

const agencyName =
    marker.agencyData?.agency_name?.toLowerCase() || '';

const agencyAbbreviation =
    marker.agencyData?.agency_abbreviation?.toLowerCase() || '';

const matchesFullName =
    words.every(word =>
        agencyName.includes(word)
    );

const matchesAbbreviation =
    agencyAbbreviation === query ||
    agencyAbbreviation.includes(query);

return matchesFullName || matchesAbbreviation;

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

        

        // ================= CLOSE SEARCH ON OUTSIDE CLICK =================

const searchForm = document.querySelector('.search-form');

document.addEventListener('click', (event) => {

    // If the search component does not exist,
    // there is nothing to close.
    if (!searchForm || !resultsContainer) {
        return;
    }

    // If the click happened inside the search component,
    // keep the search results open.
    if (searchForm.contains(event.target)) {
        return;
    }

    // Any click outside the search component closes the results.
    resultsContainer.innerHTML = '';
});

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