// main.js
import { logout } from './module/auth.js';
import { toggleDarkMode, setInitialDarkMode } from './module/darkMode.js';
import { updatePageLanguage } from './module/language.js';

document.addEventListener('DOMContentLoaded', function() {
    const $ = id => document.getElementById(id);
    // Dark mode
    setInitialDarkMode();
    const themeToggle = $('darkModeToggle');
    if (themeToggle && !themeToggle.dataset.themeBound) {
        themeToggle.dataset.themeBound = 'true';
        themeToggle.addEventListener('click', toggleDarkMode);
    }

    // Mobile menu
    const mobileMenuButton = $('mobile-menu-button');
    const navbar = $('navbar-default');

    function toggleMobileMenu() {
        navbar?.classList.toggle('hidden');
    }

    mobileMenuButton?.addEventListener('click', toggleMobileMenu);

    // Language
    const languageSelect = $("language");
    if (languageSelect && typeof window.languageMap === "object") {
        const savedLanguage = localStorage.getItem("language") || "en";
        languageSelect.value = savedLanguage;
        updatePageLanguage(savedLanguage, window.languageMap); // Assuming languageMap is defined globally

        languageSelect.addEventListener("change", function () {
            const selectedLanguage = this.value;
            localStorage.setItem("language", selectedLanguage);
            updatePageLanguage(selectedLanguage, window.languageMap);
        });
    }

    // Logout functionality
    $('logoutButton')?.addEventListener('click', logout);

    // Store the current page in the history array
    const historyArr = JSON.parse(sessionStorage.getItem('pageHistory')) || [];
    historyArr.push(window.location.href);
    sessionStorage.setItem('pageHistory', JSON.stringify(historyArr));
});
