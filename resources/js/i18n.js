/**
 * Tiny translation helper for front-end scripts. Translations are rendered by Blade into a JSON blob
 * (e.g. `window.AppData.i18n = @json(__('shop.js'))`), so no strings are hardcoded in JS.
 *
 * @param {object|undefined} dict Translation dictionary rendered by the page.
 * @param {string} key Key in that dictionary.
 * @param {object} [replace] Values for `:name` placeholders.
 * @returns {string} The translated string, or the key when missing (easy to spot).
 */
export function t(dict, key, replace = {}) {
    let line = dict && typeof dict[ key ] === 'string' ? dict[ key ] : key;
    for (const [ name, value ] of Object.entries(replace)) {
        line = line.split(`:${name}`).join(String(value));
    }
    return line;
}
