// ================== admin.js - Card-Based Design ==================

// ========== UPDATE DASHBOARD STATS ==========
function updateDashboardStats(users, reservations, orders, payments) {
  document.getElementById('total-users').textContent = users.length;
  document.getElementById('total-reservations-admin').textContent = reservations.length;
  document.getElementById('total-orders-admin').textContent = orders.length;
  
  const totalRevenue = payments.reduce((sum, payment) => {
    return sum + (parseFloat(payment.total_amount) || 0);
  }, 0);
  document.getElementById('total-revenue').textContent = `₱${totalRevenue.toFixed(2)}`;
}

// ========== RENDER USERS CARDS ==========
async function renderUsersCards() {
  try {
    const response = await fetch('http://localhost:8000/api/admin/get/allUsers.php', {
      method: 'GET',
      credentials: 'include',
    });

    const result = await response.json();
    if (result.status !== 'success') {
      console.error('Failed to load users:', result.message);
      return;
    }

    const usersGrid = document.getElementById('users-grid');
    const usersEmpty = document.getElementById('users-empty');
    const usersCount = document.getElementById('users-count');

    usersCount.textContent = result.data.length;

    if (result.data.length === 0) {
      usersGrid.classList.add('hidden');
      usersEmpty.classList.remove('hidden');
      return;
    }

    usersGrid.classList.remove('hidden');
    usersEmpty.classList.add('hidden');
    usersGrid.innerHTML = '';

    result.data.forEach((user) => {
      const card = document.createElement('div');
      card.className = 'bg-[#121212] border border-gray-700 rounded-lg p-5 hover:border-[var(--color-primary-600)] transition-all duration-300 transform hover:scale-105';
      card.innerHTML = `
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="bg-[var(--color-primary-600)] text-[#121212] w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold">
              ${user.username ? user.username.charAt(0).toUpperCase() : 'U'}
            </div>
            <div>
              <h3 class="text-lg font-bold text-[#fffeee]">${user.username || 'N/A'}</h3>
              <p class="text-sm text-gray-400">ID: ${user.user_id}</p>
            </div>
          </div>
          <button class="delete-user text-red-400 hover:text-red-600 transition-colors text-xl" data-id="${user.user_id}">
            <i class="bi bi-trash"></i>
          </button>
        </div>
        <div class="space-y-2">
          <div class="flex items-center gap-2 text-sm">
            <i class="bi bi-envelope text-[var(--color-primary-600)]"></i>
            <span class="text-gray-300">${user.email || 'N/A'}</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <i class="bi bi-calendar3 text-[var(--color-primary-600)]"></i>
            <span class="text-gray-400">Joined: ${new Date(user.created_at).toLocaleDateString()}</span>
          </div>
        </div>
      `;
      usersGrid.appendChild(card);
    });

    return result.data;
  } catch (err) {
    console.error('Error loading users:', err);
    return [];
  }
}

