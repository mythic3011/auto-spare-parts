function showProductDialog(title, message, ids = [], confirmLabel = '') {
    const dialog = document.createElement('dialog');
    dialog.className = 'w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 text-gray-900 shadow-xl backdrop:bg-gray-950/60 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    dialog.setAttribute('aria-labelledby', 'productDialogTitle');
    const heading = document.createElement('h2');
    heading.id = 'productDialogTitle';
    heading.className = 'text-xl font-semibold';
    heading.textContent = title;
    const text = document.createElement('p');
    text.className = 'mt-4 text-sm leading-relaxed text-gray-600 dark:text-gray-400';
    text.textContent = message;
    dialog.append(heading, text);
    if (ids.length) {
        const products = document.createElement('p');
        products.className = 'mt-3 text-sm font-medium';
        products.textContent = 'Product IDs: ' + ids.join(', ');
        dialog.append(products);
    }
    const actions = document.createElement('div');
    actions.className = 'mt-6 flex justify-end gap-3';
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'rounded-xl border border-gray-300 px-4 py-2 text-sm dark:border-gray-600';
    close.textContent = confirmLabel ? 'Cancel' : 'Close';
    close.addEventListener('click', () => dialog.close('cancel'));
    actions.append(close);
    if (confirmLabel) {
        const confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.className = 'rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white';
        confirm.textContent = confirmLabel;
        confirm.addEventListener('click', () => dialog.close('confirm'));
        actions.append(confirm);
    }
    dialog.append(actions);
    document.body.append(dialog);
    return new Promise(resolve => {
        dialog.addEventListener('close', () => {
            const confirmed = dialog.returnValue === 'confirm';
            dialog.remove();
            resolve(confirmed);
        }, { once: true });
        dialog.showModal();
        close.focus();
    });
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function safeImageFile(value) {
    const name = String(value ?? "");
    return /^[A-Za-z0-9._-]+$/.test(name) ? name : "NoImage.jpg";
}

const GLOBAL_ENDPOINTS = window.APP_API_ENDPOINTS || {};
const MANAGE_ITEM_API_URL = GLOBAL_ENDPOINTS.manageItem;

if (!MANAGE_ITEM_API_URL) {
    throw new Error("Missing APP_API_ENDPOINTS.manageItem");
}

function buildUrl(base, params = {}) {
    const query = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && String(value).trim() !== '') {
            query.set(key, String(value));
        }
    });
    const serialized = query.toString();
    return serialized ? `${base}?${serialized}` : base;
}

document.addEventListener('DOMContentLoaded', function () {
    getSparePartList();
    const darkModeToggle = document.getElementById('darkModeToggle');


    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const navbarDefault = document.getElementById('navbar-default');
    mobileMenuButton?.addEventListener('click', function () {
        navbarDefault.classList.toggle('hidden');
    });
});



function getSparePartList() {
    if (!document.getElementById('sparePartsTableBody')) return;
    return loadAndRenderSpareParts();
}


async function itemAction(action) {
    if (action === 'add') {
        console.log('Add item');
        window.location.href = 'AddItem.html';
    } else if (action === 'edit') {
        const checkboxes = document.querySelectorAll('#sparePartsTableBody input[type="checkbox"]:checked');
        
        if (checkboxes.length === 0) {
            alert('Please select an item to edit');
            return;
        }
        
        if (checkboxes.length > 1) {
            alert('Please select only one item to edit');
            return;
        }
        
        const selectedRow = checkboxes[0].closest('tr');
        const partNumber = selectedRow.querySelector('td:nth-child(2)').textContent;
        
        window.location.href = `editItem.html?id=${encodeURIComponent(partNumber)}`;
        
    } else if (action === 'delete') {
        const checkboxes = document.querySelectorAll('#sparePartsTableBody input[type="checkbox"]:checked');
        if (checkboxes.length === 0) {
            alert('No items selected for deletion');
            return;
        }

        const selectedIds = Array.from(checkboxes).map(checkbox => {
            const row = checkbox.closest('tr');
            return row.querySelector('td:nth-child(2)').textContent; 
        });

        if (await showProductDialog('Delete selected products?', 'This permanently removes products that are not used in any order. If a selected product is locked, the entire selection will be kept.', selectedIds, 'Delete products')) {
            fetchAPIForItemList('BatchDelete', 'POST', { ids: selectedIds })
                .then(result => {
                    console.log('Items deleted:', result);
                    showProductDialog('Products deleted', 'The selected products were deleted.');
                    loadAndRenderSpareParts(); // Refresh the table
                })
                .catch(error => {
                    console.error('Error deleting items:', error);
                    showProductDialog(error.code === 'PRODUCT_IN_ORDER' ? 'Products locked by orders' : 'Could not delete products', error.message, error.lockedItemIds || []);
                });
        }

        console.log('Delete items with Part Numbers:', selectedIds);
    }
}


