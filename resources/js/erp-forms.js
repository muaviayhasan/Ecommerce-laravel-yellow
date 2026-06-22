/**
 * ErpForms — central initialiser for jQuery-based form enhancements.
 *
 * Per CONVENTIONS.md §1 & §7, this is the single place that wires up:
 *   - Select2 on every <select> (opt out with `data-no-select2`)
 *   - jquery-mask-plugin on inputs carrying `data-mask`
 *
 * Inside Livewire components, wrap the field in `wire:ignore` and call
 * `window.ErpForms.init(el)` again after the DOM updates.
 */
import jQuery from 'jquery';
import select2 from 'select2';
import 'jquery-mask-plugin';

// Select2 4.1 exports a CommonJS factory `(root, jQuery) => {…}` that installs
// `$.fn.select2`. When bundled, a bare `import 'select2'` imports the factory but
// never calls it, so `$(...).select2` is undefined. Invoke it with our jQuery.
select2(window, jQuery);

/**
 * Named mask aliases (CONVENTIONS.md §1.2). Any other `data-mask` value is
 * treated as a raw jquery-mask pattern.
 */
const MASKS = {
    cnic: '00000-0000000-0', // e.g. 32301-0000000-0
    phone: '0000-0000000',   // Pakistani mobile, 03 prefix
};

function initSelect2(root) {
    $(root)
        .find('select')
        .addBack('select')
        .not('[data-no-select2]')
        .not('.select2-hidden-accessible')
        .each(function () {
            $(this).select2({
                width: '100%',
                // Render the dropdown inside the nearest dialog/modal so it
                // works when fields live inside an overlay.
                dropdownParent: $(this).closest('.modal, [role="dialog"]').length
                    ? $(this).closest('.modal, [role="dialog"]')
                    : $(document.body),
            });
        });
}

function initMasks(root) {
    $(root)
        .find('[data-mask]')
        .addBack('[data-mask]')
        .each(function () {
            const requested = $(this).data('mask');
            const pattern = MASKS[requested] ?? requested;

            if (pattern) {
                $(this).mask(pattern);
            }
        });
}

/**
 * Initialise every enhancement within `root` (defaults to the whole document).
 */
function init(root = document) {
    initSelect2(root);
    initMasks(root);
}

window.ErpForms = { init, MASKS };

// Initial page load + Livewire SPA navigation / DOM updates.
document.addEventListener('DOMContentLoaded', () => init());
document.addEventListener('livewire:navigated', () => init());
document.addEventListener('livewire:updated', () => init());

export default window.ErpForms;
