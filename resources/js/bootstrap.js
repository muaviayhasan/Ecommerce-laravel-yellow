import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * jQuery is exposed globally so jQuery-based plugins such as Select2 and
 * jquery-mask-plugin can attach themselves to it.
 */
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel (configured for Reverb in echo.js).
 */
import './echo';
