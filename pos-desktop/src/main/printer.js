'use strict';

const log = require('electron-log');

/**
 * ESC/POS Thermal Printer Module
 *
 * Supports:
 *  - USB thermal printers (via escpos-usb)
 *  - Network thermal printers (via escpos-network, TCP port 9100)
 *  - 80mm paper width (standard POS receipt)
 *
 * All printer operations are wrapped in try/catch with timeouts
 * to prevent the app from hanging if a printer is offline.
 */

const PRINT_TIMEOUT_MS = 15000; // 15 second timeout per print job

function setupPrinterIPC(ipcMain, store) {

  // --- Print a full receipt ---
  ipcMain.handle('printer:print-receipt', async (_event, receiptData) => {
    const printerType = store.get('printerType', 'none');

    if (printerType === 'none') {
      return { success: false, error: 'No printer configured. Open Printer Settings from the system tray.' };
    }

    if (!receiptData || !receiptData.sale_number) {
      return { success: false, error: 'Invalid receipt data' };
    }

    try {
      return await withTimeout(printReceipt(printerType, store, receiptData), PRINT_TIMEOUT_MS);
    } catch (err) {
      log.error('Print failed:', err.message);
      return { success: false, error: err.message };
    }
  });

  // --- Discover USB printers ---
  ipcMain.handle('printer:discover-usb', async () => {
    try {
      const USB = require('escpos-usb');
      const devices = USB.findPrinter();
      return devices.map((d) => ({
        vendorId: d.deviceDescriptor?.idVendor,
        productId: d.deviceDescriptor?.idProduct,
        name: `USB Printer (${d.deviceDescriptor?.idVendor}:${d.deviceDescriptor?.idProduct})`,
      }));
    } catch (err) {
      log.warn('USB printer discovery failed:', err.message);
      return [];
    }
  });

  // --- Test print ---
  ipcMain.handle('printer:test', async () => {
    const printerType = store.get('printerType', 'none');
    if (printerType === 'none') {
      return { success: false, error: 'No printer configured' };
    }

    try {
      return await withTimeout(testPrint(printerType, store), PRINT_TIMEOUT_MS);
    } catch (err) {
      log.error('Test print failed:', err.message);
      return { success: false, error: err.message };
    }
  });

  // --- Printer status ---
  ipcMain.handle('printer:status', async () => {
    const printerType = store.get('printerType', 'none');
    return {
      configured: printerType !== 'none',
      type: printerType,
      ip: printerType === 'network' ? store.get('printerIp') : null,
      port: printerType === 'network' ? store.get('printerPort') : null,
    };
  });

  // --- Settings ---
  ipcMain.handle('settings:get', () => {
    return {
      printerType:  store.get('printerType'),
      printerIp:    store.get('printerIp'),
      printerPort:  store.get('printerPort'),
      printerWidth: store.get('printerWidth'),
      kioskMode:    store.get('kioskMode'),
      autoStart:    store.get('autoStart'),
    };
  });

  ipcMain.handle('settings:save', (_event, settings) => {
    if (!settings || typeof settings !== 'object') return { success: false };
    const allowedKeys = ['printerType', 'printerIp', 'printerPort', 'printerWidth', 'kioskMode', 'autoStart'];
    for (const key of allowedKeys) {
      if (key in settings) {
        // Sanitize values
        if (key === 'printerType' && !['usb', 'network', 'none'].includes(settings[key])) continue;
        if (key === 'printerPort') settings[key] = Math.max(1, Math.min(65535, parseInt(settings[key]) || 9100));
        if (key === 'printerIp') settings[key] = String(settings[key]).substring(0, 45);
        if (key === 'printerWidth') settings[key] = [32, 40, 48, 56].includes(parseInt(settings[key])) ? parseInt(settings[key]) : 48;
        store.set(key, settings[key]);
      }
    }
    return { success: true };
  });
}

/**
 * Open a printer device (USB or network) and return { device, escpos, Printer }
 */
