import './bootstrap';

// Language menu: apply the choice as soon as it is picked (components/localeSwitcher.blade.php).
document.addEventListener('change', (event) => {
    if (event.target.matches('.locale-switcher__select')) event.target.form.requestSubmit();
});
