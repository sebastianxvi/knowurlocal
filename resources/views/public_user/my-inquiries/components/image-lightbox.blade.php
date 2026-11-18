<div
    class="image-lightbox"
    id="image-lightbox"
    role="dialog"
    aria-modal="true"
    aria-labelledby="image-lightbox-title"
    aria-hidden="true"
>

    <div class="image-lightbox-content">

        <button
            type="button"
            class="image-lightbox-close"
            id="image-lightbox-close"
            aria-label="Close image preview"
        >

            <i
                class="ph-light ph-x"
                aria-hidden="true"
            ></i>

        </button>


        <img
            src=""
            alt=""
            class="image-lightbox-image"
            id="image-lightbox-image"
            hidden
        >
        <x-image-placeholder type="image" class="inquiry-lightbox-placeholder" :hidden="true" />
        <p class="inquiry-lightbox-unavailable" role="status" hidden>
            Image data cannot be retrieved. This attachment is no longer available.
        </p>

    </div>


    <span
        id="image-lightbox-title"
        hidden
    >
        Attached image preview
    </span>

</div>