function openDevice(printerType, store) {
  const escpos = require('escpos');

  if (printerType === 'usb') {
    const USB = require('escpos-usb');
    const device = new USB();
    return { device, escpos, Printer: escpos.Printer };
  }

  if (printerType === 'network') {
    const Network = require('escpos-network');
    const ip = store.get('printerIp');
    const port = store.get('printerPort', 9100);
    if (!ip) throw new Error('Network printer IP not configured');
    const device = new Network(ip, port);
    return { device, escpos, Printer: escpos.Printer };
  }

  throw new Error(`Unknown printer type: ${printerType}`);
}

/**
 * Print a full sales receipt using ESC/POS commands.
 */
function printReceipt(printerType, store, data) {
  return new Promise((resolve, reject) => {
    try {
      const { device, escpos, Printer } = openDevice(printerType, store);

      device.open((err) => {
        if (err) return reject(new Error(`Cannot open printer: ${err.message}`));

        const W = store.get('printerWidth', 48); // configurable paper width (chars)
        const divider = '-'.repeat(W);
        const printer = new Printer(device, { encoding: 'UTF-8', width: W });

        printer
          .align('CT')
          .style('B')
          .size(2, 2)
          .text(sanitize(data.store_name || 'ForeverKids'))
          .size(1, 1)
          .style('NORMAL');

        if (data.store_address) printer.text(sanitize(data.store_address));
        if (data.gstin)         printer.text(`GSTIN: ${sanitize(data.gstin)}`);
        if (data.store_phone)   printer.text(`Ph: ${sanitize(data.store_phone)}`);

        printer
          .text(divider)
          .align('LT')
          .text(`Bill #: ${sanitize(data.sale_number)}`)
          .text(`Date  : ${sanitize(data.date)}`)
          .text(`Cashier: ${sanitize(data.cashier || '-')}`);

        if (data.customer) printer.text(`Customer: ${sanitize(data.customer)}`);

        printer.text(divider);

        // Column header
        printer
          .style('B')
          .text(padColumns('Item', 'Qty', 'Amount', W))
          .style('NORMAL')
          .text(divider);

        // Line items
        if (Array.isArray(data.items)) {
          for (const item of data.items) {
            const name   = sanitize(item.name || 'Product').substring(0, W - 20);
            const qty    = String(item.quantity || 1);
            const amount = formatCurrency(item.total || 0);
            printer.text(padColumns(name, qty, amount, W));

            if (item.price && item.quantity > 1) {
              printer.text(`  ${qty} x ${formatCurrency(item.price)}`);
            }
            if (item.discount > 0) {
              printer.text(`  Disc: -${formatCurrency(item.discount)}`);
            }
            if (item.hsn) {
              printer.text(`  HSN: ${sanitize(item.hsn)} | Tax: ${item.tax_rate || 0}%`);
            }
          }
        }

        printer.text(divider);

        // Totals
        printer.text(padRight('Subtotal:', formatCurrency(data.subtotal || 0), W));
        if ((data.discount || 0) > 0) {
          printer.text(padRight('Discount:', `-${formatCurrency(data.discount)}`, W));
        }
        if ((data.tax || 0) > 0) {
          printer.text(padRight('GST:', formatCurrency(data.tax), W));
        }

        printer
          .text(divider)
          .style('B')
          .size(1, 2)
          .text(padRight('TOTAL:', formatCurrency(data.total || 0), W))
          .size(1, 1)
          .style('NORMAL')
          .text(divider);

        // Payment info
        const method = (data.payment_method || 'cash').toUpperCase();
        printer.text(padRight('Payment:', method, W));
        if (data.payment_ref) {
          printer.text(padRight('Ref:', sanitize(data.payment_ref), W));
        }
        if ((data.paid_amount || 0) > 0) {
          printer.text(padRight('Paid:', formatCurrency(data.paid_amount), W));
        }
        if ((data.change_amount || 0) > 0) {
          printer.text(padRight('Change:', formatCurrency(data.change_amount), W));
        }

        // Credit note used
        const cn = data.payment_details?.credit_note;
        if (cn) {
          printer.text(padRight(`Credit Note(${sanitize(cn.number)}):`, `-${formatCurrency(cn.amount)}`, W));
        }

        // GST breakup
        if (Array.isArray(data.gst_breakup) && data.gst_breakup.length > 0) {
          printer.text(divider);
          printer.style('B').text('GST Breakup:').style('NORMAL');
          for (const gst of data.gst_breakup) {
            if ((gst.igst || 0) > 0) {
              // Inter-state sale — print IGST
              printer.text(`  ${gst.rate}%  IGST: ${formatCurrency(gst.igst)}`);
            } else {
              // Intra-state sale — print CGST + SGST
              printer.text(
                `  ${gst.rate}%  CGST: ${formatCurrency(gst.cgst)}  SGST: ${formatCurrency(gst.sgst)}`
              );
            }
          }
        }

        // Footer
        printer
          .text(divider)
          .align('CT')
          .text(sanitize(data.footer || 'Thank you for shopping with us!'));

        if (data.return_policy) {
          printer.text(sanitize(data.return_policy));
        }

        printer
          .text(' ')
          .cut()
          .close(() => {
            log.info(`Receipt printed: ${data.sale_number}`);
            resolve({ success: true });
          });
      });
    } catch (err) {
      reject(err);
    }
  });
}

