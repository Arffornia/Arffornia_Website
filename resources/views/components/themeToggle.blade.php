{{-- Sun/moon switch for the whole site. Other widgets (progression graph) listen to `arffornia:theme`. --}}
<button type="button" class="theme-toggle" data-theme-toggle
    data-label-dark="{{ __('nav.theme_dark') }}" data-label-light="{{ __('nav.theme_light') }}">
    <svg class="theme-toggle__sun" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4V2M12 22v-2M4.9 4.9 3.5 3.5M20.5 20.5l-1.4-1.4M4 12H2M22 12h-2M4.9 19.1l-1.4 1.4M20.5 3.5l-1.4 1.4M12 17a5 5 0 1 0 0-10 5 5 0 0 0 0 10z"/></svg>
    <svg class="theme-toggle__moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/></svg>
</button>
<script>
    (function () {
        var KEY = 'arffornia.theme';
        var root = document.documentElement;
        var buttons = document.querySelectorAll('[data-theme-toggle]');
        function sync() {
            var dark = root.dataset.theme === 'dark';
            buttons.forEach(function (b) {
                var label = dark ? b.dataset.labelLight : b.dataset.labelDark;
                b.title = label;
                b.setAttribute('aria-label', label);
            });
        }
        function apply(theme, store) {
            root.dataset.theme = theme;
            if (store) { try { localStorage.setItem(KEY, theme); } catch (e) {} }
            sync();
            window.dispatchEvent(new CustomEvent('arffornia:theme', { detail: { theme: theme } }));
        }
        window.ArfforniaTheme = {
            get: function () { return root.dataset.theme === 'dark' ? 'dark' : 'light'; },
            set: function (theme) { apply(theme === 'dark' ? 'dark' : 'light', true); },
        };
        buttons.forEach(function (b) {
            b.addEventListener('click', function () { window.ArfforniaTheme.set(root.dataset.theme === 'dark' ? 'light' : 'dark'); });
        });
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
                var stored = null;
                try { stored = localStorage.getItem(KEY); } catch (err) {}
                if (stored !== 'light' && stored !== 'dark') apply(e.matches ? 'dark' : 'light', false);
            });
        }
        sync();
    })();
</script>
