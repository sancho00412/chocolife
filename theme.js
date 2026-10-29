// Loaded in <head> so the saved theme is applied before the page is drawn.
const THEME_KEY = 'chocolifeTheme';

function isDarkTheme() {
    try {
        return localStorage.getItem(THEME_KEY) === 'dark';
    } catch {
        return false;
    }
}

function applyTheme(dark) {
    document.documentElement.classList.toggle('dark-theme-variables', dark);

    document.querySelectorAll('.theme-toggler').forEach(toggler => {
        const [lightIcon, darkIcon] = toggler.querySelectorAll('span');

        if (lightIcon) lightIcon.classList.toggle('active', !dark);
        if (darkIcon) darkIcon.classList.toggle('active', dark);
    });
}

applyTheme(isDarkTheme());

document.addEventListener('DOMContentLoaded', () => {
    applyTheme(isDarkTheme());

    document.querySelectorAll('.theme-toggler').forEach(toggler => {
        toggler.addEventListener('click', () => {
            const dark =
                !document.documentElement.classList.contains('dark-theme-variables');

            try {
                localStorage.setItem(THEME_KEY, dark ? 'dark' : 'light');
            } catch {
                // Storage unavailable: theme still switches for this page.
            }

            applyTheme(dark);
        });
    });
});