/**
 * Print a short test receipt to verify printer connection.
 */
function testPrint(printerType, store) {
  return new Promise((resolve, reject) => {
    try {
      const { device, escpos, Printer } = openDevice(printerType, store);

      device.open((err) => {
        if (err) return reject(new Error(`Cannot open printer: ${err.message}`));

        const printer = new Printer(device, { encoding: 'UTF-8', width: 48 });

        printer
          .align('CT')
          .style('B')
          .size(2, 2)
          .text('ForeverKids POS')
          .size(1, 1)
          .style('NORMAL')
          .text('--------------------------------')
          .text('Printer Test Successful!')
          .text(`Date: ${new Date().toLocaleString('en-IN')}`)
          .text(`Type: ${printerType.toUpperCase()}`)
          .text('--------------------------------')
          .text('If you can read this, your')
          .text('printer is configured correctly.')
          .text(' ')
          .cut()
          .close(() => {
            log.info('Test print completed');
            resolve({ success: true });
          });
      });
    } catch (err) {
      reject(err);
    }
  });
}

// --- Helpers ---

function sanitize(str) {
  // Remove any non-printable characters that could confuse the printer
  return String(str).replace(/[^\x20-\x7E\u00A0-\u00FF₹]/g, '').trim();
}

function formatCurrency(amount) {
  return `Rs.${Number(amount).toFixed(2)}`;
}

function padColumns(col1, col2, col3, width) {
  const c1 = col1.substring(0, width - 16);
  const c2 = String(col2).padStart(4);
  const c3 = String(col3).padStart(10);
  const pad = width - c1.length - c2.length - c3.length;
  return c1 + ' '.repeat(Math.max(1, pad)) + c2 + c3;
}

function padRight(label, value, width) {
  const pad = width - label.length - value.length;
  return label + ' '.repeat(Math.max(1, pad)) + value;
}

function withTimeout(promise, ms) {
  return Promise.race([
    promise,
    new Promise((_, reject) =>
      setTimeout(() => reject(new Error(`Printer operation timed out after ${ms}ms`)), ms)
    ),
  ]);
}

/**
 * Call the thermal printer directly from the main process (no IPC round-trip).
 * Used by handleReceiptPrint in window-manager when the renderer fallback fires.
 */
async function printReceiptDirect(store, receiptData) {
  const printerType = store.get('printerType', 'none');
  if (printerType === 'none') {
    return { success: false, error: 'No printer configured. Open Printer Settings from the system tray.' };
  }
  if (!receiptData || !receiptData.sale_number) {
    return { success: false, error: 'Invalid receipt data' };
  }
  try {
    return await withTimeout(printReceipt(printerType, store, receiptData), PRINT_TIMEOUT_MS);
  } catch (err) {
    log.error('printReceiptDirect failed:', err.message);
    return { success: false, error: err.message };
  }
}

module.exports = { setupPrinterIPC, printReceiptDirect };
