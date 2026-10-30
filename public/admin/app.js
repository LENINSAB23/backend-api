// GRUPO RINO E.I.R.L. | DASHBOARD DE DESPACHO & TELEMETRÍA IOT
const API_BASE = '/api';

// App State
let state = {
  orders: [],
  categories: [],
  products: [],
  drivers: [],
  activeDriverId: null,
  simInterval: null,
  map: null,
  driverMarkers: {},
  orderMarkers: {},
  telemetryLogs: []
};

// Initialize Dashboard
document.addEventListener('DOMContentLoaded', () => {
  initTabs();
  initMap();
  initModals();
  fetchInitialData();

  // Auto refresh every 4 seconds for live telemetry & order status updates
  setInterval(fetchOrdersAndTelemetry, 4000);

  document.getElementById('refresh-btn')?.addEventListener('click', () => {
    fetchInitialData();
  });
});

/* ==========================================================================
   NAVIGATION & TABS
   ========================================================================== */
function initTabs() {
  const tabs = document.querySelectorAll('.nav-tab');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

      tab.classList.add('active');
      const targetId = `tab-${tab.dataset.tab}`;
      const pane = document.getElementById(targetId);
      if (pane) pane.classList.add('active');

      if (tab.dataset.tab === 'map' && state.map) {
        setTimeout(() => state.map.invalidateSize(), 200);
      }
    });
  });
}

/* ==========================================================================
   API FETCH & DATA MANAGEMENT
   ========================================================================== */
async function fetchInitialData() {
  try {
    await Promise.all([
      fetchCategories(),
      fetchProducts(),
      fetchOrdersAndTelemetry(),
      fetchDrivers()
    ]);
    updateApiStatus(true);
  } catch (err) {
    console.error('API Error:', err);
    updateApiStatus(false);
  }
}

function updateApiStatus(isConnected) {
  const badge = document.getElementById('api-status-badge');
  const text = document.getElementById('api-status-text');
  if (isConnected && badge && text) {
    badge.className = 'status-badge connected';
    text.textContent = 'Backend API Conectado';
  } else if (badge && text) {
    badge.className = 'status-badge disconnected';
    text.textContent = 'Sin Conexión con API';
  }
}

async function fetchCategories() {
  const res = await fetch(`${API_BASE}/categories`);
  const data = await res.json();
  if (data.status === 'success') {
    state.categories = data.data;
    populateCategorySelects();
  }
}

async function fetchProducts() {
  const res = await fetch(`${API_BASE}/products`);
  const data = await res.json();
  if (data.status === 'success') {
    state.products = data.data;
    renderCatalogGrid();
  }
}

async function fetchOrdersAndTelemetry() {
  const res = await fetch(`${API_BASE}/orders`);
  const data = await res.json();
  if (data.status === 'success') {
    state.orders = data.data;
    renderKanban();
    updateMetrics();
    updateMapMarkers();
  }
}

async function fetchDrivers() {
  const simSelect = document.getElementById('sim-driver-id');
  const assignSelect = document.getElementById('assign-driver-select');
  const mapDriverSelect = document.getElementById('map-driver-select');

  state.drivers = [
    { id: 2, name: 'Juan Carlos Perez (Repartidor 01)' },
    { id: 3, name: 'Carlos Mendoza (Repartidor 02)' }
  ];

  let options = state.drivers.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
  
  if (simSelect) simSelect.innerHTML = options;
  if (assignSelect) assignSelect.innerHTML = options;
  if (mapDriverSelect) {
    mapDriverSelect.innerHTML = options;
    state.activeDriverId = state.drivers[0].id;
  }
}

/* ==========================================================================
   METRICS & KANBAN BOARD
   ========================================================================== */
