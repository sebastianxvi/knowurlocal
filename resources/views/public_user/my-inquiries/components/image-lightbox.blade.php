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
            onerror="this.hidden=true; this.nextElementSibling.hidden=false;"
        >
        <x-image-placeholder type="image" class="inquiry-lightbox-placeholder" :hidden="true" />

    </div>


    <span
        id="image-lightbox-title"
        hidden
    >
        Attached image preview
    </span>

</div>