// ========== RENDER RESERVATIONS CARDS ==========
async function renderReservationsCards() {
  try {
    const response = await fetch('http://localhost:8000/api/admin/get/allReservations.php', {
      method: 'GET',
      credentials: 'include',
    });

    const result = await response.json();
    if (result.status !== 'success') {
      console.error('Failed to load reservations:', result.message);
      return;
    }

    const reservationsGrid = document.getElementById('reservations-grid');
    const reservationsEmpty = document.getElementById('reservations-empty');
    const reservationsCount = document.getElementById('reservations-count');

    reservationsCount.textContent = result.data.length;

    if (result.data.length === 0) {
      reservationsGrid.classList.add('hidden');
      reservationsEmpty.classList.remove('hidden');
      return;
    }

    reservationsGrid.classList.remove('hidden');
    reservationsEmpty.classList.add('hidden');
    reservationsGrid.innerHTML = '';

    result.data.forEach((reservation) => {
      const statusColors = {
        'Pending': 'bg-yellow-500',
        'Approved': 'bg-green-500',
        'Cancelled': 'bg-red-500',
        'Completed': 'bg-blue-500'
      };

      const card = document.createElement('div');
      card.className = 'bg-[#121212] border border-gray-700 rounded-lg p-5 hover:border-blue-500 transition-all duration-300';
      card.innerHTML = `
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="text-lg font-bold text-[#fffeee] mb-1">${reservation.user_name || 'N/A'}</h3>
            <p class="text-sm text-gray-400">ID: ${reservation.reservation_id}</p>
          </div>
          <button class="delete-reservation text-red-400 hover:text-red-600 transition-colors text-xl" data-id="${reservation.reservation_id}">
            <i class="bi bi-trash"></i>
          </button>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mb-4">
          <div class="flex items-center gap-2 text-sm">
            <i class="bi bi-calendar3 text-blue-500"></i>
            <span class="text-gray-300">${new Date(reservation.reservation_date).toLocaleDateString()}</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <i class="bi bi-clock text-blue-500"></i>
            <span class="text-gray-300">${reservation.reservation_time}</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <i class="bi bi-people text-blue-500"></i>
            <span class="text-gray-300">${reservation.number_of_people} people</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <i class="bi bi-calendar-check text-blue-500"></i>
            <span class="text-gray-400">${new Date(reservation.created_at).toLocaleDateString()}</span>
          </div>
        </div>

        <div class="flex items-center gap-2 mt-4">
          <select class="reservation-status flex-1 bg-[#1e1e1e] border border-gray-600 rounded px-3 py-2 text-white text-sm" data-id="${reservation.reservation_id}">
            <option value="Pending" ${reservation.status === 'Pending' ? 'selected' : ''}>Pending</option>
            <option value="Approved" ${reservation.status === 'Approved' ? 'selected' : ''}>Approved</option>
            <option value="Cancelled" ${reservation.status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
            <option value="Completed" ${reservation.status === 'Completed' ? 'selected' : ''}>Completed</option>
          </select>
          <button class="save-reservation hidden bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition-all duration-300" data-id="${reservation.reservation_id}">
            <i class="bi bi-check2-circle"></i>
          </button>
        </div>
      `;
      reservationsGrid.appendChild(card);
    });

    // Attach change listeners
    document.querySelectorAll('.reservation-status').forEach(select => {
      select.addEventListener('change', (e) => {
        const card = e.target.closest('div').parentElement;
        const saveBtn = card.querySelector('.save-reservation');
        if (saveBtn) {
          saveBtn.classList.remove('hidden');
        }
      });
    });

    return result.data;
  } catch (err) {
    console.error('Error loading reservations:', err);
    return [];
  }
}

// ========== RENDER ORDERS CARDS ==========
async function renderOrdersCards() {
  try {
    const response = await fetch('http://localhost:8000/api/admin/get/allOrders.php', {
      method: 'GET',
      credentials: 'include',
    });

    const result = await response.json();
    if (result.status !== 'success') {
      console.error('Failed to load orders:', result.message);
      return;
    }

    const ordersGrid = document.getElementById('orders-grid');
    const ordersEmpty = document.getElementById('orders-empty');
    const ordersCount = document.getElementById('orders-count');

    ordersCount.textContent = result.data.length;

    if (result.data.length === 0) {
      ordersGrid.classList.add('hidden');
      ordersEmpty.classList.remove('hidden');
      return;
    }

    ordersGrid.classList.remove('hidden');
    ordersEmpty.classList.add('hidden');
    ordersGrid.innerHTML = '';

    result.data.forEach((order) => {
      const statusColors = {
        'pending': 'bg-yellow-500',
        'verified': 'bg-green-500',
        'rejected': 'bg-red-500'
      };

      const card = document.createElement('div');
      card.className = 'bg-[#121212] border border-gray-700 rounded-lg p-5 hover:border-green-500 transition-all duration-300';
      card.innerHTML = `
        <div class="flex items-start justify-between mb-4">
          <div class="flex-1">
            <div class="flex items-center justify-between mb-2">
              <h3 class="text-lg font-bold text-[#fffeee]">${order.user_name || 'N/A'}</h3>
              <span class="text-sm px-3 py-1 rounded-full ${statusColors[order.payment_status] || 'bg-gray-500'} text-white">
                ${order.payment_status || 'N/A'}
              </span>
            </div>
            <p class="text-sm text-gray-400">Order ID: ${order.order_id} | Payment ID: ${order.payment_id || 'N/A'}</p>
          </div>
          <button class="delete-order text-red-400 hover:text-red-600 transition-colors text-xl ml-3" data-id="${order.order_id}">
            <i class="bi bi-trash"></i>
          </button>
        </div>
        
        <div class="bg-[#1e1e1e] rounded-lg p-4 mb-3">
          <div class="flex items-center justify-between mb-2">
            <span class="text-white font-semibold">${order.product_name || 'N/A'}</span>
            <span class="text-[var(--color-primary-600)] font-bold">₱${parseFloat(order.price || 0).toFixed(2)}</span>
          </div>
          <div class="flex items-center gap-4 text-sm text-gray-400">
            <span><i class="bi bi-cart3 text-green-500"></i> Qty: ${order.quantity}</span>
            ${order.customize && order.customize !== 'N/A' ? `<span><i class="bi bi-pencil text-blue-500"></i> ${order.customize}</span>` : ''}
          </div>
        </div>

        <div class="flex items-center gap-2 text-sm text-gray-400">
          <i class="bi bi-calendar3 text-green-500"></i>
          <span>${new Date(order.order_date).toLocaleString()}</span>
        </div>
      `;
      ordersGrid.appendChild(card);
    });

    return result.data;
  } catch (err) {
    console.error('Error loading orders:', err);
    return [];
  }
}

