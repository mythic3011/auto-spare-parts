(function initStaffNavTemplateGlobal() {
  function navLink(href, label, activeKey, currentKey) {
    const isActive = activeKey === currentKey;
    const baseClass =
      "block py-2 px-3 rounded dark:text-white dark:hover:bg-gray-700";
    const stateClass = isActive
      ? "bg-gray-100 text-gray-900 dark:bg-gray-700"
      : "text-gray-900 hover:bg-gray-100";
    return `<a class="${baseClass} ${stateClass}" href="${href}">${label}</a>`;
  }

  function desktopNav(activeKey) {
    return `
      <nav class="bg-white border-gray-200 dark:bg-gray-900 w-64 flex-shrink-0 hidden md:flex md:flex-col">
        <div class="flex flex-col items-center p-4">
          <a class="flex items-center space-x-3 rtl:space-x-reverse mb-6" href="#">
            <div class="h-8 w-8 relative">
              <img alt="SLMC Logo" class="absolute inset-0 object-contain dark:hidden pt-2 dark:pt-1" src="../../resources/image/SLMC_LOGO.png"/>
              <img alt="SLMC Logo" class="absolute inset-0 object-contain hidden dark:block" src="../../resources/image/SLMC_LOGO_DM.png"/>
            </div>
            <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">SLMC</span>
          </a>
          <ul class="font-medium flex flex-col space-y-2">
            <li>${navLink("order.html", "Order Record", activeKey, "order")}</li>
            <li>${navLink("Item.html", "Spare Part", activeKey, "item")}</li>
            <li>${navLink("genreport.html", "Generate Report", activeKey, "report")}</li>
            <li><a class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" href="../../logout.html" onclick="logout()">Log Out</a></li>
          </ul>
          <button class="mt-6 p-2 text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700" id="darkModeToggle">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
          </button>
        </div>
      </nav>`;
  }

  function mobileHeader() {
    return `
      <div class="md:hidden flex items-center justify-between p-4 bg-white dark:bg-gray-900">
        <a href="#" class="flex items-center space-x-3 rtl:space-x-reverse">
          <div class="h-8 w-8 relative">
            <img alt="SLMC Logo" class="absolute inset-0 object-contain dark:hidden pt-2 dark:pt-1" src="../../resources/image/SLMC_LOGO.png"/>
            <img alt="SLMC Logo" class="absolute inset-0 object-contain hidden dark:block" src="../../resources/image/SLMC_LOGO_DM.png"/>
          </div>
          <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">SLMC</span>
        </a>
        <button class="p-2 text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700" id="mobileMenuButton">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
      </div>`;
  }

  function mobileMenu(activeKey) {
    return `
      <div class="md:hidden" id="mobileMenu" style="display: none;">
        <ul class="font-medium flex flex-col space-y-2 p-4 bg-white dark:bg-gray-900">
          <li>${navLink("order.html", "Order Record", activeKey, "order")}</li>
          <li>${navLink("Item.html", "Spare Part", activeKey, "item")}</li>
          <li>${navLink("genreport.html", "Generate Report", activeKey, "report")}</li>
          <li><a class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" href="../../logout.html" onclick="logout()">Log Out</a></li>
        </ul>
      </div>`;
  }

  window.renderStaffNavTemplate = function renderStaffNavTemplate(activeKey) {
    const desktopHost = document.getElementById("staffDesktopNavHost");
    const mobileHeaderHost = document.getElementById("staffMobileHeaderHost");
    const mobileMenuHost = document.getElementById("staffMobileMenuHost");

    if (desktopHost) {
      desktopHost.outerHTML = desktopNav(activeKey || "");
    }
    if (mobileHeaderHost) {
      mobileHeaderHost.outerHTML = mobileHeader();
    }
    if (mobileMenuHost) {
      mobileMenuHost.outerHTML = mobileMenu(activeKey || "");
    }
  };
})();
