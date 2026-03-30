'use strict';

const { BrowserWindow, net } = require('electron');
const path = require('path');
const log = require('electron-log');
const { printReceiptDirect } = require('./printer');

/**
 * Create the main POS BrowserWindow.
 *
 * Security hardening:
 *  - contextIsolation: true   → renderer can't access Node.js
 *  - nodeIntegration: false   → no require() in the web page
 *  - sandbox: true            → process-level sandboxing
 *  - webviewTag: false        → no <webview> embedding
 *  - allowRunningInsecureContent: false → no mixed HTTP content
 *  - Navigation locked to the POS server origin only
 *  - window.open() intercepted — receipt URLs routed to native printer
 */
function createMainWindow(store) {
  const serverUrl = store.get('serverUrl');
  const isKiosk = store.get('kioskMode', true);

  const win = new BrowserWindow({
    width: 1366,
    height: 768,
    minWidth: 1024,
    minHeight: 600,
    kiosk: isKiosk,
    fullscreen: isKiosk,
    autoHideMenuBar: true,
    frame: false,
    title: 'ForeverKids POS',
    backgroundColor: '#1E293B',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: true,
      webviewTag: false,
      allowRunningInsecureContent: false,
      enableWebSQL: false,
      spellcheck: false,
      autoplayPolicy: 'user-gesture-required',
    },
    icon: path.join(__dirname, '../../assets/icon.ico'),
  });

  // Load the remote POS URL
  win.loadURL(serverUrl);

  // --- Navigation lock ---
  // Only allow URLs that start with the configured server origin
  win.webContents.on('will-navigate', (event, url) => {
    if (!url.startsWith(serverUrl) && !url.startsWith('about:')) {
      log.warn(`Navigation blocked: ${url}`);
      event.preventDefault();
    }
  });

  // --- Intercept window.open() (receipt printing + security) ---
  win.webContents.setWindowOpenHandler(({ url }) => {
    // Receipt URL pattern: /pos/sale/{id}/receipt
    if (url.includes('/pos/sale/') && url.includes('/receipt')) {
      handleReceiptPrint(win, url, store);
      return { action: 'deny' };
    }

    // Block everything else
    log.warn(`Popup blocked: ${url}`);
    return { action: 'deny' };
  });

  // --- Keyboard shortcuts ---
  win.webContents.on('before-input-event', (event, input) => {
    // F11 → toggle kiosk/fullscreen
    if (input.key === 'F11' && input.type === 'keyDown') {
      const isK = win.isKiosk();
      win.setKiosk(!isK);
      win.setFullScreen(!isK);
    }

    // Ctrl+Shift+P → open printer settings (handled via IPC in tray.js)
    if (input.control && input.shift && input.key === 'P' && input.type === 'keyDown') {
      win.webContents.send('open-printer-settings-shortcut');
    }

    // Ctrl+Shift+D → open DevTools (development only)
    if (input.control && input.shift && input.key === 'D' && input.type === 'keyDown') {
      if (process.env.NODE_ENV === 'development') {
        win.webContents.toggleDevTools();
      }
    }

    // Block Ctrl+L (address bar habit), Ctrl+T (new tab), Ctrl+N (new window)
    if (input.control && !input.shift && ['l', 't', 'n'].includes(input.key.toLowerCase()) && input.type === 'keyDown') {
      event.preventDefault();
    }
  });

  // --- Error handling ---
  win.webContents.on('did-fail-load', (_event, errorCode, errorDescription) => {
    log.error(`Page load failed: ${errorCode} — ${errorDescription}`);
    // Show a friendly offline page after 3 seconds, then retry
    setTimeout(() => {
      win.loadURL(serverUrl);
    }, 5000);
  });

  win.webContents.on('render-process-gone', (_event, details) => {
    log.error('Render process gone:', details.reason);
    win.reload();
  });

  win.on('unresponsive', () => {
    log.warn('Window unresponsive, reloading...');
    win.reload();
  });

  return win;
}

/**
 * Fetch receipt data as JSON from the server and send it to the printer via IPC.
 * Falls back to opening the HTML receipt if JSON endpoint fails.
 */
async function handleReceiptPrint(win, receiptUrl, store) {
  // Step 1: fetch receipt JSON from the server using the BrowserWindow's session
  // so auth cookies are sent. net.request string-form does NOT auto-attach cookies
  // in Electron v29+, so we pass the session object explicitly.
  let receiptData = null;
  try {
    const jsonUrl = receiptUrl.replace('/receipt', '/receipt-data');
    receiptData = await new Promise((resolve, reject) => {
      const request = net.request({
        url: jsonUrl,
        session: win.webContents.session,
        useSessionCookies: true,
      });
      request.setHeader('Accept', 'application/json');
      let body = '';
      request.on('response', (res) => {
        res.on('data', (chunk) => { body += chunk.toString(); });
        res.on('end', () => {
          const ct = String(res.headers['content-type'] || '');
          if (res.statusCode === 200 && ct.includes('application/json')) {
            try { resolve(JSON.parse(body)); } catch (e) {
              reject(new Error('Server returned invalid JSON'));
            }
          } else if (res.statusCode === 401) {
            reject(new Error('POS session expired'));
          } else {
            reject(new Error(`HTTP ${res.statusCode}`));
          }
        });
      });
      request.on('error', reject);
      request.end();
    });
  } catch (fetchErr) {
    log.warn('Receipt JSON fetch failed, using HTML fallback:', fetchErr.message);
    openHtmlPrintWindow(receiptUrl);
    return;
  }

  // Step 2: send directly to the thermal printer (no renderer round-trip)
  try {
    const result = await printReceiptDirect(store, receiptData);
    if (!result.success) {
      log.warn('Thermal print failed, using HTML fallback:', result.error);
      // Notify the renderer so the UI can show an error toast
      win.webContents.send('printer-error', result.error);
      openHtmlPrintWindow(receiptUrl);
    }
  } catch (printErr) {
    log.error('printReceiptDirect threw:', printErr.message);
    openHtmlPrintWindow(receiptUrl);
  }
}

/**
 * Open a hidden BrowserWindow and trigger the system print dialog on the HTML receipt.
 * Used as a last-resort fallback when the thermal printer is unavailable.
 */
function openHtmlPrintWindow(receiptUrl) {
  try {
    const printWin = new BrowserWindow({
      width: 400,
      height: 600,
      show: false,
      webPreferences: {
        contextIsolation: true,
        nodeIntegration: false,
        sandbox: true,
      },
    });
    printWin.loadURL(receiptUrl);
    printWin.webContents.on('did-finish-load', () => {
      printWin.webContents.print({}, () => printWin.close());
    });
  } catch (err) {
    log.error('HTML fallback print window failed:', err.message);
  }
}

module.exports = { createMainWindow };
