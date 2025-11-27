// Menu Management Functions
document.addEventListener('DOMContentLoaded', function() {
    loadMenuItems();
});

// Load menu items
async function loadMenuItems() {
    try {
        const response = await fetch('../../api/admin/menu/getMenu.php');
        const data = await response.json();
        
        if (data.status === 'success') {
            renderMenuItems(data.items);
            updateMenuCount(data.items.length);
        } else {
            showAlert('error', 'Error', 'Failed to load menu items');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', 'There was a problem loading menu items');
    }
}

// Render menu items in grid
function renderMenuItems(items) {
    const menuGrid = document.getElementById('menu-items-grid');
    const emptyMessage = document.getElementById('menu-empty-message');
    
    if (!items || items.length === 0) {
        menuGrid.innerHTML = '';
        emptyMessage.classList.remove('hidden');
        return;
    }
    
    emptyMessage.classList.add('hidden');
    menuGrid.innerHTML = items.map((item, index) => `
        <div class="bg-[#2a2a2a] rounded-lg p-4 border border-gray-700 hover:border-[var(--color-primary-600)] transition-colors duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="flex gap-4 items-start">
                    <div class="w-20 h-20 overflow-hidden rounded-md bg-gray-700 flex-shrink-0">
                        <img src="${item.image || '/public/images/menu/default-menu.jpg'}" alt="${item.name}" class="w-full h-full object-cover" />
                    </div>
                    <div>
                        <h3 class="text-[#fffeee] font-semibold text-lg">${item.name}</h3>
                        <p class="text-[var(--color-primary-600)] font-bold">₱${parseFloat(item.price).toFixed(2)}</p>
                        <p class="text-gray-400 text-sm mt-1">Category: ${item.category}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="editMenuItem(${item.id})" class="text-blue-500 hover:text-blue-400">
                        <i class="bi bi-pencil-square text-xl"></i>
                    </button>
                    <button onclick="deleteMenuItem(${item.id})" class="text-red-500 hover:text-red-400">
                        <i class="bi bi-trash text-xl"></i>
                    </button>
                </div>
            </div>
            <p class="text-gray-400 text-sm mb-2">${item.description || 'No description'}</p>
        </div>
    `).join('');
}

// Update menu count
function updateMenuCount(count) {
    document.getElementById('menu-count').textContent = count;
}

// Add new menu item
async function addMenuItem() {
    const form = document.getElementById('menu-form');
    const formData = new FormData(form);
    
    // Validate form data
    const name = formData.get('name');
    const price = formData.get('price');
    const category = formData.get('category');
    
    if (!name || name.trim() === '') {
        showAlert('error', 'Error', 'Please enter an item name');
        return;
    }
    
    if (!price || price <= 0) {
        showAlert('error', 'Error', 'Please enter a valid price');
        return;
    }
    
    if (!category || category.trim() === '') {
        showAlert('error', 'Error', 'Please select a category');
        return;
    }
    

        // Use FormData directly so files are uploaded correctly
        console.log('Sending formData keys:');
        for (let pair of formData.entries()) console.log(pair[0], pair[1]);

        const response = await fetch('../../api/admin/menu/addMenu.php', {
            method: 'POST',
            body: formData
            // NOTE: Do not set Content-Type; browser will set multipart/form-data boundary
        });

        // Read raw text first (sa ilang setups, PHP may output warnings/html which breaks JSON.parse)
        const raw = await response.text();
        let result = null;

        try {
            result = raw ? JSON.parse(raw) : null;
        } catch (parseError) {
            console.warn('Could not parse JSON response from addMenu.php:', parseError, 'raw:', raw);
            result = null;
        }

        // If server returned non-ok, prefer message from parsed JSON when available
        if (!response.ok) {
            const errMsg = (result && result.message) ? result.message : raw;
            console.error('Server returned non-OK response:', response.status, errMsg);
            throw new Error(`HTTP error! status: ${response.status} - server: ${String(errMsg).substring(0,300)}`);
        }

        console.log('Server response (parsed):', result, 'raw:', raw);

        // If parsed JSON indicates success, handle normally
        if (result && result.status === 'success') {
            console.log('addMenuItem: success branch (parsed JSON)');
            // Close modal first so the success toast is visible above the UI
            closeMenuModal();
            try {
                console.log('addMenuItem: calling showAlert(success)');
                showAlert('success', 'Success', 'Menu item added successfully');
                console.log('addMenuItem: showAlert returned');
            } catch (e) {
                console.error('addMenuItem: showAlert threw:', e);
            }
            await loadMenuItems();
        } else if (response.ok && !result) {
            // Server returned 200 but non-JSON body (common when PHP prints warnings or echoes).
            // Assume success (because backend actually added the item) to avoid false error popups.
            console.warn('Server returned 200 but non-JSON body. Assuming success. raw:', raw);
            // Close first so the toast isn't hidden
            closeMenuModal();
            try {
                console.log('addMenuItem: calling showAlert(success) [non-JSON branch]');
                showAlert('success', 'Success', 'Menu item added successfully');
                console.log('addMenuItem: showAlert returned [non-JSON branch]');
            } catch (e) {
                console.error('addMenuItem: showAlert threw [non-JSON branch]:', e);
            }
            await loadMenuItems();
        } else {
            const msg = (result && result.message) ? result.message : 'Failed to add menu item';
            throw new Error(msg);
        }

 
}

// Edit menu item
async function editMenuItem(id) {
    const menuModal = document.getElementById('menu-modal');
    const form = document.getElementById('menu-form');
    const modalTitle = document.getElementById('modal-title');
    
    try {
        const response = await fetch(`../../api/admin/menu/getMenu.php?id=${id}`);
        const data = await response.json();
        
        if (data.status === 'success' && data.item) {
            const item = data.item;
            form.name.value = item.name;
            form.price.value = item.price;
            form.description.value = item.description || '';
            form.category.value = item.category;
            form.dataset.itemId = item.id;
            
            modalTitle.textContent = 'Edit Menu Item';
            menuModal.classList.remove('hidden');
        } else {
            showAlert('error', 'Error', 'Failed to load menu item');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', 'Error loading menu item');
    }
}

// Update menu item
async function updateMenuItem(id) {
    const form = document.getElementById('menu-form');
    const formData = new FormData(form);
    
    try {
        const response = await fetch(`../../api/admin/menu/updateMenu.php?id=${id}`, {
            method: 'POST',
            body: JSON.stringify(Object.fromEntries(formData)),
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            showAlert('success', 'Success', 'Menu item updated successfully');
            loadMenuItems();
            closeMenuModal();
        } else {
            showAlert('error', 'Error', result.message || 'Failed to update menu item');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', 'Error updating menu item');
    }
}

// Delete menu item
async function deleteMenuItem(id) {
    const confirmed = await Swal.fire({
        title: 'Are you sure?',
        text: "This item will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });
    
    if (confirmed.isConfirmed) {
        try {
            const response = await fetch(`../../api/admin/menu/deleteMenu.php?id=${id}`, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                showAlert('success', 'Success', 'Menu item deleted successfully');
                loadMenuItems();
            } else {
                showAlert('error', 'Error', result.message || 'Failed to delete menu item');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('error', 'Error', 'Error deleting menu item');
        }
    }
}

// Open menu modal
function openMenuModal() {
    const menuModal = document.getElementById('menu-modal');
    const form = document.getElementById('menu-form');
    const modalTitle = document.getElementById('modal-title');
    
    form.reset();
    delete form.dataset.itemId;
    modalTitle.textContent = 'Add Menu Item';
    menuModal.classList.remove('hidden');
}

// Close menu modal
function closeMenuModal() {
    const menuModal = document.getElementById('menu-modal');
    const form = document.getElementById('menu-form');
    
    form.reset();
    delete form.dataset.itemId;
    menuModal.classList.add('hidden');
}

// Hide modal only (do not reset form or delete dataset.itemId)
function hideMenuModalOnly() {
    const menuModal = document.getElementById('menu-modal');
    if (menuModal) menuModal.classList.add('hidden');
}

// Handle form submission
document.getElementById('menu-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const itemId = this.dataset.itemId;

    // Hide modal immediately on Save click, but keep form data intact
    hideMenuModalOnly();

    if (itemId) {
        updateMenuItem(itemId);
    } else {
        addMenuItem();
    }
});

// Show alert message
function showAlert(icon, title, text) {
    Swal.fire({
        icon: icon,
        title: title,
        text: text,
        timer: 2000,
        showConfirmButton: false
    });
}