// ========== RENDER PAYMENTS CARDS ==========
async function renderPaymentsCards() {
  try {
    const response = await fetch('http://localhost:8000/api/admin/get/allPayments.php', {
      method: 'GET',
      credentials: 'include',
    });

    const result = await response.json();
    if (result.status !== 'success') {
      console.error('Failed to load payments:', result.message);
      return;
    }

    const paymentsGrid = document.getElementById('payments-grid');
    const paymentsEmpty = document.getElementById('payments-empty');
    const paymentsCount = document.getElementById('payments-count');

    paymentsCount.textContent = result.data.length;

    if (result.data.length === 0) {
      paymentsGrid.classList.add('hidden');
      paymentsEmpty.classList.remove('hidden');
      return;
    }

    paymentsGrid.classList.remove('hidden');
    paymentsEmpty.classList.add('hidden');
    paymentsGrid.innerHTML = '';

    result.data.forEach((payment) => {
      const statusColors = {
        'pending': 'bg-yellow-500',
        'verified': 'bg-green-500',
        'rejected': 'bg-red-500'
      };

      const card = document.createElement('div');
      card.className = 'bg-[#121212] border border-gray-700 rounded-lg p-5 hover:border-purple-500 transition-all duration-300';
      card.innerHTML = `
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="text-lg font-bold text-[#fffeee] mb-1">${payment.user_name || 'N/A'}</h3>
            <p class="text-sm text-gray-400">Payment ID: ${payment.payment_id}</p>
          </div>
          <button class="delete-payment text-red-400 hover:text-red-600 transition-colors text-xl" data-id="${payment.payment_id}">
            <i class="bi bi-trash"></i>
          </button>
        </div>
        
        <div class="bg-[#1e1e1e] rounded-lg p-4 mb-3">
          <div class="flex items-center justify-between mb-3">
            <span class="text-2xl font-bold text-[var(--color-primary-600)]">₱${parseFloat(payment.total_amount || 0).toFixed(2)}</span>
            <span class="text-sm px-3 py-1 rounded-full ${statusColors[payment.payment_status] || 'bg-gray-500'} text-white font-semibold">
              ${payment.payment_status || 'pending'}
            </span>
          </div>
          <div class="space-y-2 text-sm">
            <div class="flex items-center gap-2 text-gray-300">
              <i class="bi bi-credit-card text-purple-500"></i>
              <span>Ref: ${payment.reference_number || 'N/A'}</span>
            </div>
            <div class="flex items-center gap-2 text-gray-300">
              <i class="bi bi-wallet2 text-purple-500"></i>
              <span>${payment.mop || 'N/A'}</span>
            </div>
            <div class="flex items-center gap-2 text-gray-400">
              <i class="bi bi-calendar3 text-purple-500"></i>
              <span>${new Date(payment.payment_date).toLocaleString()}</span>
            </div>
          </div>
        </div>

        ${payment.screenshot_path ? `
          <button class="show-screenshot w-full bg-purple-500 hover:bg-purple-600 text-white py-2 rounded mb-3 transition-all duration-300" data-src="${payment.screenshot_path}">
            <i class="bi bi-image"></i> View Screenshot
          </button>
        ` : ''}

        <div class="flex items-center gap-2">
          <select class="payment-status flex-1 bg-[#1e1e1e] border border-gray-600 rounded px-3 py-2 text-white text-sm" data-id="${payment.payment_id}">
            <option value="pending" ${payment.payment_status === 'pending' ? 'selected' : ''}>Pending</option>
            <option value="verified" ${payment.payment_status === 'verified' ? 'selected' : ''}>Verified</option>
            <option value="rejected" ${payment.payment_status === 'rejected' ? 'selected' : ''}>Rejected</option>
          </select>
          <button class="save-status hidden bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition-all duration-300" data-id="${payment.payment_id}">
            <i class="bi bi-check2-circle"></i>
          </button>
        </div>
      `;
      paymentsGrid.appendChild(card);
    });

    // Attach change listeners
    document.querySelectorAll('.payment-status').forEach(select => {
      select.addEventListener('change', (e) => {
        const card = e.target.closest('div').parentElement;
        const saveBtn = card.querySelector('.save-status');
        if (saveBtn) {
          saveBtn.classList.remove('hidden');
        }
      });
    });

    return result.data;
  } catch (err) {
    console.error('Error loading payments:', err);
    return [];
  }
}

