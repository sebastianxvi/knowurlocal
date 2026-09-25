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
        imageLightboxImage.src =
            imageSource;

        imageLightboxImage.alt =
            imageAlt;


        /*
        | Make the lightbox visible.
        */
        imageLightbox.classList.add(
            "is-open"
        );

        imageLightbox.setAttribute(
            "aria-hidden",
            "false"
        );


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

            openImageLightbox(
                trigger
            );
        }
    );


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
        "error",
        () => {
            closeImageLightbox();

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