document.addEventListener("DOMContentLoaded", function () {

    // =====================================================
    // DOM REFERENCES
    // =====================================================

    const modal =
        document.getElementById("modal-back");

    const form =
        document.getElementById("agencyForm");

    const uploadBox =
        document.getElementById("agency-upload-box");

    const fileInput =
        document.getElementById("agency_image");

    const previewImg =
        document.getElementById("agency-preview");

    const placeholder =
        document.getElementById("agency-upload-placeholder");

    const contactsContainer =
        document.getElementById("agency-contacts");

    const addContactBtn =
        document.getElementById("add-contact-btn");


    // =====================================================
    // APPLICATION STATE
    // =====================================================

    let currentMode = "add";

    let contactIndex = 0;


    /*
     * Contact types are provided by Laravel through Blade.
     *
     * We never trust this data for security-sensitive
     * validation. Laravel validates it again server-side.
     */
    const contactTypes =
        Array.isArray(window.agencyContactTypes)
            ? window.agencyContactTypes
            : [];


    // =====================================================
    // LEAFLET STATE
    // =====================================================

    window.map = null;
    window.marker = null;


    // =====================================================
    // CONTACT TYPE HELPER
    // =====================================================

    /*
     * Find a predefined contact type by its database ID.
     */
    function getContactType(contactTypeId) {

        return contactTypes.find(
            type =>
                String(type.id) ===
                String(contactTypeId)
        ) || null;

    }


    // =====================================================
    // HTML ESCAPE HELPER
    // =====================================================

    /*
     * Database values may eventually be inserted into
     * innerHTML, so escape them first.
     *
     * This protects the frontend from HTML injection.
     */
    function escapeContactHtml(value) {

        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    }


    // =====================================================
    // PRIMARY CONTACT LOGIC
    // =====================================================

    /*
     * Returns true when no other contact currently uses
     * the selected contact type.
     *
     * This means the first Email, first Hotline, first
     * Facebook, etc. can automatically become primary.
     */
    function isFirstContactOfType(
        contactTypeId,
        ignoredRow = null
    ) {

        if (!contactTypeId) {
            return false;
        }


        if (!contactsContainer) {
            return true;
        }


        const rows =
            contactsContainer.querySelectorAll(
                ".agency-contact-row"
            );


        for (const row of rows) {

            if (row === ignoredRow) {
                continue;
            }


            const typeSelect =
                row.querySelector(
                    ".contact-type-select"
                );


            if (
                typeSelect &&
                String(typeSelect.value) ===
                String(contactTypeId)
            ) {

                return false;

            }

        }


        return true;

    }


    /*
     * Ensure every contact type has at most one
     * primary contact.
     *
     * If a contact type has contacts but none is primary,
     * the first contact automatically becomes primary.
     */
    function syncPrimaryContacts() {

        if (!contactsContainer) {
            return;
        }


        const rows =
            Array.from(
                contactsContainer.querySelectorAll(
                    ".agency-contact-row"
                )
            );


        const grouped =
            new Map();


        rows.forEach(row => {

            const typeSelect =
                row.querySelector(
                    ".contact-type-select"
                );

            const checkbox =
                row.querySelector(
                    ".contact-primary-checkbox"
                );


            if (!typeSelect || !checkbox) {
                return;
            }


            const typeId =
                String(
                    typeSelect.value || ""
                ).trim();


            if (!typeId) {

                checkbox.checked = false;

                return;

            }


            if (!grouped.has(typeId)) {

                grouped.set(
                    typeId,
                    []
                );

            }


            grouped
                .get(typeId)
                .push({
                    row,
                    checkbox
                });

        });


        grouped.forEach(
            contacts => {

                /*
                 * Find every primary contact for this type.
                 */
                const primaryContacts =
                    contacts.filter(
                        contact =>
                            contact.checkbox.checked
                    );


                /*
                 * Only one contact of a particular type
                 * may be primary.
                 */
                if (
                    primaryContacts.length > 1
                ) {

                    primaryContacts
                        .slice(1)
                        .forEach(
                            contact => {

                                contact
                                    .checkbox
                                    .checked = false;

                            }
                        );

                }


                /*
                 * Check whether a primary still exists.
                 */
                const hasPrimary =
                    contacts.some(
                        contact =>
                            contact.checkbox.checked
                    );


                /*
                 * If none exists, automatically assign
                 * the first contact as primary.
                 */
                if (
                    !hasPrimary &&
                    contacts.length > 0
                ) {

                    contacts[0]
                        .checkbox
                        .checked = true;

                }

            }
        );

    }


    // =====================================================
    // CREATE CONTACT ROW
    // =====================================================

    function createContactRow(
        contact = {}
    ) {

        if (!contactsContainer) {
            return null;
        }


        const index =
            contactIndex++;


        const row =
            document.createElement("div");


        row.className =
            "agency-contact-row";


        row.dataset.contactIndex =
            index;


        /*
         * Preserve existing database ID when editing.
         */
        if (contact.id) {

            row.dataset.contactId =
                String(contact.id);

        }


        const selectedType =
            getContactType(
                contact.contact_type_id
            );


        // =================================================
        // CONTACT TYPE OPTIONS
        // =================================================

        const options =
            contactTypes
                .map(type => {

                    const selected =
                        String(type.id) ===
                        String(
                            contact.contact_type_id ?? ""
                        )
                            ? "selected"
                            : "";


                    return `
                        <option
                            value="${escapeContactHtml(type.id)}"
                            ${selected}
                        >
                            ${escapeContactHtml(type.name)}
                        </option>
                    `;

                })
                .join("");


        // =================================================
        // AUTO PRIMARY
        // =================================================

        /*
         * Existing database primary status always wins.
         *
         * Otherwise, a newly-created first contact of a
         * particular type becomes primary automatically.
         */
        const autoPrimary =
            contact.is_primary ||
            (
                contact.contact_type_id &&
                isFirstContactOfType(
                    contact.contact_type_id
                )
            );


        // =================================================
        // ROW HTML
        // =================================================

        row.innerHTML = `

            <div class="agency-contact-header">

                <div class="agency-contact-type">

                    <select
                        name="contacts[${index}][contact_type_id]"
                        class="contact-type-select"
                        aria-label="Contact type"
                        required
                    >

                        <option
                            value=""
                            disabled
                            ${selectedType ? "" : "selected"}
                        >
                            Select contact type
                        </option>

                        ${options}

                    </select>

                </div>


                <button
                    type="button"
                    class="remove-contact-btn"
                    aria-label="Remove contact"
                    title="Remove contact"
                >
                    <i class="ph-light ph-x"></i>
                </button>

            </div>


            <div class="floating-group contact-label-group">

                <input
                    type="text"
                    name="contacts[${index}][label]"
                    class="contact-label-input"
                    placeholder=" "
                    value="${escapeContactHtml(
                        contact.label
                    )}"
                    maxlength="100"
                >

                <label>
                    Label
                </label>

                <span class="form-message"></span>

            </div>


            <div class="floating-group">

                <input
                    type="text"
                    name="contacts[${index}][value]"
                    class="contact-value-input"
                    placeholder=" "
                    value="${escapeContactHtml(
                        contact.value
                    )}"
                    maxlength="255"
                    required
                >

                <label>
                    Contact
                </label>

                <span class="form-message"></span>

            </div>


            <label class="contact-primary">

                <input
                    type="checkbox"
                    name="contacts[${index}][is_primary]"
                    value="1"
                    class="contact-primary-checkbox"
                    ${autoPrimary ? "checked" : ""}
                >

                <span>
                    Primary contact
                </span>

            </label>


            <input
                type="hidden"
                name="contacts[${index}][sort_order]"
                value="${escapeContactHtml(
                    contact.sort_order ?? index
                )}"
                class="contact-sort-order"
            >

        `;


        contactsContainer.appendChild(
            row
        );


        // =================================================
        // ELEMENT REFERENCES
        // =================================================

        const typeSelect =
            row.querySelector(
                ".contact-type-select"
            );


        const primaryCheckbox =
            row.querySelector(
                ".contact-primary-checkbox"
            );


        const removeBtn =
            row.querySelector(
                ".remove-contact-btn"
            );


        // =================================================
        // CONTACT TYPE CHANGE
        // =================================================

        if (typeSelect) {

            typeSelect.addEventListener(
                "change",
                function () {

                    /*
                     * If no type is selected,
                     * primary cannot be active.
                     */
                    if (!this.value) {

                        primaryCheckbox.checked =
                            false;

                        return;

                    }


                    /*
                     * First contact of this type
                     * automatically becomes primary.
                     */
                    if (
                        isFirstContactOfType(
                            this.value,
                            row
                        )
                    ) {

                        primaryCheckbox.checked =
                            true;

                    }
                    else {

                        /*
                         * Additional contacts of the
                         * same type are NOT automatically
                         * primary.
                         *
                         * However, the checkbox remains
                         * enabled and can be manually checked.
                         */
                        primaryCheckbox.checked =
                            false;

                    }

                }
            );

        }


        // =================================================
        // PRIMARY CHECKBOX
        // =================================================

        if (primaryCheckbox) {

            primaryCheckbox.addEventListener(
                "change",
                function () {

                    /*
                     * Unchecking is allowed temporarily.
                     */
                    if (!this.checked) {
                        return;
                    }


                    const selectedType =
                        typeSelect?.value;


                    /*
                     * A contact cannot become primary
                     * without a contact type.
                     */
                    if (!selectedType) {

                        this.checked =
                            false;

                        return;

                    }


                    /*
                     * When this contact becomes primary,
                     * remove primary status from every
                     * other contact of the same type.
                     */
                    contactsContainer
                        .querySelectorAll(
                            ".agency-contact-row"
                        )
                        .forEach(
                            otherRow => {

                                if (
                                    otherRow === row
                                ) {
                                    return;
                                }


                                const otherType =
                                    otherRow.querySelector(
                                        ".contact-type-select"
                                    );


                                const otherPrimary =
                                    otherRow.querySelector(
                                        ".contact-primary-checkbox"
                                    );


                                if (
                                    otherType &&
                                    otherPrimary &&
                                    String(
                                        otherType.value
                                    ) ===
                                    String(
                                        selectedType
                                    )
                                ) {

                                    otherPrimary.checked =
                                        false;

                                }

                            }
                        );

                }
            );

        }


        // =================================================
        // REMOVE CONTACT
        // =================================================

        if (removeBtn) {

            removeBtn.addEventListener(
                "click",
                function () {

                    row.remove();

                    refreshContactIndexes();

                    syncPrimaryContacts();

                }
            );

        }


        return row;

    }


    // =====================================================
    // REFRESH SORT ORDER
    // =====================================================

    function refreshContactIndexes() {

        if (!contactsContainer) {
            return;
        }


        const rows =
            contactsContainer.querySelectorAll(
                ".agency-contact-row"
            );


        rows.forEach(
            (row, index) => {

                const sortInput =
                    row.querySelector(
                        ".contact-sort-order"
                    );


                if (sortInput) {

                    sortInput.value =
                        index;

                }

            }
        );

    }


    // =====================================================
    // ENABLE / DISABLE CONTACT ROW
    // =====================================================

    function enableContactRow(
        row,
        enable = true
    ) {

        if (!row) {
            return;
        }


        row
            .querySelectorAll(
                "input, select, button"
            )
            .forEach(
                control => {

                    control.disabled =
                        !enable;

                }
            );

    }


    // =====================================================
    // ENABLE / DISABLE ALL CONTACTS
    // =====================================================

    function enableContactInputs(
        enable = true
    ) {

        if (!contactsContainer) {
            return;
        }


        contactsContainer
            .querySelectorAll(
                ".agency-contact-row"
            )
            .forEach(
                row => {

                    enableContactRow(
                        row,
                        enable
                    );

                }
            );

    }


    // =====================================================
    // CLEAR CONTACTS
    // =====================================================

    function clearContacts() {

        if (!contactsContainer) {
            return;
        }


        contactsContainer.innerHTML =
            "";


        contactIndex =
            0;

    }


    // =====================================================
    // LOAD CONTACTS
    // =====================================================

    function loadContacts(
        contacts = []
    ) {

        clearContacts();


        if (!Array.isArray(contacts)) {
            contacts = [];
        }


        contacts.forEach(
            contact => {

                createContactRow({
                    id:
                        contact.id,

                    contact_type_id:
                        contact.contact_type_id,

                    label:
                        contact.label || "",

                    value:
                        contact.value || "",

                    is_primary:
                        Boolean(
                            contact.is_primary
                        ),

                    sort_order:
                        contact.sort_order ?? 0

                });

            }
        );


        syncPrimaryContacts();

    }


    // =====================================================
    // ADD CONTACT
    // =====================================================

    function addContact() {

        createContactRow({

            contact_type_id:
                "",

            label:
                "",

            value:
                "",

            is_primary:
                false,

            sort_order:
                contactIndex

        });

    }


    // =====================================================
    // ADD CONTACT BUTTON
    // =====================================================

    if (addContactBtn) {

        addContactBtn.addEventListener(
            "click",
            function () {

                /*
                 * View mode must remain read-only.
                 */
                if (
                    currentMode === "view"
                ) {
                    return;
                }


                addContact();

            }
        );

    }


    // =====================================================
    // REQUIRED AGENCY FIELDS
    // =====================================================

    const agencyRequiredFields = [

        {
            name: "agency_name",
            label: "Agency Name"
        },

        {
            name: "agency_abbreviation",
            label: "Abbreviation"
        },

        {
            name: "agency_type_id",
            label: "Agency Type"
        },

        {
            name: "category_id",
            label: "Category"
        },

        {
            name: "agency_location",
            label: "Location"
        },

        {
            name: "office_hours",
            label: "Office Hours"
        },

        {
            name: "lat",
            label: "Latitude"
        },

        {
            name: "lng",
            label: "Longitude"
        }

    ];


    // =====================================================
    // VALIDATE AGENCY FORM
    // =====================================================

    function validateAgencyForm(
        submittedForm
    ) {

        const missingFields = [];

        let firstMissingField = null;


        // =================================================
        // AGENCY FIELDS
        // =================================================

        agencyRequiredFields.forEach(
            field => {

                const input =
                    submittedForm.querySelector(
                        `[name="${field.name}"]`
                    );


                if (!input) {
                    return;
                }


                const value =
                    String(
                        input.value ?? ""
                    ).trim();


                if (!value) {

                    missingFields.push(
                        field.label
                    );


                    if (!firstMissingField) {

                        firstMissingField =
                            input;

                    }

                }

            }
        );


        // =================================================
        // COORDINATE VALIDATION
        // =================================================

        const latInput =
            submittedForm.querySelector(
                '[name="lat"]'
            );


        const lngInput =
            submittedForm.querySelector(
                '[name="lng"]'
            );


        const latitude =
            latInput?.value.trim() || "";


        const longitude =
            lngInput?.value.trim() || "";


        if (latitude) {

            const lat =
                Number(latitude);


            if (
                !Number.isFinite(lat)
            ) {

                missingFields.push(
                    "Latitude must be a valid number"
                );


                if (!firstMissingField) {

                    firstMissingField =
                        latInput;

                }

            }
            else if (
                lat < -90 ||
                lat > 90
            ) {

                missingFields.push(
                    "Latitude must be between -90 and 90"
                );


                if (!firstMissingField) {

                    firstMissingField =
                        latInput;

                }

            }

        }


        if (longitude) {

            const lng =
                Number(longitude);


            if (
                !Number.isFinite(lng)
            ) {

                missingFields.push(
                    "Longitude must be a valid number"
                );


                if (!firstMissingField) {

                    firstMissingField =
                        lngInput;

                }

            }
            else if (
                lng < -180 ||
                lng > 180
            ) {

                missingFields.push(
                    "Longitude must be between -180 and 180"
                );


                if (!firstMissingField) {

                    firstMissingField =
                        lngInput;

                }

            }

        }


        // =================================================
        // CONTACT VALIDATION
        // =================================================

        const contactRows =
            submittedForm.querySelectorAll(
                ".agency-contact-row"
            );


        let hasHotline = false;
        let hasEmail = false;


        contactRows.forEach(
            (row, index) => {

                const typeSelect =
                    row.querySelector(
                        ".contact-type-select"
                    );


                const valueInput =
                    row.querySelector(
                        ".contact-value-input"
                    );


                const typeValue =
                    typeSelect?.value.trim() || "";


                const contactValue =
                    valueInput?.value.trim() || "";


                const contactNumber =
                    index + 1;


                // =========================================
                // CONTACT TYPE
                // =========================================

                if (!typeValue) {

                    missingFields.push(
                        `Contact ${contactNumber}: Contact Type`
                    );


                    if (!firstMissingField) {

                        firstMissingField =
                            typeSelect;

                    }


                    /*
                     * We cannot determine the contact
                     * type without a selected type.
                     */
                    return;

                }


                // =========================================
                // CONTACT VALUE
                // =========================================

                if (!contactValue) {

                    missingFields.push(
                        `Contact ${contactNumber}: Contact Information`
                    );


                    if (!firstMissingField) {

                        firstMissingField =
                            valueInput;

                    }

                }


                // =========================================
                // CONTACT TYPE LOOKUP
                // =========================================

                const selectedType =
                    getContactType(
                        typeValue
                    );


                if (!selectedType) {

                    missingFields.push(
                        `Contact ${contactNumber}: Invalid Contact Type`
                    );


                    if (!firstMissingField) {

                        firstMissingField =
                            typeSelect;

                    }


                    return;

                }


                const slug =
                    String(
                        selectedType.slug || ""
                    )
                    .toLowerCase()
                    .trim();


                // =========================================
                // REQUIRED TYPES
                // =========================================

                if (
                    slug === "hotline"
                ) {

                    hasHotline = true;

                }


                if (
                    slug === "email"
                ) {

                    hasEmail = true;

                }


                // =========================================
                // EMAIL FORMAT
                // =========================================

                if (
                    slug === "email" &&
                    contactValue
                ) {

                    const emailPattern =
                        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


                    if (
                        !emailPattern.test(
                            contactValue
                        )
                    ) {

                        missingFields.push(
                            `Contact ${contactNumber}: Invalid email format`
                        );


                        if (!firstMissingField) {

                            firstMissingField =
                                valueInput;

                        }

                    }

                }

            }
        );


        // =================================================
        // REQUIRED CONTACT TYPES
        // =================================================

        if (!hasHotline) {

            missingFields.push(
                "At least one Hotline is required"
            );

        }


        if (!hasEmail) {

            missingFields.push(
                "At least one Email is required"
            );

        }


        // =================================================
        // VALID
        // =================================================

        if (
            missingFields.length === 0
        ) {

            return true;

        }


        // =================================================
        // REMOVE DUPLICATES
        // =================================================

        const uniqueErrors =
            [
                ...new Set(
                    missingFields
                )
            ];


        // =================================================
        // ALERT MESSAGE
        // =================================================

        const message =
            uniqueErrors
                .map(
                    item =>
                        `• ${item}`
                )
                .join("\n");


        // =================================================
        // ALERT MODAL
        // =================================================

        showAlertModal({

            title:
                "Incomplete Agency Information",

            text:
                `Please complete or correct the following:\n\n${message}`,

            icon:
                "!",

            variant:
                "danger",

            confirmText:
                "OK",

            showCancel:
                false,

            loading:
                false,

            onConfirm:
                () => {

                    closeAlertModal();


                    setTimeout(
                        () => {

                            if (
                                firstMissingField
                            ) {

                                firstMissingField.focus();


                                firstMissingField.scrollIntoView(
                                    {
                                        behavior:
                                            "smooth",

                                        block:
                                            "center"
                                    }
                                );

                            }

                        },
                        220
                    );

                }

        });


        return false;

    }


    // =====================================================
    // FORM SUBMISSION VALIDATION
    // =====================================================

    if (form) {

        form.addEventListener(
            "submit",
            function (e) {

                /*
                 * View mode must never submit.
                 */
                if (
                    currentMode === "view"
                ) {

                    e.preventDefault();

                    return;

                }


                /*
                 * Synchronize primary contact state
                 * before Laravel receives the form.
                 */
                syncPrimaryContacts();


                /*
                 * Validate the form.
                 */
                if (
                    !validateAgencyForm(
                        form
                    )
                ) {

                    e.preventDefault();

                    return;

                }


                /*
                 * Existing edit operations require
                 * confirmation.
                 *
                 * Add operations submit normally.
                 */
                if (
                    currentMode === "edit"
                ) {

                    e.preventDefault();


                    showAlertModal({

                        title:
                            "Save changes?",

                        text:
                            "Make sure all agency and contact information is correct.",

                        icon:
                            "✓",

                        variant:
                            "success",

                        confirmText:
                            "Save",

                        showCancel:
                            true,

                        onConfirm:
                            () => {

                                closeAlertModal();

                                form.submit();

                            }

                    });

                }

            }
        );

    }


    // =====================================================
    // IMAGE UPLOAD
    // =====================================================

    if (
        uploadBox &&
        fileInput
    ) {

        uploadBox.addEventListener(
            "click",
            function () {

                fileInput.click();

            }
        );


        fileInput.addEventListener(
            "change",
            function () {

                const file =
                    this.files[0];


                if (!file) {
                    return;
                }


                /*
                 * Client-side image MIME validation.
                 *
                 * Laravel must still validate the uploaded
                 * file server-side.
                 */
                if (
                    !file.type.startsWith(
                        "image/"
                    )
                ) {

                    showAlertModal({

                        title:
                            "Invalid Image",

                        text:
                            "Only image files are allowed.",

                        icon:
                            "!",

                        variant:
                            "danger",

                        confirmText:
                            "OK",

                        showCancel:
                            false,

                        loading:
                            false

                    });


                    fileInput.value =
                        "";


                    return;

                }


                /*
                 * 2 MB client-side limit.
                 */
                if (
                    file.size >
                    2 * 1024 * 1024
                ) {

                    showAlertModal({

                        title:
                            "Image Too Large",

                        text:
                            "The maximum image size is 2 MB.",

                        icon:
                            "!",

                        variant:
                            "danger",

                        confirmText:
                            "OK",

                        showCancel:
                            false,

                        loading:
                            false

                    });


                    fileInput.value =
                        "";


                    return;

                }


                const reader =
                    new FileReader();


                reader.onload =
                    function (e) {

                        previewImg.src =
                            e.target.result;


                        previewImg.style.display =
                            "block";


                        placeholder.style.display =
                            "none";

                    };


                reader.readAsDataURL(
                    file
                );

            }
        );

    }


    // =====================================================
    // FILL FORM
    // =====================================================

    function fillForm(data) {

        document.getElementById(
            "agency_name"
        ).value =
            data.name || "";


        document.getElementById(
            "agency_abbreviation"
        ).value =
            data.abbreviation || "";


        const typeSelect =
            document.getElementById(
                "agency_type_id"
            );


        if (typeSelect) {

            [...typeSelect.options]
                .forEach(
                    option => {

                        option.selected =
                            option.value ==
                            data.type_id;

                    }
                );

        }


        const categorySelect =
            document.getElementById(
                "category_id"
            );


        if (categorySelect) {

            [...categorySelect.options]
                .forEach(
                    option => {

                        option.selected =
                            option.value ==
                            data.category_id;

                    }
                );

        }


        document.getElementById(
            "agency_description"
        ).value =
            data.description || "";


        document.getElementById(
            "services_offered"
        ).value =
            data.services_offered || "";


        document.getElementById(
            "agency_location"
        ).value =
            data.location || "";


        loadContacts(
            data.contacts || []
        );


        document.getElementById(
            "office_hours"
        ).value =
            data.office || "";


        document.getElementById(
            "lat"
        ).value =
            data.lat || "";


        document.getElementById(
            "lng"
        ).value =
            data.lng || "";

    }


    // =====================================================
    // ENABLE / DISABLE FORM INPUTS
    // =====================================================

    function enableInputs(
        enable = true
    ) {

        if (!form) {
            return;
        }


        const inputs =
            form.querySelectorAll(
                "input, textarea, select"
            );


        inputs.forEach(
            input => {

                if (
                    input.name === "_method" ||
                    input.type === "hidden"
                ) {

                    return;

                }


                if (enable) {

                    input.removeAttribute(
                        "readonly"
                    );


                    input.disabled =
                        false;


                    if (
                        input.tagName ===
                        "SELECT"
                    ) {

                        input.style.pointerEvents =
                            "auto";

                    }

                }
                else {

                    input.setAttribute(
                        "readonly",
                        true
                    );


                    /*
                     * Keep fields enabled so values can
                     * still be selected/copied.
                     */
                    input.disabled =
                        false;


                    if (
                        input.tagName ===
                        "SELECT"
                    ) {

                        input.style.pointerEvents =
                            "none";

                    }

                }

            }
        );


        enableContactInputs(
            enable
        );

    }


    // =====================================================
    // SET MAP COORDINATES
    // =====================================================

    function setMapToCoordinates(
        lat,
        lng
    ) {

        const latitude =
            parseFloat(lat);


        const longitude =
            parseFloat(lng);


        if (
            !Number.isFinite(latitude) ||
            !Number.isFinite(longitude)
        ) {

            return;

        }


        if (
            latitude < -90 ||
            latitude > 90
        ) {

            return;

        }


        if (
            longitude < -180 ||
            longitude > 180
        ) {

            return;

        }


        if (
            !window.map ||
            !window.marker
        ) {

            return;

        }


        window.marker.setLatLng(
            [
                latitude,
                longitude
            ]
        );


        window.map.setView(
            [
                latitude,
                longitude
            ],
            17
        );

    }


    // =====================================================
    // OPEN MODAL
    // =====================================================

    function openModal(
        mode = "add",
        data = null
    ) {

        currentMode =
            mode;


        modal.style.display =
            "flex";


        setTimeout(
            () => {

                modal.classList.add(
                    "active"
                );

            },
            10
        );


        const title =
            document.getElementById(
                "modal-title"
            );


        if (mode === "add") {

            title.textContent =
                "Add Agency";

        }


        if (mode === "edit") {

            title.textContent =
                "Edit Agency";

        }


        if (mode === "view") {

            title.textContent =
                "View Agency";

        }


        const saveBtn =
            document.querySelector(
                ".btn-save"
            );


        if (saveBtn) {

            saveBtn.disabled =
                mode === "view";

        }


        if (!window.map) {

            initMap();

        }
        else {

            window.map.invalidateSize();

        }


        // =================================================
        // ADD
        // =================================================

        if (mode === "add") {

            /*
             * Reset any previous validation UI.
             */
            if (
                window.resetFormValidation
            ) {

                resetFormValidation(
                    form
                );

            }
            else {

                form.reset();

            }


            /*
             * Remove contacts from the previous agency.
             */
            clearContacts();


            /*
             * Add one blank contact row.
             */
            addContact();


            /*
             * Add route.
             */
            form.action =
                "/admin/agencies";


            const methodInput =
                document.getElementById(
                    "form-method"
                );


            if (methodInput) {

                methodInput.value =
                    "POST";

            }


            /*
             * Reset image preview.
             */
            previewImg.src =
                "https://via.placeholder.com/150";


            previewImg.style.display =
                "none";


            placeholder.style.display =
                "flex";


            /*
             * Reset coordinates.
             */
            document.getElementById(
                "lat"
            ).value = "";


            document.getElementById(
                "lng"
            ).value = "";


            /*
             * Default map location.
             */
            if (
                window.map &&
                window.marker
            ) {

                window.marker.setLatLng(
                    [
                        12.354,
                        121.065
                    ]
                );


                window.map.setView(
                    [
                        12.354,
                        121.065
                    ],
                    15
                );

            }


            enableInputs(true);

            enableContactInputs(true);

        }


        // =================================================
        // EDIT
        // =================================================

        if (
            mode === "edit" &&
            data
        ) {

            form.action =
                `/admin/agencies/${data.id}`;


            const methodInput =
                document.getElementById(
                    "form-method"
                );


            if (methodInput) {

                methodInput.value =
                    "PUT";

            }


            fillForm(
                data
            );


            setMapToCoordinates(
                data.lat,
                data.lng
            );


            if (
                data.image
            ) {

                previewImg.src =
                    `/storage/${data.image}`;


                previewImg.style.display =
                    "block";


                placeholder.style.display =
                    "none";

            }
            else {

                previewImg.src =
                    "https://via.placeholder.com/150";


                previewImg.style.display =
                    "block";


                placeholder.style.display =
                    "none";

            }


            enableInputs(true);

        }


        // =================================================
        // VIEW
        // =================================================

        if (
            mode === "view" &&
            data
        ) {

            form.action =
                "#";


            fillForm(
                data
            );


            setMapToCoordinates(
                data.lat,
                data.lng
            );


            if (
                data.image
            ) {

                previewImg.src =
                    `/storage/${data.image}`;


                previewImg.style.display =
                    "block";


                placeholder.style.display =
                    "none";

            }
            else {

                previewImg.src =
                    "https://via.placeholder.com/150";


                previewImg.style.display =
                    "block";


                placeholder.style.display =
                    "none";

            }


            enableInputs(false);

        }

    }


    window.openModal =
        openModal;


    // =====================================================
    // VIEW AGENCY
    // =====================================================

    document.addEventListener(
        "click",
        function (e) {

            const row =
                e.target.closest(
                    ".agency-row"
                );


            if (!row) {
                return;
            }


            /*
             * Existing buttons/forms handle their own actions.
             */
            if (
                e.target.closest("button") ||
                e.target.closest("form")
            ) {

                return;

            }


            let contacts = [];


            try {

                contacts =
                    JSON.parse(
                        row.dataset.contacts ||
                        "[]"
                    );

            }
            catch (error) {

                console.error(
                    "Unable to read agency contacts:",
                    error
                );

            }


            openModal(
                "view",
                {

                    id:
                        row.dataset.id,

                    name:
                        row.dataset.name,

                    abbreviation:
                        row.dataset.abbreviation,

                    type_id:
                        row.dataset.type_id,

                    category_id:
                        row.dataset.category_id,

                    description:
                        row.dataset.description,

                    services_offered:
                        row.dataset.services_offered,

                    location:
                        row.dataset.location,

                    contacts:
                        contacts,

                    office:
                        row.dataset.office,

                    lat:
                        row.dataset.lat,

                    lng:
                        row.dataset.lng,

                    image:
                        row.dataset.image

                }
            );

        }
    );


    // =====================================================
    // CLOSE MODAL
    // =====================================================

    function closeModal() {

        modal.classList.remove(
            "active"
        );


        setTimeout(
            () => {

                modal.style.display =
                    "none";


                if (
                    window.resetFormValidation
                ) {

                    resetFormValidation(
                        form
                    );

                }
                else {

                    form.reset();

                }


                clearContacts();

            },
            250
        );

    }


    window.closeModal =
        closeModal;


    // =====================================================
    // LEAFLET MAP
    // =====================================================

    function initMap(
        lat = 12.354,
        lng = 121.065
    ) {

        window.map =
            L.map(
                "map",
                {
                    zoomControl:
                        false
                }
            )
            .setView(
                [
                    lat,
                    lng
                ],
                15
            );


        L.tileLayer(
            "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
            {
                attribution:
                    "&copy; OpenStreetMap contributors"
            }
        )
        .addTo(
            window.map
        );


        window.marker =
            L.marker(
                [
                    lat,
                    lng
                ],
                {
                    draggable:
                        true
                }
            )
            .addTo(
                window.map
            );


        window.marker.on(
            "dragend",
            function () {

                const pos =
                    window.marker.getLatLng();


                updateCoords(
                    pos.lat,
                    pos.lng
                );

            }
        );


        window.map.on(
            "click",
            function (e) {

                window.marker.setLatLng(
                    e.latlng
                );


                updateCoords(
                    e.latlng.lat,
                    e.latlng.lng
                );

            }
        );

    }


    // =====================================================
    // SEARCH LOCATION
    // =====================================================

    async function searchLocation() {

        const query =
            document
                .getElementById(
                    "searchLocation"
                )
                .value
                .trim();


        const btn =
            document.getElementById(
                "searchBtn"
            );


        if (!query) {

            showAlertModal({

                title:
                    "Location Required",

                text:
                    "Please enter a location to search.",

                icon:
                    "!",

                variant:
                    "danger",

                confirmText:
                    "OK",

                showCancel:
                    false,

                loading:
                    false

            });


            return;

        }


        if (
            !window.map ||
            !window.marker
        ) {

            initMap();

        }


        btn.disabled =
            true;


        btn.textContent =
            "Searching...";


        try {

            const res =
                await fetch(
                    `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`,
                    {
                        headers: {
                            "Accept":
                                "application/json"
                        }
                    }
                );


            if (!res.ok) {

                throw new Error(
                    "Location search failed."
                );

            }


            const data =
                await res.json();


            if (!data.length) {

                showAlertModal({

                    title:
                        "Location Not Found",

                    text:
                        "No matching location could be found.",

                    icon:
                        "!",

                    variant:
                        "danger",

                    confirmText:
                        "OK",

                    showCancel:
                        false,

                    loading:
                        false

                });


                return;

            }


            const place =
                data[0];


            const lat =
                parseFloat(
                    place.lat
                );


            const lng =
                parseFloat(
                    place.lon
                );


            window.map.setView(
                [
                    lat,
                    lng
                ],
                15
            );


            window.marker.setLatLng(
                [
                    lat,
                    lng
                ]
            );


            updateCoords(
                lat,
                lng
            );

        }
        catch (err) {

            console.error(
                "Search error:",
                err
            );


            showAlertModal({

                title:
                    "Search Error",

                text:
                    "Something went wrong while searching for the location.",

                icon:
                    "!",

                variant:
                    "danger",

                confirmText:
                    "OK",

                showCancel:
                    false,

                loading:
                    false

            });

        }
        finally {

            btn.disabled =
                false;


            btn.textContent =
                "Search";

        }

    }


    window.searchLocation =
        searchLocation;


    // =====================================================
    // UPDATE COORDINATES
    // =====================================================

    function updateCoords(
        lat,
        lng
    ) {

        document.getElementById(
            "lat"
        ).value =
            lat;


        document.getElementById(
            "lng"
        ).value =
            lng;


        getAddress(
            lat,
            lng
        );

    }


    // =====================================================
    // MANUAL COORDINATE SYNC
    // =====================================================

    function updateMapFromCoordinates() {

        const latInput =
            document.getElementById(
                "lat"
            );


        const lngInput =
            document.getElementById(
                "lng"
            );


        if (
            !latInput ||
            !lngInput
        ) {

            return;

        }


        const lat =
            parseFloat(
                latInput.value.trim()
            );


        const lng =
            parseFloat(
                lngInput.value.trim()
            );


        if (
            !Number.isFinite(lat) ||
            !Number.isFinite(lng)
        ) {

            return;

        }


        if (
            lat < -90 ||
            lat > 90
        ) {

            return;

        }


        if (
            lng < -180 ||
            lng > 180
        ) {

            return;

        }


        if (
            !window.map ||
            !window.marker
        ) {

            return;

        }


        window.marker.setLatLng(
            [
                lat,
                lng
            ]
        );


        window.map.setView(
            [
                lat,
                lng
            ],
            17
        );


        getAddress(
            lat,
            lng
        );

    }


    const latInput =
        document.getElementById(
            "lat"
        );


    const lngInput =
        document.getElementById(
            "lng"
        );


    let coordinateUpdateTimer =
        null;


    function scheduleCoordinateUpdate() {

        clearTimeout(
            coordinateUpdateTimer
        );


        coordinateUpdateTimer =
            setTimeout(
                () => {

                    updateMapFromCoordinates();

                },
                5000
            );

    }


    if (latInput) {

        latInput.addEventListener(
            "input",
            scheduleCoordinateUpdate
        );

    }


    if (lngInput) {

        lngInput.addEventListener(
            "input",
            scheduleCoordinateUpdate
        );

    }


    // =====================================================
    // REVERSE GEOCODING
    // =====================================================

    async function getAddress(
        lat,
        lng
    ) {

        try {

            const res =
                await fetch(
                    `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`,
                    {
                        headers: {
                            "Accept":
                                "application/json"
                        }
                    }
                );


            if (!res.ok) {
                return;
            }


            const data =
                await res.json();


            if (
                data?.display_name
            ) {

                document.getElementById(
                    "agency_location"
                ).value =
                    data.display_name;

            }

        }
        catch (err) {

            console.error(
                "Geocoding error:",
                err
            );

        }

    }


    window.getAddress =
        getAddress;


    // =====================================================
    // EDIT BUTTON
    // =====================================================

    document.addEventListener(
        "click",
        function (e) {

            const btn =
                e.target.closest(
                    ".btn-primary"
                );


            if (!btn) {
                return;
            }


            /*
             * Ignore buttons that aren't agency edit buttons.
             */
            if (
                btn.closest(
                    "form"
                )
            ) {

                return;

            }


            let contacts = [];


            try {

                contacts =
                    JSON.parse(
                        btn.dataset.contacts ||
                        "[]"
                    );

            }
            catch (error) {

                console.error(
                    "Unable to read agency contacts:",
                    error
                );

            }


            openModal(
                "edit",
                {

                    id:
                        btn.dataset.id,

                    name:
                        btn.dataset.name,

                    abbreviation:
                        btn.dataset.abbreviation,

                    type_id:
                        btn.dataset.type_id,

                    category_id:
                        btn.dataset.category_id,

                    description:
                        btn.dataset.description,

                    services_offered:
                        btn.dataset.services_offered,

                    location:
                        btn.dataset.location,

                    contacts:
                        contacts,

                    office:
                        btn.dataset.office,

                    lat:
                        btn.dataset.lat,

                    lng:
                        btn.dataset.lng,

                    image:
                        btn.dataset.image

                }
            );

        }
    );


    // =====================================================
    // MODAL CLOSE EVENTS
    // =====================================================

    modal.addEventListener(
        "click",
        function (e) {

            if (
                e.target === modal
            ) {

                closeModal();

            }

        }
    );


    document.addEventListener(
        "keydown",
        function (e) {

            if (
                e.key === "Escape" &&
                modal.classList.contains(
                    "active"
                )
            ) {

                closeModal();

            }

        }
    );


    // =====================================================
    // SEARCH BUTTON
    // =====================================================

    const searchBtn =
        document.getElementById(
            "searchBtn"
        );


    if (searchBtn) {

        searchBtn.addEventListener(
            "click",
            searchLocation
        );

    }


    // =====================================================
    // DELETE WITH MODAL
    // =====================================================

    document.addEventListener(
        "click",
        function (e) {

            const btn =
                e.target.closest(
                    ".delete-btn"
                );


            if (!btn) {
                return;
            }


            const deleteForm =
                btn.closest(
                    "form"
                );


            if (!deleteForm) {
                return;
            }


            e.preventDefault();


            showAlertModal({

                title:
                    "Move agency to Trash?",

                text:
                    "This agency will be moved to the Trashed records.",

                icon:
                    "!",

                variant:
                    "danger",

                confirmText:
                    "Trash",

                showCancel:
                    true,

                onConfirm:
                    () => {

                        deleteForm.submit();

                    }

            });

        }
    );


    // =====================================================
    // RESTORE AGENCY
    // =====================================================

    document.addEventListener(
        "click",
        function (e) {

            const btn =
                e.target.closest(
                    ".restore-btn"
                );


            if (!btn) {
                return;
            }


            const restoreForm =
                btn.closest(
                    ".restore-form"
                );


            if (!restoreForm) {
                return;
            }


            e.preventDefault();


            const agencyName =
                btn.dataset.agencyName ||
                "this agency";


            showAlertModal({

                title:
                    "Restore agency?",

                text:
                    `"${agencyName}" will be returned to the active agency list.`,

                icon:
                    "↶",

                variant:
                    "success",

                confirmText:
                    "Restore",

                showCancel:
                    true,

                onConfirm:
                    () => {

                        restoreForm.submit();

                    }

            });

        }
    );


    // =====================================================
    // PERMANENT DELETE
    // =====================================================

    document.addEventListener(
        "click",
        function (e) {

            const btn =
                e.target.closest(
                    ".force-delete-btn"
                );


            if (!btn) {
                return;
            }


            const forceDeleteForm =
                btn.closest(
                    ".force-delete-form"
                );


            if (!forceDeleteForm) {
                return;
            }


            e.preventDefault();


            const agencyName =
                btn.dataset.agencyName ||
                "this agency";


            showAlertModal({

                title:
                    "Permanently delete agency?",

                text:
                    `"${agencyName}" and its associated data will be permanently removed. This action cannot be undone.`,

                icon:
                    "!",

                variant:
                    "danger",

                confirmText:
                    "Delete Permanently",

                showCancel:
                    true,

                onConfirm:
                    () => {

                        forceDeleteForm.submit();

                    }

            });

        }
    );


    // =====================================================
    // FLASH SUCCESS
    // =====================================================

    if (
        window.__FLASH_SUCCESS__
    ) {

        showAlertModal({

            title:
                "Success",

            text:
                window.__FLASH_SUCCESS__,

            icon:
                "✓",

            variant:
                "success",

            confirmText:
                "OK",

            showCancel:
                false,

            loading:
                false,

            onConfirm:
                () => {

                    closeAlertModal();

                }

        });


        setTimeout(
            () => {

                closeAlertModal();

            },
            1500
        );


        window.__FLASH_SUCCESS__ =
            null;

    }

});