function updateMetrics() {
  const pending = state.orders.filter(o => o.status === 'pending');
  const cooking = state.orders.filter(o => o.status === 'cooking');
  const transit = state.orders.filter(o => o.status === 'in_transit');
  const delivered = state.orders.filter(o => o.status === 'delivered');

  const elPending = document.getElementById('metric-pending');
  const elCooking = document.getElementById('metric-cooking');
  const elTransit = document.getElementById('metric-transit');
  const elDelivered = document.getElementById('metric-delivered');
  const elRevenue = document.getElementById('metric-revenue');

  if (elPending) elPending.textContent = pending.length;
  if (elCooking) elCooking.textContent = cooking.length;
  if (elTransit) elTransit.textContent = transit.length;
  if (elDelivered) elDelivered.textContent = delivered.length;

  const totalRev = delivered.reduce((acc, o) => acc + parseFloat(o.total || 0), 0);
  if (elRevenue) elRevenue.textContent = `S/ ${totalRev.toFixed(2)}`;

  if (document.getElementById('count-pending')) document.getElementById('count-pending').textContent = pending.length;
  if (document.getElementById('count-cooking')) document.getElementById('count-cooking').textContent = cooking.length;
  if (document.getElementById('count-transit')) document.getElementById('count-transit').textContent = transit.length;
  if (document.getElementById('count-delivered')) document.getElementById('count-delivered').textContent = delivered.length;

  const simOrderSelect = document.getElementById('sim-order-id');
  if (simOrderSelect) {
    const activeOrders = state.orders.filter(o => o.status === 'in_transit' || o.status === 'cooking');
    simOrderSelect.innerHTML = activeOrders.map(o => `<option value="${o.id}">${o.order_number} - ${o.customer_name}</option>`).join('') || '<option value="">Sin ordenes activas en ruta</option>';
  }
}

function renderKanban() {
  const cols = {
    pending: document.getElementById('cards-pending'),
    cooking: document.getElementById('cards-cooking'),
    in_transit: document.getElementById('cards-transit'),
    delivered: document.getElementById('cards-delivered')
  };

  Object.values(cols).forEach(c => { if (c) c.innerHTML = ''; });

  state.orders.forEach(order => {
    const card = createOrderCard(order);
    if (cols[order.status]) {
      cols[order.status].appendChild(card);
    }
  });
}

function createOrderCard(order) {
  const card = document.createElement('div');
  card.className = 'order-card';
  
  const createdTime = new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  const itemsText = order.items ? order.items.map(i => `${i.quantity}x ${i.product_name}`).join(', ') : 'Cargando productos...';

  let actionButtons = '';
  if (order.status === 'pending') {
    actionButtons = `<button class="btn btn-warning btn-sm" onclick="event.stopPropagation(); updateOrderStatus(${order.id}, 'cooking')"><i class="fa-solid fa-wrench"></i> Preparar Repuestos</button>`;
  } else if (order.status === 'cooking') {
    actionButtons = `<button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); openAssignDriverModal(${order.id}, '${order.order_number}')"><i class="fa-solid fa-motorcycle"></i> Asignar Repartidor</button>`;
  } else if (order.status === 'in_transit') {
    actionButtons = `<button class="btn btn-success btn-sm" onclick="event.stopPropagation(); updateOrderStatus(${order.id}, 'delivered')"><i class="fa-solid fa-circle-check"></i> Marcar Entregado</button>`;
  }

  card.innerHTML = `
    <div class="card-top">
      <span class="order-num">${order.order_number}</span>
      <span class="order-time">${createdTime}</span>
    </div>
    <div class="customer-info">
      <div class="customer-name">${order.customer_name}</div>
      <div class="customer-address"><i class="fa-solid fa-location-dot"></i> ${order.delivery_address}</div>
    </div>
    <div class="order-items-summary">${itemsText}</div>
    <div class="card-bottom">
      <span class="order-total">S/ ${parseFloat(order.total).toFixed(2)}</span>
      <div class="card-actions">${actionButtons}</div>
    </div>
  `;

  card.addEventListener('click', () => openOrderDetailModal(order.id));
  return card;
}

window.updateOrderStatus = async function(orderId, newStatus) {
  try {
    const res = await fetch(`${API_BASE}/orders/${orderId}/status`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: newStatus })
    });
    const data = await res.json();
    if (data.status === 'success') {
      fetchOrdersAndTelemetry();
    }
  } catch (err) {
    alert('Error al actualizar estado');
  }
};

window.openAssignDriverModal = function(orderId, orderNum) {
  document.getElementById('assign-order-id').value = orderId;
  document.getElementById('assign-order-number').textContent = orderNum;
  document.getElementById('modal-assign-driver').classList.add('active');
};

