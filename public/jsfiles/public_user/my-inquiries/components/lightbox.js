/**
 * KNOWURLOCAL
 * My Inquiries — Image Lightbox
 *
 * Handles opening and closing attached inquiry images
 * in an accessible fullscreen preview.
 */

function initializeLightbox() {
    "use strict";


    /*
    |--------------------------------------------------------------------------
    | LIGHTBOX ELEMENTS
    |--------------------------------------------------------------------------
    */

    const imageLightbox =
        document.getElementById(
            "image-lightbox"
        );

    const imageLightboxImage =
        document.getElementById(
            "image-lightbox-image"
        );

    const imageLightboxClose =
        document.getElementById(
            "image-lightbox-close"
        );


    /*
    | If the page does not contain the lightbox,
    | there is nothing for this module to initialize.
    */
    if (
        !imageLightbox ||
        !imageLightboxImage
    ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | FOCUS + SCROLL STATE
    |--------------------------------------------------------------------------
    */

    let previouslyFocusedElement = null;

    let originalBodyOverflow = "";


    /*
    |--------------------------------------------------------------------------
    | OPEN LIGHTBOX
    |--------------------------------------------------------------------------
    */

    const openImageLightbox = (
        trigger
    ) => {
        if (!trigger) {
            return;
        }


        /*
        | Support both old and current image data attributes.
        |
        | The current inquiry-card component uses data-image-url.
        */
        const imageSource =
            trigger.dataset.imagePreview ||
            trigger.dataset.imageSrc ||
            trigger.dataset.imageUrl;


        if (!imageSource) {
            return;
        }


        /*
        | Prefer an explicitly supplied alt value.
        |
        | If none exists, look for the image associated with
        | the trigger.
        */
        const imageAlt =
            trigger.dataset.imageAlt ||
            trigger
                .closest(
                    ".inquiry-image-block, .response-image"
                )
                ?.querySelector("img")
                ?.getAttribute("alt") ||
            "Attached inquiry image";


        /*
        | Remember the element that opened the lightbox.
        |
        | This allows keyboard users to return to their previous
        | position after closing the preview.
        */
        previouslyFocusedElement =
            document.activeElement;


        /*
        | Remember the page's current scrolling state.
        */
        originalBodyOverflow =
            document.body.style.overflow;


        /*
        | Set the image source only after validating that
        | a source was actually provided.
        */
        const imageFallback = imageLightbox.querySelector(".inquiry-lightbox-placeholder");

        // Reset the preview, open the dialog first, then request the image.
        // A 404/403/expired attachment must leave the viewer open with a fallback.
        imageLightboxImage.hidden = true;
        if (imageFallback) imageFallback.hidden = true;
        imageLightboxImage.removeAttribute("src");
        imageLightboxImage.alt = imageAlt;

        imageLightbox.classList.add("is-open");

        imageLightbox.setAttribute(
            "aria-hidden",
            "false"
        );

        imageLightboxImage.src = imageSource;

        /*
        | Prevent the underlying page from scrolling while
        | the image preview is open.
        */
        document.body.style.overflow =
            "hidden";


        /*
        | Move keyboard focus into the lightbox.
        */
        imageLightboxClose?.focus();
    };


    /*
    |--------------------------------------------------------------------------
    | CLOSE LIGHTBOX
    |--------------------------------------------------------------------------
    */

    const closeImageLightbox = () => {
        if (!imageLightbox) {
            return;
        }


        /*
        | Hide the lightbox.
        */
        imageLightbox.classList.remove(
            "is-open"
        );

        imageLightbox.setAttribute(
            "aria-hidden",
            "true"
        );


        /*
        | Remove the image source after closing.
        |
        | This prevents the browser from unnecessarily retaining
        | a potentially large attachment in memory.
        */
        imageLightboxImage.removeAttribute(
            "src"
        );

        imageLightboxImage.removeAttribute(
            "alt"
        );


        /*
        | Restore the page's original scrolling behavior.
        */
        document.body.style.overflow =
            originalBodyOverflow;


        /*
        | Return focus to the element that opened the dialog.
        */
        if (
            previouslyFocusedElement &&
            document.contains(
                previouslyFocusedElement
            )
        ) {
            previouslyFocusedElement.focus();
        }


        previouslyFocusedElement = null;
    };


    /*
    |--------------------------------------------------------------------------
    | IMAGE EVENTS
    |--------------------------------------------------------------------------
    |
    | Event delegation is used here because response images can
    | be inserted or replaced when realtime inquiry data arrives.
    |
    */

    document.addEventListener(
        "click",
        (event) => {
            const trigger =
                event.target.closest(
                    "[data-image-preview], [data-image-url]"
                );

            if (!trigger) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            openImageLightbox(trigger);
        }
    );


    /*
    |--------------------------------------------------------------------------
    | THUMBNAIL FALLBACKS
    |--------------------------------------------------------------------------
    |
    | Avoid inline onerror attributes: production Content Security Policies
    | can block them. Capture image errors for dynamically-rendered inquiry
    | attachments and reveal the adjacent shared x-image-placeholder.
    */

    const revealThumbnailPlaceholder = (image) => {
        if (!(image instanceof HTMLImageElement)) return;
        const placeholder = image.nextElementSibling;
        if (!placeholder?.classList.contains("kyl-image-placeholder")) return;
        image.hidden = true;
        placeholder.hidden = false;
    };

    const bindThumbnailFallbacks = (scope = document) => {
        scope.querySelectorAll?.(
            ".response-image-trigger > img, .messenger-avatar > img"
        ).forEach((image) => {
            if (image.complete && image.naturalWidth === 0) {
                revealThumbnailPlaceholder(image);
            }
        });
    };

    document.addEventListener("error", (event) => {
        const image = event.target;
        if (
            image instanceof HTMLImageElement &&
            image.matches(".response-image-trigger > img, .messenger-avatar > img")
        ) {
            revealThumbnailPlaceholder(image);
        }
    }, true);

    bindThumbnailFallbacks();
    const thumbnailObserver = new MutationObserver((records) => {
        records.forEach((record) => {
            record.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (node.matches?.(".response-image-trigger > img, .messenger-avatar > img")) {
                        if (node.complete && node.naturalWidth === 0) revealThumbnailPlaceholder(node);
                    }
                    bindThumbnailFallbacks(node);
                }
            });
        });
    });
    thumbnailObserver.observe(document.body, { childList: true, subtree: true });

    /*
    |--------------------------------------------------------------------------
    | CLOSE BUTTON
    |--------------------------------------------------------------------------
    */

    imageLightboxClose?.addEventListener(
        "click",
        (event) => {
            event.preventDefault();

            closeImageLightbox();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | BACKDROP CLICK
    |--------------------------------------------------------------------------
    */

    imageLightbox.addEventListener(
        "click",
        (event) => {
            /*
            | Only clicking the actual backdrop closes the dialog.
            | Clicking the image itself should not close it.
            */
            if (
                event.target ===
                imageLightbox
            ) {
                closeImageLightbox();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | ESCAPE KEY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "keydown",
        (event) => {
            if (
                event.key !== "Escape"
            ) {
                return;
            }


            if (
                imageLightbox.classList.contains(
                    "is-open"
                )
            ) {
                closeImageLightbox();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | IMAGE LOAD FAILURE
    |--------------------------------------------------------------------------
    */

    imageLightboxImage.addEventListener(
        "load",
        () => {
            imageLightboxImage.hidden = false;
            const imageFallback = imageLightbox.querySelector(".inquiry-lightbox-placeholder");
                if (imageFallback) imageFallback.hidden = true;
            }
    );

    imageLightboxImage.addEventListener(
        "error",
        () => {
            imageLightboxImage.hidden = true;
            const imageFallback = imageLightbox.querySelector(".inquiry-lightbox-placeholder");
                if (imageFallback) imageFallback.hidden = false;
            // Deliberately keep the dialog open: an unavailable attachment is
            // still a valid preview state, not a reason to dismiss the viewer.

            console.warn(
                "KNOWURLOCAL: The inquiry image could not be loaded."
            );
        }
    );
}


/*
|--------------------------------------------------------------------------
| ES MODULE EXPORT
|--------------------------------------------------------------------------
|
| index.js imports this exact function:
|
| import { initializeLightbox } from './components/lightbox.js';
|
*/

export {
    initializeLightbox
};