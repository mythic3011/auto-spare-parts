// language.js
export function updatePageLanguage(language, languageMap) {
    const translations = languageMap[language];
    for (const key in translations) {
        const element = document.getElementById(key);
        if (element) {
            element.textContent = translations[key];
        }
    }
}