/* ==========================================================================
   LEAFLET LIVE MAP (HUÁNUCO)
   ========================================================================== */
function initMap() {
  const huanucoCoords = [-9.9306, -76.2422];
  
  if (!document.getElementById('live-map')) return;

  state.map = L.map('live-map').setView(huanucoCoords, 14);

  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap &copy; CARTO',
    maxZoom: 19
  }).addTo(state.map);
}

function updateMapMarkers() {
  if (!state.map) return;

  state.orders.forEach(order => {
    if (order.status === 'in_transit' || order.status === 'cooking') {
      const lat = parseFloat(order.delivery_latitude || -9.9306);
      const lng = parseFloat(order.delivery_longitude || -76.2422);

      if (!state.orderMarkers[order.id]) {
        const orderIcon = L.divIcon({
          className: 'custom-order-icon',
          html: `<div style="background:#ef4444; color:white; padding:4px 8px; border-radius:12px; font-weight:bold; font-size:11px; box-shadow:0 0 10px rgba(239,68,68,0.6);"><i class="fa-solid fa-house"></i> ${order.order_number}</div>`,
          iconSize: [80, 24]
        });

        const marker = L.marker([lat, lng], { icon: orderIcon })
          .bindPopup(`<b>${order.order_number}</b><br>${order.customer_name}<br>${order.delivery_address}`)
          .addTo(state.map);

        state.orderMarkers[order.id] = marker;
      }
    } else if (state.orderMarkers[order.id]) {
      state.map.removeLayer(state.orderMarkers[order.id]);
      delete state.orderMarkers[order.id];
    }
  });

  state.orders.forEach(order => {
    if (order.status === 'in_transit' && order.latest_telemetry) {
      const t = order.latest_telemetry;
      const driverId = order.driver_id || 2;
      const lat = parseFloat(t.latitude);
      const lng = parseFloat(t.longitude);

      if (!state.driverMarkers[driverId]) {
        const driverIcon = L.divIcon({
          className: 'custom-driver-icon',
          html: `<div style="background:#3b82f6; color:white; padding:6px; border-radius:50%; border:3px solid white; box-shadow:0 0 15px rgba(59,130,246,0.8); text-align:center;"><i class="fa-solid fa-motorcycle"></i></div>`,
          iconSize: [36, 36]
        });

        const marker = L.marker([lat, lng], { icon: driverIcon })
          .bindPopup(`<b>Repartidor IoT</b><br>Velocidad: ${t.speed || 0} km/h<br>Batería: ${t.battery_level || '--'}%`)
          .addTo(state.map);

        state.driverMarkers[driverId] = marker;
      } else {
        state.driverMarkers[driverId].setLatLng([lat, lng]);
      }

      if (driverId === state.activeDriverId || !state.activeDriverId) {
        if (document.getElementById('t-lat')) document.getElementById('t-lat').textContent = lat.toFixed(5);
        if (document.getElementById('t-lng')) document.getElementById('t-lng').textContent = lng.toFixed(5);
        if (document.getElementById('t-speed')) document.getElementById('t-speed').textContent = `${t.speed || 0} km/h`;
        if (document.getElementById('t-battery')) document.getElementById('t-battery').textContent = `${t.battery_level || '--'}%`;
      }
    }
  });
}

/* ==========================================================================
   CATALOG MANAGEMENT
   ========================================================================== */
function renderCatalogGrid() {
  const grid = document.getElementById('catalog-products-grid');
  if (!grid) return;

  grid.innerHTML = state.products.map(p => `
    <div class="product-card">
      <img src="${p.image_url || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500'}" class="product-img" alt="${p.name}" />
      <div class="product-body">
        <h4 class="product-title">${p.name}</h4>
        <p class="product-desc">${p.description || 'Sin descripción'}</p>
        <div class="product-footer">
          <span class="product-price">S/ ${parseFloat(p.price).toFixed(2)}</span>
          <span class="badge" style="font-size:11px; background:rgba(59,130,246,0.2); color:#60a5fa; padding:4px 8px; border-radius:6px;">⏱️ ${p.prep_time_minutes} min</span>
        </div>
      </div>
    </div>
  `).join('');
}

function populateCategorySelects() {
  const catSelect = document.getElementById('prod-category');
  if (catSelect) {
    catSelect.innerHTML = state.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
  }
}