// ========== RENDER DELIVERIES CARDS ==========
async function renderDeliveriesCards() {
  try {
    const response = await fetch('http://localhost:8000/api/admin/get/allPayments.php', {
      method: 'GET',
      credentials: 'include',
    });

    const result = await response.json();
    if (result.status !== 'success') {
      console.error('Failed to load deliveries:', result.message);
      return;
    }

    const deliveriesGrid = document.getElementById('deliveries-grid');
    const deliveriesEmpty = document.getElementById('deliveries-empty');
    const deliveriesCount = document.getElementById('deliveries-count');

    // Filter for deliveries only
    const deliveries = result.data.filter(item => item.delivery_type === 'delivery');

    deliveriesCount.textContent = deliveries.length;

    if (deliveries.length === 0) {
      deliveriesGrid.classList.add('hidden');
      deliveriesEmpty.classList.remove('hidden');
      return;
    }

    deliveriesGrid.classList.remove('hidden');
    deliveriesEmpty.classList.add('hidden');
    deliveriesGrid.innerHTML = '';

    deliveries.forEach((delivery) => {
      const statusColors = {
        'pending': 'bg-yellow-500',
        'preparing': 'bg-blue-500',
        'out_for_delivery': 'bg-purple-500',
        'delivered': 'bg-green-500',
        'cancelled': 'bg-red-500'
      };

      const statusLabels = {
        'pending': 'Pending',
        'preparing': 'Preparing',
        'out_for_delivery': 'Out for Delivery',
        'delivered': 'Delivered',
        'cancelled': 'Cancelled'
      };

      const card = document.createElement('div');
      card.className = 'bg-[#121212] border border-gray-700 rounded-lg p-5 hover:border-orange-500 transition-all duration-300';
      card.innerHTML = `
        <div class="flex items-start justify-between mb-4">
          <div class="flex-1">
            <h3 class="text-lg font-bold text-[#fffeee] mb-1">${delivery.user_name || 'N/A'}</h3>
            <p class="text-sm text-gray-400">Payment ID: ${delivery.payment_id}</p>
          </div>
          <span class="text-sm px-3 py-1 rounded-full ${statusColors[delivery.delivery_status] || 'bg-gray-500'} text-white font-semibold ml-3">
            ${statusLabels[delivery.delivery_status] || delivery.delivery_status || 'Pending'}
          </span>
        </div>
        
        <div class="space-y-3 mb-4">
          <div class="flex items-start gap-2 text-sm">
            <i class="bi bi-geo-alt text-orange-500 mt-1"></i>
            <span class="text-gray-300">${delivery.delivery_address || 'No address provided'}</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <i class="bi bi-wallet2 text-orange-500"></i>
            <span class="text-gray-300">${delivery.mop || 'N/A'}</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <i class="bi bi-calendar3 text-orange-500"></i>
            <span class="text-gray-400">${new Date(delivery.payment_date).toLocaleString()}</span>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <select class="delivery-status flex-1 bg-[#1e1e1e] border border-gray-600 rounded px-3 py-2 text-white text-sm" data-id="${delivery.payment_id}">
            <option value="pending" ${delivery.delivery_status === 'pending' ? 'selected' : ''}>Pending</option>
            <option value="preparing" ${delivery.delivery_status === 'preparing' ? 'selected' : ''}>Preparing</option>
            <option value="out_for_delivery" ${delivery.delivery_status === 'out_for_delivery' ? 'selected' : ''}>Out for Delivery</option>
            <option value="delivered" ${delivery.delivery_status === 'delivered' ? 'selected' : ''}>Delivered</option>
            <option value="cancelled" ${delivery.delivery_status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
          </select>
          <button class="save-delivery hidden bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition-all duration-300" data-id="${delivery.payment_id}">
            <i class="bi bi-check2-circle"></i>
          </button>
        </div>
      `;
      deliveriesGrid.appendChild(card);
    });

    // Attach change listeners
    document.querySelectorAll('.delivery-status').forEach(select => {
      select.addEventListener('change', (e) => {
        const card = e.target.closest('div').parentElement;
        const saveBtn = card.querySelector('.save-delivery');
        if (saveBtn) {
          saveBtn.classList.remove('hidden');
        }
      });
    });
  } catch (err) {
    console.error('Error loading deliveries:', err);
  }
}

