{{-- Applies the stored theme, else the system one, before the first paint (no flash of the wrong theme).
     The toggle (components/themeToggle.blade.php) stores the choice under the same key. --}}
<script>
    (function () {
        var theme = null;
        try { theme = localStorage.getItem('arffornia.theme'); } catch (e) {}
        if (theme !== 'light' && theme !== 'dark') {
            theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.dataset.theme = theme;
    })();
</script>