/* ==========================================================================
   IOT TELEMETRY SIMULATOR
   ========================================================================== */
let simRouteIndex = 0;
// Simulated GPS route points through streets of Huánuco (Circuito vial que respeta calles y esquinas)
const huanucoSimRoute = [
  { lat: -9.9306, lng: -76.2422 }, // Plaza de Armas / 28 de Julio
  { lat: -9.9312, lng: -76.2418 }, // Jr. General Prado cdra 3
  { lat: -9.9318, lng: -76.2412 }, // Jr. General Prado & Dámaso Beraún
  { lat: -9.9325, lng: -76.2405 }, // Jr. General Prado & Aguilar
  { lat: -9.9332, lng: -76.2398 }, // Jr. General Prado & Ayacucho
  { lat: -9.9324, lng: -76.2389 }, // Giro en Jr. Ayacucho hacia Dos de Mayo
  { lat: -9.9316, lng: -76.2396 }, // Jr. Dos de Mayo & Aguilar
  { lat: -9.9307, lng: -76.2404 }, // Jr. Dos de Mayo & Dámaso Beraún
  { lat: -9.9298, lng: -76.2413 }  // Jr. Dos de Mayo & 28 de Julio (Catedral)
];

document.getElementById('btn-start-sim')?.addEventListener('click', () => {
  const driverId = document.getElementById('sim-driver-id').value;
  const orderId = document.getElementById('sim-order-id').value;
  const speed = document.getElementById('sim-speed').value;
  const battery = document.getElementById('sim-battery').value;

  if (!driverId) {
    alert('Por favor selecciona un repartidor');
    return;
  }

  document.getElementById('btn-start-sim').disabled = true;
  document.getElementById('btn-stop-sim').disabled = false;
  document.getElementById('sim-status-box').innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> SIMULACIÓN ACTIVA: Emitiendo paquetes GPS satelitales cada 3 segundos...`;

  state.simInterval = setInterval(() => {
    const point = huanucoSimRoute[simRouteIndex % huanucoSimRoute.length];
    simRouteIndex++;

    sendTelemetryPacket({
      driver_id: parseInt(driverId),
      order_id: orderId ? parseInt(orderId) : null,
      latitude: point.lat,
      longitude: point.lng,
      altitude: 1910.0 + Math.random() * 5,
      speed: parseFloat(speed) + Math.floor(Math.random() * 6 - 3),
      battery_level: parseInt(battery)
    });

  }, 3000);
});

document.getElementById('btn-stop-sim')?.addEventListener('click', () => {
  clearInterval(state.simInterval);
  state.simInterval = null;
  document.getElementById('btn-start-sim').disabled = false;
  document.getElementById('btn-stop-sim').disabled = true;
  document.getElementById('sim-status-box').innerHTML = `<i class="fa-solid fa-circle-stop"></i> Simulación Detenida.`;
});

document.getElementById('btn-send-single-telemetry')?.addEventListener('click', () => {
  const driverId = document.getElementById('sim-driver-id').value;
  const orderId = document.getElementById('sim-order-id').value;
  const point = huanucoSimRoute[Math.floor(Math.random() * huanucoSimRoute.length)];

  sendTelemetryPacket({
    driver_id: parseInt(driverId),
    order_id: orderId ? parseInt(orderId) : null,
    latitude: point.lat,
    longitude: point.lng,
    altitude: 1912.0,
    speed: 32.5,
    battery_level: 90
  });
});

async function sendTelemetryPacket(payload) {
  try {
    const res = await fetch(`${API_BASE}/telemetry/location`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.status === 'success') {
      const logList = document.getElementById('telemetry-log-list');
      const timeStr = new Date().toLocaleTimeString();
      const logItem = document.createElement('li');
      logItem.className = 'log-item';
      logItem.innerHTML = `[${timeStr}] GPS Tx: Lat ${payload.latitude.toFixed(4)}, Lng ${payload.longitude.toFixed(4)} | ${payload.speed}km/h`;
      
      if (logList.querySelector('.empty')) logList.innerHTML = '';
      logList.prepend(logItem);

      fetchOrdersAndTelemetry();
    }
  } catch (err) {
    console.error('Error enviando telemetría:', err);
  }
}

/* ==========================================================================
   MODAL CONTROLS & EVENT LISTENERS
   ========================================================================== */
function initModals() {
  document.querySelectorAll('[data-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modalId = btn.dataset.close;
      document.getElementById(modalId)?.classList.remove('active');
    });
  });

  document.getElementById('btn-confirm-assign')?.addEventListener('click', async () => {
    const orderId = document.getElementById('assign-order-id').value;
    const driverId = document.getElementById('assign-driver-select').value;

    try {
      const res = await fetch(`${API_BASE}/orders/${orderId}/assign-driver`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ driver_id: parseInt(driverId) })
      });
      const data = await res.json();
      if (data.status === 'success') {
        document.getElementById('modal-assign-driver').classList.remove('active');
        fetchOrdersAndTelemetry();
      }
    } catch (err) {
      alert('Error al asignar repartidor');
    }
  });

  document.getElementById('btn-new-product-modal')?.addEventListener('click', () => {
    document.getElementById('modal-new-product').classList.add('active');
  });

  document.getElementById('form-new-product')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
      category_id: parseInt(document.getElementById('prod-category').value),
      name: document.getElementById('prod-name').value,
      description: document.getElementById('prod-description').value,
      price: parseFloat(document.getElementById('prod-price').value),
      prep_time_minutes: parseInt(document.getElementById('prod-prep').value),
      image_url: document.getElementById('prod-image').value || null
    };

    try {
      const res = await fetch(`${API_BASE}/products`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.status === 'success') {
        document.getElementById('modal-new-product').classList.remove('active');
        fetchProducts();
      }
    } catch (err) {
      alert('Error al crear producto');
    }
  });
}

async function openOrderDetailModal(orderId) {
  try {
    const res = await fetch(`${API_BASE}/orders/${orderId}`);
    const data = await res.json();
    if (data.status === 'success') {
      const order = data.data;
      document.getElementById('detail-order-number').textContent = order.order_number;

      const body = document.getElementById('detail-modal-body');
      body.innerHTML = `
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
          <div>
            <p><strong>Cliente:</strong> ${order.customer_name}</p>
            <p><strong>Teléfono:</strong> ${order.customer_phone}</p>
            <p><strong>Dirección:</strong> ${order.delivery_address}</p>
          </div>
          <div>
            <p><strong>Estado:</strong> <span style="text-transform:uppercase; font-weight:bold; color:#3b82f6;">${order.status}</span></p>
            <p><strong>Método de Pago:</strong> ${order.payment_method} (${order.payment_status})</p>
            <p><strong>Repartidor:</strong> ${order.driver ? order.driver.name : 'Sin asignar'}</p>
          </div>
        </div>
        <h4 style="margin-bottom:10px;">Items del Pedido:</h4>
        <table style="width:100%; border-collapse:collapse; background:rgba(15,23,42,0.5); border-radius:8px; overflow:hidden;">
          <thead>
            <tr style="background:rgba(255,255,255,0.05); text-align:left;">
              <th style="padding:10px;">Producto</th>
              <th style="padding:10px;">Cant.</th>
              <th style="padding:10px;">P. Unit</th>
              <th style="padding:10px;">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            ${order.items.map(i => `
              <tr style="border-top:1px solid rgba(255,255,255,0.05);">
                <td style="padding:10px;">${i.product_name}</td>
                <td style="padding:10px;">${i.quantity}</td>
                <td style="padding:10px;">S/ ${parseFloat(i.unit_price).toFixed(2)}</td>
                <td style="padding:10px;">S/ ${parseFloat(i.subtotal).toFixed(2)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        <div style="text-align:right; margin-top:16px; font-size:16px;">
          <p>Subtotal: S/ ${parseFloat(order.subtotal).toFixed(2)}</p>
          <p>Delivery: S/ ${parseFloat(order.delivery_fee).toFixed(2)}</p>
          <p style="font-size:20px; font-weight:bold; color:#34d399;">Total: S/ ${parseFloat(order.total).toFixed(2)}</p>
        </div>
      `;

      document.getElementById('modal-order-detail').classList.add('active');
    }
  } catch (err) {
    alert('Error al obtener detalle del pedido');
  }
}
