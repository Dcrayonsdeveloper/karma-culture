'use strict';

const log = require('electron-log');

/**
 * Enumerate connected USB printers.
 * Returns an array of { vendorId, productId, name }.
 */
async function discoverUSBPrinters() {
  try {
    const USB = require('escpos-usb');
    const devices = USB.findPrinter();

    return devices.map((device) => ({
      vendorId: device.deviceDescriptor?.idVendor,
      productId: device.deviceDescriptor?.idProduct,
      name: formatPrinterName(device.deviceDescriptor?.idVendor, device.deviceDescriptor?.idProduct),
    }));
  } catch (err) {
    log.warn('USB printer discovery failed:', err.message);
    return [];
  }
}

/**
 * Map known vendor/product IDs to human-readable names.
 */
function formatPrinterName(vendorId, productId) {
  const knownVendors = {
    0x04B8: 'Epson',
    0x0519: 'Star Micronics',
    0x0DD4: 'Bixolon',
    0x20D1: 'TVS',
    0x0483: 'STMicroelectronics',
    0x1FC9: 'NXP',
  };

  const vendor = knownVendors[vendorId] || 'Unknown';
  return `${vendor} (${hex(vendorId)}:${hex(productId)})`;
}

function hex(n) {
  return n ? `0x${n.toString(16).toUpperCase().padStart(4, '0')}` : '????';
}

module.exports = { discoverUSBPrinters };
