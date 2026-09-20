/* ============================================================
   KNOWURLOCAL
   LANDING PAGE
   landing.js
   ============================================================

   Responsibilities
   ------------------------------------------------------------
   01. DOM references
   02. Shared utilities
   03. Mobile navigation
   04. Scroll state
   05. Reveal animations
   06. Journey route generation
   07. Journey route gradient
   08. Journey marker movement
   09. Active milestone detection
   10. Navigation behavior
   11. Scroll events
   12. Resize handling
   13. Initialization
   ============================================================ */


(() => {

    "use strict";


    /* =========================================================
       01. DOM REFERENCES
       ========================================================= */

    const page =
        document.querySelector("[data-landing-page]");


    /*
     * The script is intentionally defensive.
     *
     * If the landing page root does not exist, the script
     * exits immediately instead of generating console errors.
     */

    if (!page) {
        return;
    }


    const header =
        document.querySelector("[data-landing-header]");

    const menuToggle =
        document.querySelector("[data-menu-toggle]");

    const mobileMenu =
        document.querySelector("[data-mobile-menu]");

    const mobileLinks =
        document.querySelectorAll("[data-mobile-link]");

    const navLinks =
        document.querySelectorAll("[data-nav-target]");


    /*
     * Journey route elements.
     */

    const journey =
        document.querySelector("[data-journey]");

    const routeSvg =
        document.querySelector("[data-route]");

    const routeBase =
        document.querySelector("[data-route-base]");

    const routeProgress =
        document.querySelector("[data-route-progress]");

    const routeMarker =
        document.querySelector("[data-route-marker]");

    /*
     * Main page sections.
     */

    const hero =
        document.querySelector("[data-hero]");

    const arriveSection =
        document.querySelector(
            '[data-route-milestone="arrive"]'
        );


    const milestoneSections =
        Array.from(
            document.querySelectorAll(
                "[data-route-milestone]"
            )
        );


    /*
     * Reveal elements.
     *
     * Both normal reveal elements and chat reveal elements
     * use the same IntersectionObserver.
     */

    const revealElements =
        document.querySelectorAll(
            "[data-reveal], [data-chat-reveal]"
        );


    /* =========================================================
       02. SHARED UTILITIES
       ========================================================= */

    const prefersReducedMotion =
        window.matchMedia(
            "(prefers-reduced-motion: reduce)"
        );


    /*
     * Keeps a number inside a specified range.
     *
     * Example:
     *
     * clamp(1.4, 0, 1)
     *
     * returns:
     *
     * 1
     */

    const clamp = (
        value,
        min,
        max
    ) => {

        return Math.min(
            Math.max(
                value,
                min
            ),
            max
        );

    };


    /*
     * Scroll calculations are synchronized with the browser's
     * rendering cycle.
     *
     * This avoids repeatedly performing expensive DOM work
     * during a high-frequency scroll event.
     */

    let animationFrameId = null;


    const requestUpdate = () => {

        if (animationFrameId !== null) {
            return;
        }


        animationFrameId =
            window.requestAnimationFrame(() => {

                animationFrameId = null;

                updateScrollState();

                updateJourneyProgress();

            });

    };


    /* =========================================================
       03. MOBILE NAVIGATION
       ========================================================= */

    const setMenuIcon = (isOpen) => {

        if (!menuToggle) {
            return;
        }


        const icon =
            menuToggle.querySelector("i");


        if (!icon) {
            return;
        }


        icon.classList.toggle(
            "ph-list",
            !isOpen
        );

        icon.classList.toggle(
            "ph-x",
            isOpen
        );

    };


    const closeMobileMenu = () => {

        if (!header || !menuToggle) {
            return;
        }


        header.classList.remove(
            "is-menu-open"
        );


        menuToggle.setAttribute(
            "aria-expanded",
            "false"
        );


        menuToggle.setAttribute(
            "aria-label",
            "Open navigation menu"
        );


        if (mobileMenu) {

            mobileMenu.setAttribute(
                "aria-hidden",
                "true"
            );

        }


        setMenuIcon(false);

    };


    const openMobileMenu = () => {

        if (!header || !menuToggle) {
            return;
        }


        header.classList.add(
            "is-menu-open"
        );


        menuToggle.setAttribute(
            "aria-expanded",
            "true"
        );


        menuToggle.setAttribute(
            "aria-label",
            "Close navigation menu"
        );


        if (mobileMenu) {

            mobileMenu.setAttribute(
                "aria-hidden",
                "false"
            );

        }


        setMenuIcon(true);

    };


    if (menuToggle) {

        menuToggle.addEventListener(
            "click",
            () => {

                const isOpen =
                    header.classList.contains(
                        "is-menu-open"
                    );


                if (isOpen) {

                    closeMobileMenu();

                } else {

                    openMobileMenu();

                }

            }
        );

    }


    /*
     * Clicking a mobile navigation item closes the menu.
     */

    mobileLinks.forEach((link) => {

        link.addEventListener(
            "click",
            () => {

                closeMobileMenu();

            }
        );

    });


    /*
     * Escape is a standard keyboard interaction for dismissing
     * an open navigation menu.
     */

    document.addEventListener(
        "keydown",
        (event) => {

            if (event.key !== "Escape") {
                return;
            }


            closeMobileMenu();

        }
    );


    /* =========================================================
       04. SCROLL STATE
       ========================================================= */

    const updateScrollState = () => {

        if (!header) {
            return;
        }


        /*
         * The header receives a smaller state after the user
         * moves away from the top of the page.
         */

        header.classList.toggle(
            "is-scrolled",
            window.scrollY > 24
        );

    };


    /* =========================================================
       05. REVEAL ANIMATIONS
       ========================================================= */

    /*
     * IntersectionObserver lets the browser efficiently detect
     * when elements enter and leave the viewport.
     *
     * Unlike the previous implementation, elements remain
     * observed after becoming visible.
     *
     * This allows the animation to replay every time the user
     * scrolls away from the element and then returns to it.
     */

    if (
        "IntersectionObserver" in window &&
        !prefersReducedMotion.matches
    ) {

        const revealObserver =
            new IntersectionObserver(
                (entries) => {

                    entries.forEach(
                        (entry) => {

                            /*
                             * Element entered the viewport.
                             *
                             * Adding "is-visible" allows the
                             * existing CSS transition to animate
                             * the element into its visible state.
                             */

                            if (entry.isIntersecting) {

                                entry.target.classList.add(
                                    "is-visible"
                                );

                                return;
                            }


                            /*
                             * Element left the viewport.
                             *
                             * Removing "is-visible" returns the
                             * element to its CSS-defined hidden
                             * state.
                             *
                             * When it enters the viewport again,
                             * the class is added again and the
                             * transition replays.
                             */

                            entry.target.classList.remove(
                                "is-visible"
                            );

                        }
                    );

                },
                {
                    /*
                     * The animation begins when approximately
                     * 12% of the element is visible.
                     */

                    threshold: 0.12,

                    /*
                     * Start the reveal slightly before the
                     * element reaches the lower edge of the
                     * visible viewport.
                     */

                    rootMargin:
                        "0px 0px -8% 0px"
                }
            );


        /*
         * Register every reveal element with the observer.
         *
         * We intentionally do NOT call unobserve().
         */

        revealElements.forEach(
            (element) => {

                revealObserver.observe(
                    element
                );

            }
        );

    } else {

        /*
         * Users who prefer reduced motion should still receive
         * all content immediately.
         */

        revealElements.forEach(
            (element) => {

                element.classList.add(
                    "is-visible"
                );

            }
        );

    }


    /* =========================================================
       06. JOURNEY ROUTE GENERATION
       ========================================================= */

    /*
     * The route uses a normalized SVG viewBox:
     *
     * width  = 100
     * height = 1000
     *
     * This allows the route to scale with the page without
     * hard-coding physical pixel dimensions.
     */

    const getRoutePath = () => {

        /*
         * Mobile route:
         *
         * Mostly vertical with controlled side-to-side
         * movement so it doesn't aggressively cross content.
         */

        if (window.innerWidth <= 760) {

            return [

                "M 82 55",

                "C 82 100, 88 135, 80 175",

                "C 73 215, 75 255, 84 292",

                "C 91 325, 89 360, 79 395",

                "C 70 430, 73 465, 84 500",

                "C 92 535, 90 570, 80 605",

                "C 72 640, 74 675, 84 710",

                "C 91 745, 89 780, 81 815",

                /*
                 * Extended endpoint.
                 *
                 * The route is lifted using CSS, so the path
                 * itself extends farther down to compensate.
                 */

                "C 76 850, 78 882, 84 910"

            ].join(" ");

        }


        /*
         * Desktop route:
         *
         * Starts on the right, travels left, returns right,
         * then continues through multiple organic curves.
         */

        return [

            "M 88 55",

            "C 88 105, 84 140, 70 175",

            "C 54 215, 30 230, 19 270",

            "C 8 310, 13 350, 31 382",

            "C 49 414, 76 432, 84 465",

            "C 93 502, 86 540, 66 573",

            "C 45 606, 21 622, 16 658",

            "C 11 694, 27 724, 47 747",

            "C 67 770, 84 791, 85 817",

            /*
             * Extended endpoint.
             */

            "C 86 850, 82 878, 78 910"

        ].join(" ");

    };


    /*
     * Convert an SVG coordinate into a screen coordinate.
     *
     * getPointAtLength() returns values inside the SVG's
     * viewBox. CSS positioning requires actual rendered
     * coordinates, so we calculate the scale here.
     */

    const svgPointToScreen = (
        point
    ) => {

        if (!routeSvg) {

            return {
                x: 0,
                y: 0
            };

        }


        const rect =
            routeSvg.getBoundingClientRect();


        const viewBoxWidth = 100;

        const viewBoxHeight = 1000;


        return {

            x:
                rect.left +
                (
                    point.x /
                    viewBoxWidth
                ) *
                rect.width,

            y:
                rect.top +
                (
                    point.y /
                    viewBoxHeight
                ) *
                rect.height

        };

    };


    /*
     * Build the route itself.
     */

    const buildRoute = () => {

        if (
            !routeSvg ||
            !routeBase ||
            !routeProgress
        ) {
            return;
        }


        const path =
            getRoutePath();


        routeBase.setAttribute(
            "d",
            path
        );


        routeProgress.setAttribute(
            "d",
            path
        );


        /*
         * SVG calculates the exact rendered length of the
         * Bezier path.
         */

        const length =
            routeProgress.getTotalLength();


        routeProgress.style.strokeDasharray =
            String(length);


        routeProgress.style.strokeDashoffset =
            String(length);


        /*
         * Store the calculated length so the scroll handler
         * does not need to calculate it repeatedly.
         */

        routeProgress.dataset.routeLength =
            String(length);

            journeyMetrics.routeLength =
    length;


        /*
         * The gradient is rebuilt whenever the route itself
         * is rebuilt because the SVG dimensions can change
         * during responsive resizing.
         */

        buildRouteGradient();

        refreshJourneyMetrics();

    };


    /* =========================================================
       07. JOURNEY ROUTE GRADIENT
       ========================================================= */

    /*
     * The route should remain green while travelling through
     * the normal page.
     *
     * Once it reaches ARRIVE, the green background makes the
     * original green route difficult to see.
     *
     * Therefore the SVG route changes to white at ARRIVE.
     *
     * We use an SVG gradient instead of changing the CSS stroke
     * directly because the route is one continuous SVG path.
     */

    const buildRouteGradient = () => {

        if (!routeSvg) {
            return;
        }


        /*
         * Find an existing <defs> element or create one.
         */

        let defs =
            routeSvg.querySelector(
                "defs"
            );


        if (!defs) {

            defs =
                document.createElementNS(
                    "http://www.w3.org/2000/svg",
                    "defs"
                );


            routeSvg.prepend(
                defs
            );

        }


        /*
         * Reuse the same gradient instead of creating a new
         * gradient every time the browser resizes.
         */

        let gradient =
            defs.querySelector(
                "#landingRouteGradient"
            );


        if (!gradient) {

            gradient =
                document.createElementNS(
                    "http://www.w3.org/2000/svg",
                    "linearGradient"
                );


            gradient.setAttribute(
                "id",
                "landingRouteGradient"
            );


            gradient.setAttribute(
                "x1",
                "0%"
            );


            gradient.setAttribute(
                "y1",
                "0%"
            );


            gradient.setAttribute(
                "x2",
                "0%"
            );


            gradient.setAttribute(
                "y2",
                "100%"
            );


            defs.appendChild(
                gradient
            );

        }


        /*
         * Remove old stops before rebuilding them.
         */

        while (
            gradient.firstChild
        ) {

            gradient.removeChild(
                gradient.firstChild
            );

        }


        /*
         * Determine where ARRIVE begins relative to the
         * rendered SVG height.
         */

        let arrivePercentage = 90;


        if (arriveSection) {

            const svgRect =
                routeSvg.getBoundingClientRect();


            /*
             * getBoundingClientRect() is viewport-relative.
             *
             * Add scrollY to convert the SVG's top position
             * back into document coordinates.
             */

            const svgDocumentTop =
                svgRect.top +
                window.scrollY;


            const svgHeight =
                Math.max(
                    svgRect.height,
                    1
                );


            const arriveDocumentTop =
                arriveSection.offsetTop;


            arrivePercentage =
                (
                    (
                        arriveDocumentTop -
                        svgDocumentTop
                    ) /
                    svgHeight
                ) *
                100;


            arrivePercentage =
                clamp(
                    arrivePercentage,
                    0,
                    100
                );

        }


        /*
         * The green route should transition shortly before
         * ARRIVE rather than switching abruptly exactly on
         * the section boundary.
         */

        const transitionStart =
            clamp(
                arrivePercentage - 3,
                0,
                100
            );


        const transitionEnd =
            clamp(
                arrivePercentage + 1,
                0,
                100
            );


        /*
         * Helper for creating SVG gradient stops safely.
         */

        const createStop = (
            offset,
            color,
            opacity
        ) => {

            const stop =
                document.createElementNS(
                    "http://www.w3.org/2000/svg",
                    "stop"
                );


            stop.setAttribute(
                "offset",
                `${offset}%`
            );


            stop.setAttribute(
                "stop-color",
                color
            );


            stop.setAttribute(
                "stop-opacity",
                String(opacity)
            );


            return stop;

        };


        /*
         * Normal route.
         */

        gradient.appendChild(
            createStop(
                0,
                "#176b52",
                0.70
            )
        );


        /*
         * Keep the route green until just before ARRIVE.
         */

        gradient.appendChild(
            createStop(
                transitionStart,
                "#176b52",
                0.70
            )
        );


        /*
         * Transition through a muted light tone.
         */

        gradient.appendChild(
            createStop(
                transitionEnd,
                "#ffffff",
                0.70
            )
        );


        /*
         * Keep the route white throughout ARRIVE.
         */

        gradient.appendChild(
            createStop(
                100,
                "#ffffff",
                0.82
            )
        );


        /*
         * Apply the gradient to both route layers.
         */

        const gradientReference =
            "url(#landingRouteGradient)";


        routeBase.style.stroke =
            gradientReference;


        routeProgress.style.stroke =
            gradientReference;

    };


   /* =========================================================
   08. JOURNEY MARKER MOVEMENT
   ========================================================= */

/*
 * Geometry used by the marker is cached instead of being
 * recalculated during every scroll frame.
 *
 * Professional rule:
 * expensive layout measurements belong outside the
 * high-frequency scroll path whenever possible.
 */
const journeyMetrics = {
    routeLength: 0,
    routeRect: null,
    journeyRect: null,
};


/*
 * Refresh the measurements that only change when the page
 * layout or viewport dimensions change.
 *
 * This function is intentionally NOT called on every scroll.
 */
const refreshJourneyMetrics = () => {

    if (
        !routeSvg ||
        !routeBase ||
        !journey
    ) {
        return;
    }


    /*
     * getTotalLength() is relatively expensive.
     *
     * The route does not change while the user is scrolling,
     * so calculate its length only when the route is rebuilt
     * or the viewport is resized.
     */
    journeyMetrics.routeLength =
        routeBase.getTotalLength();


    /*
     * Cache the SVG's rendered position and dimensions.
     *
     * These values are required to convert SVG coordinates
     * into actual screen coordinates.
     */
    journeyMetrics.routeRect =
        routeSvg.getBoundingClientRect();


    /*
     * Cache the journey container's position as well.
     *
     * This lets us calculate the marker position relative
     * to the journey without forcing another layout read
     * during scrolling.
     */
    journeyMetrics.journeyRect =
        journey.getBoundingClientRect();

};


/*
 * Calculate how far the user has travelled through the
 * landing-page journey.
 */
const getJourneyProgress = () => {

    if (
        !hero ||
        !arriveSection
    ) {
        return 0;
    }


    /*
     * The route begins slightly into the hero so the marker
     * does not start underneath the fixed navigation bar.
     */
    const start =
        hero.offsetTop +
        (
            window.innerHeight *
            0.20
        );


    /*
     * ARRIVE represents the destination.
     */
    const end =
        arriveSection.offsetTop;


    /*
     * Prevent division by zero if the two positions somehow
     * become identical.
     */
    const distance =
        Math.max(
            end - start,
            1
        );


    /*
     * Convert the current scroll position into a normalized
     * value between 0 and 1.
     */
    return clamp(
        (
            window.scrollY -
            start
        ) /
        distance,
        0,
        1
    );

};


/*
 * Update the marker's ARRIVE appearance.
 */
const updateArriveMarkerState = () => {

    if (!arriveSection) {
        return;
    }


    /*
     * Switch the marker treatment slightly before ARRIVE
     * completely occupies the viewport.
     */
    const arriveThreshold =
        arriveSection.offsetTop -
        (
            window.innerHeight *
            0.08
        );


    const isArrive =
        window.scrollY >=
        arriveThreshold;


    if (routeMarker) {

        routeMarker.classList.toggle(
            "is-arrive",
            isArrive
        );

    }

};


/*
 * Convert an SVG point into coordinates relative to the
 * journey container.
 *
 * IMPORTANT:
 * This function uses cached rectangles instead of calling
 * getBoundingClientRect() during every scroll frame.
 */
const svgPointToJourneyPosition = (
    point
) => {

    const routeRect =
        journeyMetrics.routeRect;

    const journeyRect =
        journeyMetrics.journeyRect;


    if (
        !routeRect ||
        !journeyRect
    ) {
        return {
            left: 0,
            top: 0
        };
    }


    const viewBoxWidth = 100;
    const viewBoxHeight = 1000;


    /*
     * Convert the normalized SVG X/Y coordinates into
     * actual rendered viewport coordinates.
     */
    const screenX =
        routeRect.left +
        (
            point.x /
            viewBoxWidth
        ) *
        routeRect.width;


    const screenY =
        routeRect.top +
        (
            point.y /
            viewBoxHeight
        ) *
        routeRect.height;


    /*
     * Convert viewport coordinates into coordinates relative
     * to the journey container.
     */
    return {
        left:
            screenX -
            journeyRect.left,

        top:
            screenY -
            journeyRect.top
    };

};


/*
 * Update everything visually associated with the route.
 *
 * This function is intentionally kept lightweight because it
 * runs in the browser's rendering cycle while the user scrolls.
 */
const updateJourneyProgress = () => {

    if (
        !routeProgress ||
        !routeMarker ||
        !routeBase ||
        !journey
    ) {
        return;
    }


    const progress =
        getJourneyProgress();


    /*
     * -----------------------------------------------------
     * Update progress stroke
     * -----------------------------------------------------
     */

    if (
        journeyMetrics.routeLength > 0
    ) {

        routeProgress.style.strokeDashoffset =
            String(
                journeyMetrics.routeLength *
                (
                    1 -
                    progress
                )
            );

    }


    /*
     * -----------------------------------------------------
     * Find marker position on the route
     * -----------------------------------------------------
     */

    if (
        journeyMetrics.routeLength <= 0
    ) {
        return;
    }


    /*
     * getPointAtLength() is still required because the marker
     * needs to follow the actual Bezier curve.
     *
     * Unlike getTotalLength(), this calculation depends on
     * the current scroll progress, so it remains here.
     */
    const point =
        routeBase.getPointAtLength(
            journeyMetrics.routeLength *
            progress
        );


    const position =
        svgPointToJourneyPosition(
            point
        );


    /*
     * Update only the two visual properties required for
     * marker positioning.
     */
    routeMarker.style.left =
        `${position.left}px`;


    routeMarker.style.top =
        `${position.top}px`;


    /*
     * Update the contrasting ARRIVE treatment.
     */
    updateArriveMarkerState();

};


    const setActiveMilestone = (
    id
) => {

    /*
     * The floating journey labels have been removed.
     *
     * The milestone system now only controls the active
     * navigation item in the navbar.
     */

    navLinks.forEach(
        (link) => {

            const target =
                link.dataset.navTarget;


            link.classList.toggle(
                "is-active",
                target === id
            );

        }
    );

};


    /*
     * Determine which section is currently dominant in the
     * viewport.
     */

    if (
        "IntersectionObserver" in window
    ) {

        const milestoneObserver =
            new IntersectionObserver(
                (entries) => {

                    const visibleSections =
                        entries
                            .filter(
                                (entry) =>
                                    entry.isIntersecting
                            )
                            .sort(
                                (a, b) =>
                                    b.intersectionRatio -
                                    a.intersectionRatio
                            );


                    if (
                        visibleSections.length === 0
                    ) {
                        return;
                    }


                    const activeSection =
                        visibleSections[0]
                            .target;


                    const id =
                        activeSection
                            .dataset
                            .routeMilestone;


                    setActiveMilestone(
                        id
                    );

                },
                {
                    threshold: [
                        0.15,
                        0.30,
                        0.50,
                        0.70
                    ],

                    rootMargin:
                        "-15% 0px -35% 0px"
                }
            );


        milestoneSections.forEach(
            (section) => {

                milestoneObserver.observe(
                    section
                );

            }
        );

    }


    /* =========================================================
       10. NAVIGATION BEHAVIOR
       ========================================================= */

    navLinks.forEach(
        (link) => {

            link.addEventListener(
                "click",
                (event) => {

                    const targetId =
                        link.dataset.navTarget;


                    /*
                     * Only allow navigation to elements that
                     * actually exist in the current document.
                     */

                    if (!targetId) {
                        return;
                    }


                    const target =
                        document.getElementById(
                            targetId
                        );


                    if (!target) {
                        return;
                    }


                    event.preventDefault();


                    target.scrollIntoView({

                        behavior:
                            prefersReducedMotion.matches
                                ? "auto"
                                : "smooth",

                        block: "start"

                    });


                    closeMobileMenu();

                }
            );

        }
    );


    /* =========================================================
       11. SCROLL EVENTS
       ========================================================= */

    window.addEventListener(
        "scroll",
        requestUpdate,
        {
            passive: true
        }
    );


    /* =========================================================
       12. RESIZE HANDLING
       ========================================================= */

    let resizeTimer =
        null;


    /*
     * Resize events can fire many times per second while a user
     * is dragging a browser window.
     *
     * Debouncing prevents unnecessary SVG rebuilding.
     */

    const handleResize = () => {

        if (window.innerWidth > 760) {

            closeMobileMenu();

        }


        window.clearTimeout(
            resizeTimer
        );


        resizeTimer =
            window.setTimeout(
                () => {

                    buildRoute();

                    updateJourneyProgress();

                },
                120
            );

    };


    window.addEventListener(
        "resize",
        handleResize
    );


    /* =========================================================
       13. INITIALIZATION
       ========================================================= */

    const initialize = () => {

    /*
     * Build the route before measuring its length.
     */

    buildRoute();


    /*
     * Establish the initial header state.
     */

    updateScrollState();


    /*
     * Set the initial journey stage BEFORE positioning
     * the marker and label.
     *
     * This prevents the label from briefly displaying
     * stale markup such as "05 ARRIVE".
     */

    setActiveMilestone(
        "discover"
    );


    /*
     * Position the marker and label after the initial
     * milestone state has been established.
     */

    updateJourneyProgress();

};


    /*
     * The Blade should load this file with `defer`, so in most
     * cases the DOM is already available.
     *
     * This fallback also makes the script safe if its loading
     * strategy changes later.
     */

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            initialize,
            {
                once: true
            }
        );

    } else {

        initialize();

    }


})();