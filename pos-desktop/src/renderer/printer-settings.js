'use strict';

const $ = (sel) => document.querySelector(sel);
const show = (el) => el.classList.remove('hidden');
const hide = (el) => el.classList.add('hidden');

const typeSelect   = $('#printerType');
const usbSection   = $('#usb-section');
const networkSection = $('#network-section');
const ipInput      = $('#printerIp');
const portInput    = $('#printerPort');
const widthSelect  = $('#printerWidth');
const btnScan      = $('#btn-scan');
const btnTest      = $('#btn-test');
const btnSave      = $('#btn-save');
const statusEl     = $('#status');

// Load current settings on page open
window.addEventListener('DOMContentLoaded', async () => {
  try {
    const settings = await window.posDesktop.getSettings();
    typeSelect.value  = settings.printerType  || 'none';
    ipInput.value     = settings.printerIp    || '';
    portInput.value   = settings.printerPort  || 9100;
    widthSelect.value = settings.printerWidth || 48;
    updateVisibility();
  } catch (e) {
    showStatus('Failed to load settings', 'error');
  }
});

// Toggle sections based on printer type
typeSelect.addEventListener('change', updateVisibility);

function updateVisibility() {
  const type = typeSelect.value;
  hide(usbSection);
  hide(networkSection);

  if (type === 'usb') show(usbSection);
  if (type === 'network') show(networkSection);

  btnTest.disabled = type === 'none';
}

// Scan for USB printers
btnScan.addEventListener('click', async () => {
  btnScan.disabled = true;
  btnScan.textContent = 'Scanning...';
  const listEl = $('#usb-list');

  try {
    const printers = await window.posDesktop.discoverPrinters();
    if (printers.length === 0) {
      listEl.innerHTML = '<p class="muted">No USB printers found. Make sure the printer is connected and powered on.</p>';
    } else {
      listEl.innerHTML = printers.map((p) =>
        `<div class="device-item">
          <span class="device-name">${escapeHtml(p.name)}</span>
          <span class="device-id">${p.vendorId}:${p.productId}</span>
        </div>`
      ).join('');
    }
  } catch (e) {
    listEl.innerHTML = '<p class="error">Scan failed. USB access may require driver installation.</p>';
  }

  btnScan.disabled = false;
  btnScan.textContent = 'Scan for USB Printers';
});

// Test print
btnTest.addEventListener('click', async () => {
  btnTest.disabled = true;
  btnTest.textContent = 'Printing...';
  showStatus('Sending test print...', 'info');

  // Save current settings first so test uses the right config
  await saveSettings();

  try {
    const result = await window.posDesktop.testPrint();
    if (result.success) {
      showStatus('Test print successful! Check your printer.', 'success');
    } else {
      showStatus(`Test print failed: ${escapeHtml(result.error)}`, 'error');
    }
  } catch (e) {
    showStatus(`Test print error: ${escapeHtml(e.message || 'Unknown error')}`, 'error');
  }

  btnTest.disabled = false;
  btnTest.textContent = 'Test Print';
});

// Save settings
btnSave.addEventListener('click', async () => {
  const saved = await saveSettings();
  if (saved) showStatus('Settings saved successfully!', 'success');
});

async function saveSettings() {
  const settings = {
    printerType:  typeSelect.value,
    printerIp:    ipInput.value.trim(),
    printerPort:  parseInt(portInput.value) || 9100,
    printerWidth: parseInt(widthSelect.value) || 48,
  };

  // Validate network settings
  if (settings.printerType === 'network' && !settings.printerIp) {
    showStatus('Please enter the printer IP address.', 'error');
    ipInput.focus();
    return false;
  }

  try {
    const result = await window.posDesktop.saveSettings(settings);
    return result.success;
  } catch (e) {
    showStatus('Failed to save settings.', 'error');
    return false;
  }
}

function showStatus(message, type) {
  statusEl.textContent = message;
  statusEl.className = `status ${type}`;
  show(statusEl);
  if (type === 'success') {
    setTimeout(() => hide(statusEl), 3000);
  }
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}
