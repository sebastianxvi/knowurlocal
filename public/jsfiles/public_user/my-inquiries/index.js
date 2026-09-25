import {
    initializeAccordion,
} from './components/accordion.js';

import {
    initializeFilters,
} from './components/filters.js';

import {
    initializeConfirmation,
} from './components/confirmation.js';

import {
    initializeLightbox,
} from './components/lightbox.js';

import {
    initializeRealtime,
} from './components/realtime.js';


/*
|--------------------------------------------------------------------------
| INITIALIZE MY INQUIRIES MODULES
|--------------------------------------------------------------------------
|
| Each feature owns its own interaction logic.
|
| This file acts as the single entry point for the page and keeps the
| initialization order explicit and predictable.
|--------------------------------------------------------------------------
*/

initializeAccordion();

initializeFilters();

initializeConfirmation();

initializeLightbox();

initializeRealtime();