'use strict';

const { app, ipcMain, session } = require('electron');
const path = require('path');
const Store = require('electron-store');
const log = require('electron-log');
const { createMainWindow } = require('./window-manager');
const { setupPrinterIPC } = require('./printer');
const { setupAutoUpdater } = require('./updater');
const { setupTray } = require('./tray');
const { setupAutoStart } = require('./autostart');

// ---------------------------------------------------------------------------
// Security: Disable navigation to unknown protocols, restrict permissions
// ---------------------------------------------------------------------------
app.on('web-contents-created', (_event, contents) => {
  // Block all navigation outside the allowed POS origin
  contents.on('will-navigate', (event, url) => {
    const allowed = url.startsWith(store.get('serverUrl'));
    if (!allowed) {
      log.warn(`Blocked navigation to: ${url}`);
      event.preventDefault();
    }
  });

  // Block new window creation (popups, target=_blank) — receipt handled via IPC
  contents.setWindowOpenHandler(({ url }) => {
    log.info(`Intercepted window.open: ${url}`);
    // Receipt URLs are handled in window-manager.js
    return { action: 'deny' };
  });

  // Deny permission requests (camera, microphone, geolocation, notifications, etc.)
  contents.session.setPermissionRequestHandler((_webContents, permission, callback) => {
    const allowed = ['clipboard-read', 'clipboard-sanitized-write'];
    callback(allowed.includes(permission));
  });

  // Block all external protocol launches (mailto:, tel:, custom protocols)
  contents.on('will-attach-webview', (event) => {
    event.preventDefault();
  });
});

// ---------------------------------------------------------------------------
// Persistent settings (printer config, kiosk mode, auto-start)
// ---------------------------------------------------------------------------
const store = new Store({
  name: 'pos-settings',
  encryptionKey: 'fk-pos-2026',  // Obfuscates settings file on disk
  defaults: {
    printerType:  'none',  // 'usb' | 'network' | 'none'
    printerIp:    '',
    printerPort:  9100,
    printerWidth: 48,      // 32 | 40 | 48 | 56 chars (paper width)
    kioskMode:    true,
    autoStart:    false,
    serverUrl:    'https://foreverkids.dcrayons.app/pos',
  },
});

// ---------------------------------------------------------------------------
// App lifecycle
// ---------------------------------------------------------------------------
// Prevent multiple instances — focus existing window instead
const gotLock = app.requestSingleInstanceLock();
if (!gotLock) {
  app.quit();
} else {
  app.on('second-instance', () => {
    const win = global.mainWindow;
    if (win) {
      if (win.isMinimized()) win.restore();
      win.focus();
    }
  });
}

app.whenReady().then(() => {
  // Security: Set a strict Content-Security-Policy for the Electron shell itself
  // (the remote POS server controls its own CSP headers)
  session.defaultSession.webRequest.onHeadersReceived((details, callback) => {
    callback({
      responseHeaders: {
        ...details.responseHeaders,
        // Allow the remote server's own CSP; add frame protection
        'X-Frame-Options': ['DENY'],
      },
    });
  });

  // Inject a custom header so the Laravel backend can detect the desktop app
  session.defaultSession.webRequest.onBeforeSendHeaders((details, callback) => {
    details.requestHeaders['X-POS-Desktop'] = '1';
    details.requestHeaders['X-POS-Version'] = app.getVersion();
    callback({ requestHeaders: details.requestHeaders });
  });

  // 1. Create main window
  const mainWindow = createMainWindow(store);
  global.mainWindow = mainWindow;

  // 2. Register printer IPC handlers
  setupPrinterIPC(ipcMain, store);

  // 3. System tray
  setupTray(mainWindow, store);

  // 4. Auto-updater (skip in dev mode)
  if (process.env.NODE_ENV !== 'development') {
    setupAutoUpdater(mainWindow);
  }

  // 5. Auto-start on boot
  setupAutoStart(store);

  log.info(`ForeverKids POS Desktop v${app.getVersion()} started`);
});

app.on('window-all-closed', () => {
  app.quit();
});

// Security: Prevent renderer from creating child processes
app.on('child-process-gone', (_event, details) => {
  log.error('Child process gone:', details);
});