//item list
function renderSparePartsTable(spareParts) {
    const tableBody = document.getElementById('sparePartsTableBody');

    const totalItems = document.getElementById('total-items');
        if (totalItems) {
            totalItems.textContent = `Total: ${spareParts.length} Items`;
        }

    tableBody.replaceChildren(); // Clear the table body

    spareParts.forEach((part, index) => {
        const row = document.createElement('tr');
        row.dataset.category = String(part.sparePartCategory);
        row.dataset.stock = String(part.stockItemQty);
        row.dataset.locked = String(Number(part.deletionLocked) === 1);
        row.dataset.search = [part.sparePartNum, part.sparePartName, part.sparePartDescription].join(' ');
        row.className = index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700';
        category = "N/A";
        switch (part.sparePartCategory) {
            case 1:
                category = "A";
                break;
            case 2:
                category = "B";
                break;
            case 3:
                category = "C";
                break;
            case 4:
                category = "D";
                break;
            default:
                category = "N/A";
                break;
        }
        row.innerHTML = `
         <td class="w-4 p-4">
            <div class="flex items-center">
                <input id="checkbox-table-${escapeHtml(part.sparePartNum)}" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                       data-part-number="${escapeHtml(part.sparePartNum)}">
                <label for="checkbox-table-${escapeHtml(part.sparePartNum)}" class="sr-only">checkbox</label>
            </div>
        </td>
        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">${escapeHtml(part.sparePartNum)}</td>
        <td class="px-6 py-4">${escapeHtml(category)}</td>
        <td class="px-6 py-4">${escapeHtml(part.sparePartName)}${Number(part.deletionLocked) === 1 ? '<span class="mt-2 block text-xs font-semibold text-amber-700 dark:text-amber-400">Locked · in order records</span>' : ''}</td>
        <td class="px-6 py-4"><img src="../../resources/image/spare/${escapeHtml(safeImageFile(part.sparePartImage))}" alt="${escapeHtml(part.name)}" class="w-10 h-10 rounded-full" onerror="this.src='../../resources/image/spare/NoImage.jpg'"></td>
        <td class="px-6 py-4">${escapeHtml(part.sparePartDescription)}</td>
        <td class="px-6 py-4">${escapeHtml(part.weight)}</td>
        <td class="px-6 py-4">${escapeHtml(part.stockItemQty)}</td>
        <td class="px-6 py-4">${escapeHtml(part.price)} HKD</td>
    `;
        tableBody.appendChild(row);
    });
}

