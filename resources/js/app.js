import './bootstrap';

const THEME_KEY = 'xd-radius-theme';
const THEME_OPTIONS = new Set(['system', 'light', 'dark']);

function resolveTheme(preference) {
    if (preference === 'light' || preference === 'dark') return preference;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function applyTheme(preference) {
    const safePreference = THEME_OPTIONS.has(preference) ? preference : 'system';
    document.documentElement.dataset.themePreference = safePreference;
    document.documentElement.dataset.theme = resolveTheme(safePreference);

    const selector = document.getElementById('theme-preference');
    if (selector && selector.value !== safePreference) selector.value = safePreference;
}

async function saveThemePreference(preference) {
    try {
        const response = await fetch('/user/preferences', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ key: 'theme', value: preference }),
        });

        if (!response.ok) throw new Error(`Theme preference save failed (${response.status})`);
    } catch (error) {
        // The selected theme remains active for this browser even if account sync fails.
        console.warn('Could not sync theme preference:', error);
    }
}

function initializeTheme() {
    const root = document.documentElement;
    const selector = document.getElementById('theme-preference');
    const serverPreference = root.dataset.themePreference;
    let preference = THEME_OPTIONS.has(serverPreference) ? serverPreference : 'system';

    try {
        const browserPreference = window.localStorage.getItem(THEME_KEY);
        if (THEME_OPTIONS.has(browserPreference)) preference = browserPreference;
        else window.localStorage.setItem(THEME_KEY, preference);
    } catch (_) {
        // Storage may be unavailable; the server preference still works.
    }

    applyTheme(preference);

    selector?.addEventListener('change', async (event) => {
        const nextPreference = event.target.value;
        if (!THEME_OPTIONS.has(nextPreference)) return;

        applyTheme(nextPreference);
        try {
            window.localStorage.setItem(THEME_KEY, nextPreference);
        } catch (_) {
            // Keep the in-memory selection even when storage is unavailable.
        }
        await saveThemePreference(nextPreference);
    });

    const media = window.matchMedia('(prefers-color-scheme: dark)');
    media.addEventListener?.('change', () => {
        if (document.documentElement.dataset.themePreference === 'system') {
            applyTheme('system');
        }
    });
}

document.addEventListener('DOMContentLoaded', initializeTheme, { once: true });