// ========== UPDATE RESERVATION STATUS ==========
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.save-reservation');
  if (!btn) return;

  const card = btn.closest('div').parentElement;
  const select = card.querySelector('.reservation-status');
  const reservationId = btn.getAttribute('data-id');
  const newStatus = select.value;

  try {
    const res = await fetch('http://localhost:8000/api/admin/update/updateReservationStatus.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        reservation_id: reservationId,
        status: newStatus,
      }),
    });

    const result = await res.json();

    if (result.status === 'success') {
      Swal.fire({
        title: 'Updated!',
        text: 'Reservation status saved successfully.',
        icon: 'success',
        timer: 1200,
        showConfirmButton: false,
      });
      btn.classList.add('hidden');
    } else {
      Swal.fire('Error', result.message || 'Failed to update.', 'error');
    }
  } catch (err) {
    console.error('Error updating reservation status:', err);
    Swal.fire('Error', 'A network or server error occurred.', 'error');
  }
});

// ========== UPDATE PAYMENT STATUS ==========
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.save-status');
  if (!btn) return;

  const card = btn.closest('div').parentElement;
  const select = card.querySelector('.payment-status');
  const paymentId = btn.getAttribute('data-id');
  const newStatus = select.value;

  try {
    const res = await fetch('http://localhost:8000/api/admin/update/updatePayment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        payment_id: paymentId,
        payment_status: newStatus,
      }),
    });

    const result = await res.json();

    if (result.status === 'success') {
      Swal.fire({
        title: 'Success',
        text: 'Payment status updated.',
        icon: 'success',
        timer: 1200,
        showConfirmButton: false,
      });
      btn.classList.add('hidden');
      setTimeout(() => {
        window.location.reload();
      }, 1201);
    } else {
      Swal.fire('Error', result.message || 'Failed to update.', 'error');
    }
  } catch (err) {
    console.error('Error:', err);
    Swal.fire('Error', 'Something went wrong with the server.', 'error');
  }
});

// ========== UPDATE DELIVERY STATUS ==========
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.save-delivery');
  if (!btn) return;

  const card = btn.closest('div').parentElement;
  const select = card.querySelector('.delivery-status');
  const paymentId = btn.getAttribute('data-id');
  const newStatus = select.value;

  try {
    const res = await fetch('http://localhost:8000/api/admin/update/updateDeliveryStatus.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        payment_id: paymentId,
        delivery_status: newStatus,
      }),
    });

    const result = await res.json();

    if (result.status === 'success') {
      Swal.fire({
        title: 'Success',
        text: 'Delivery status updated.',
        icon: 'success',
        timer: 1200,
        showConfirmButton: false,
      });
      btn.classList.add('hidden');
      setTimeout(() => {
        window.location.reload();
      }, 1201);
    } else {
      Swal.fire('Error', result.message || 'Failed to update.', 'error');
    }
  } catch (err) {
    console.error('Error:', err);
    Swal.fire('Error', 'Something went wrong with the server.', 'error');
  }
});