function fetchAPIForItemList(action = 'list', method = 'GET', data = null) {
    const apiUrl = buildUrl(MANAGE_ITEM_API_URL, { action });
    const AuthToken = window.SLMC_AUTH.getToken();

    console.log('Fetching from URL:', apiUrl);
    console.log('Action:', action);
    console.log('Method:', method);

    const fetchOptions = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${AuthToken}`
        },
        credentials: 'include'
    };

    if (method === 'POST' && data) {
        fetchOptions.body = JSON.stringify(data);
    }

    return fetch(apiUrl, fetchOptions)
        .then(async response => {
            const result = await response.json();
            if (!response.ok) {
                const error = new Error(result.message || 'The product request failed.');
                error.code = result.code;
                error.lockedItemIds = result.lockedItemIds;
                throw error;
            }
            return result;
        })
        .then(result => {
            console.log('Received data:', result);
            if (result.status === 'success') {
                return result.data;
            } else {
                throw new Error(result.message || 'Failed to fetch spare parts data');
            }
        })
        .catch(error => {
            console.error('Error fetching spare parts data:', error);
            throw error;
        });
}

function loadAndRenderSpareParts() {
    console.log("loadAndRenderSpareParts called");
    fetchAPIForItemList('list', 'GET')
        .then(spareParts => {
            if (spareParts && spareParts.length > 0) {
                console.log("Received spare parts:", spareParts);
                renderSparePartsTable(spareParts);
            } else {
                console.log("No spare parts data received");
                document.getElementById('sparePartsTableBody').innerHTML = '<tr><td colspan="9">No spare parts data available</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error loading spare parts:', error);
            document.getElementById('sparePartsTableBody').innerHTML = `<tr><td colspan="9">Error: ${escapeHtml(error.message)}</td></tr>`;
        });
}

function initSpareParts() {
    loadAndRenderSpareParts();
}


//edit item
function fetchAPIForEdit(id) {
    const apiUrl = buildUrl(MANAGE_ITEM_API_URL, { action: 'get', id });
    const AuthToken = window.SLMC_AUTH.getToken();

    console.log('Fetching from URL:', apiUrl);

    return fetch(apiUrl, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${AuthToken}`
        },
        credentials: 'include'
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
            });
        }
        return response.json();
    })
    .then(result => {
        console.log('Received data:', result);
        if (result.status === 'success') {
            return result.data;
        } else {
            throw new Error(result.message || 'Failed to fetch spare part data');
        }
    });
}

function fetchItemData() {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');

    if (!id) {
        alert('No item ID provided');
        return;
    }

    console.log('Fetching item with ID:', id);

    fetchAPIForEdit(id)
        .then(itemData => {
            console.log('Item data received:', itemData);
            
            if (!itemData || typeof itemData !== 'object') {
                throw new Error('Invalid item data received');
            }

            let categoryHashMap = {
                'A': 'Sheet Metal',
                'B': 'Major Asssemblies',
                'C': 'Light Components',
                'D': 'Accessories',
            };

            function getCategory(category) {
                if (Number.isInteger(category) && category >= 1 && category <= 26) {
                    return String.fromCharCode(64 + category);
                } else {
                    console.log(category);
                    return 'N/A';
                }
            }

            // Update the DOM with the fetched data, using optional chaining and nullish coalescing
            document.getElementById("sparePartNumber").textContent = itemData.sparePartNum ?? '';
            document.getElementById("sparePartCategory").textContent = getCategory(itemData.sparePartCategory) ?? '';
            document.getElementById("sparePartName").textContent = itemData.sparePartName ?? '';
            document.getElementById("sparePartWeight").textContent = itemData.weight ?? '';
            document.getElementById("input-stock-quantity").value = itemData.stockItemQty ?? '';
            document.getElementById("input-price").value = itemData.price ?? '';
            document.getElementById("description").value = itemData.sparePartDescription ?? '';
            
            const currentImageElement = document.getElementById("currentImage");
            if (currentImageElement) {
                if (itemData.sparePartImage) {
                    currentImageElement.src = `../../resources/image/spare/${safeImageFile(itemData.sparePartImage)}`;
                    currentImageElement.alt = 'Spare Part Image';
                } else {
                    currentImageElement.src = ''; // Set to empty or a default image path
                    currentImageElement.alt = 'No image available';
                }
            }
        })
        .catch(error => {
            console.error('Error fetching item data:', error);
            alert('Error fetching item data: ' + error.message);
        });
}


function updateItem() {
    const id = document.getElementById("sparePartNumber").textContent;
    const updateData = {
        id: id,
        stockItemQty: document.getElementById("input-stock-quantity").value,
        price: document.getElementById("input-price").value,
        sparePartDescription: document.getElementById("description").value
    };

    const fileInput = document.getElementById("file");
    if (fileInput.files.length > 0) {
        // If a new image is selected, we need to handle file upload differently
        // This might require a separate API call or a different approach
        console.warn('File upload not implemented in this example');
    }

    fetchAPI('update', 'POST', updateData)
        .then(result => {
            console.log('Item updated:', result);
            alert('Item updated successfully!');
            // Redirect back to the main page
            window.location.href = 'Item.html';
        })
        .catch(error => {
            console.error('Error updating item:', error);
            alert('Error updating item: ' + error.message);
        });
}
