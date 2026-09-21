import {
    initializeRequestTable,
} from './components/request-table.js';

import {
    initializeManageModal,
} from './components/manage-modal.js';

import {
    initializeResponseBuilder,
} from './components/response-builder.js';

import {
    initializeSimilarFaq,
} from './components/similar-faq.js';

import {
    initializeRealtime,
} from './components/realtime.js';


/*
|--------------------------------------------------------------------------
| INITIALIZE SUPPORT REQUEST MODULES
|--------------------------------------------------------------------------
|
| Each feature owns its own initialization logic.
| Keeping these calls here gives the page a single,
| predictable entry point.
|--------------------------------------------------------------------------
*/

initializeRequestTable();

initializeManageModal();

initializeResponseBuilder();

initializeSimilarFaq();

initializeRealtime();


/*
|--------------------------------------------------------------------------
| HANDLE FLASH SUCCESS MESSAGE
|--------------------------------------------------------------------------
|
| Laravel places the session success message into
| window.__FLASH_SUCCESS__ from the Blade view.
|
| This module then displays it using the shared alert
| modal instead of relying on browser alerts.
|--------------------------------------------------------------------------
*/

if (
    window.__FLASH_SUCCESS__ &&
    typeof window.showAlertModal === 'function'
) {
    window.showAlertModal({
        title: 'Success',
        text: window.__FLASH_SUCCESS__,
        icon: 'ph-light ph-check-circle',
        variant: 'success',
        confirmText: 'OK',
        showCancel: false,
        loading: false,
    });
}