// ========== DELETE USER ==========
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.delete-user');
  if (!btn) return;

  const id = btn.dataset.id;
  const confirmDelete = await Swal.fire({
    title: 'Are you sure?',
    text: 'This will permanently delete the user!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
  });

  if (!confirmDelete.isConfirmed) return;

  try {
    const res = await fetch('http://localhost:8000/api/admin/delete/deleteUser.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ user_id: id }),
    });
    const result = await res.json();

    if (result.status === 'success') {
      Swal.fire('Deleted!', 'User has been removed.', 'success');
      renderUsersCards();
    } else {
      Swal.fire('Error', result.message || 'Failed to delete', 'error');
    }
  } catch (err) {
    console.error('Error:', err);
    Swal.fire('Error', 'Request failed', 'error');
  }
});

// ========== DELETE RESERVATION ==========
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.delete-reservation');
  if (!btn) return;

  const id = btn.dataset.id;
  const confirmDelete = await Swal.fire({
    title: 'Are you sure?',
    text: 'This will permanently delete the reservation!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
  });

  if (!confirmDelete.isConfirmed) return;

  try {
    const res = await fetch('http://localhost:8000/api/admin/delete/deleteReservation.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ reservation_id: id }),
    });
    const result = await res.json();

    if (result.status === 'success') {
      Swal.fire('Deleted!', 'Reservation has been removed.', 'success');
      renderReservationsCards();
    } else {
      Swal.fire('Error', result.message || 'Failed to delete', 'error');
    }
  } catch (err) {
    console.error('Error:', err);
    Swal.fire('Error', 'Request failed', 'error');
  }
});

// ========== DELETE ORDER ==========
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.delete-order');
  if (!btn) return;

  const id = btn.dataset.id;
  const confirmDelete = await Swal.fire({
    title: 'Are you sure?',
    text: 'This will permanently delete the order!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
  });

  if (!confirmDelete.isConfirmed) return;

  try {
    const res = await fetch('http://localhost:8000/api/admin/delete/deleteOrder.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ order_id: id }),
    });
    const result = await res.json();

    if (result.status === 'success') {
      Swal.fire('Deleted!', 'Order has been removed.', 'success');
      renderOrdersCards();
    } else {
      Swal.fire('Error', result.message || 'Failed to delete', 'error');
    }
  } catch (err) {
    console.error('Error:', err);
    Swal.fire('Error', 'Request failed', 'error');
  }
});

// ========== DELETE PAYMENT ==========
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.delete-payment');
  if (!btn) return;

  const id = btn.dataset.id;
  const confirmDelete = await Swal.fire({
    title: 'Are you sure?',
    text: 'This will permanently delete the payment!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
  });

  if (!confirmDelete.isConfirmed) return;

  try {
    const res = await fetch('http://localhost:8000/api/admin/delete/deletePayment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ payment_id: id }),
    });
    const result = await res.json();

    if (result.status === 'success') {
      Swal.fire('Deleted!', 'Payment has been removed.', 'success');
      renderPaymentsCards();
    } else {
      Swal.fire('Error', result.message || 'Failed to delete', 'error');
    }
  } catch (err) {
    console.error('Error:', err);
    Swal.fire('Error', 'Request failed', 'error');
  }
});

// ========== SHOW PAYMENT SCREENSHOT ==========
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('show-screenshot') || e.target.closest('.show-screenshot')) {
    const btn = e.target.classList.contains('show-screenshot') ? e.target : e.target.closest('.show-screenshot');
    let imgSrc = btn.getAttribute('data-src');
    if (imgSrc && !imgSrc.startsWith('http')) {
      imgSrc = `http://localhost:8000/${imgSrc}`;
    }
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    modalImg.src = imgSrc || '';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  if (e.target.id === 'closeModal' || e.target.id === 'imageModal') {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('modalImage').src = '';
  }
});

// ========== PAGE LOAD ==========
document.addEventListener('DOMContentLoaded', async () => {
  const users = await renderUsersCards();
  const reservations = await renderReservationsCards();
  const orders = await renderOrdersCards();
  const payments = await renderPaymentsCards();
  await renderDeliveriesCards();

  // Update dashboard stats
  updateDashboardStats(users || [], reservations || [], orders || [], payments || []);
});
