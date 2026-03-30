'use strict';

const { contextBridge, ipcRenderer } = require('electron');

// ---------------------------------------------------------------------------
// Security: Only expose a minimal, well-defined API to the renderer.
// contextIsolation: true + nodeIntegration: false ensures the remote web page
// cannot access Node.js, filesystem, or any Electron APIs directly.
// ---------------------------------------------------------------------------

const ALLOWED_CHANNELS_INVOKE = [
  'printer:print-receipt',
  'printer:status',
  'printer:test',
  'printer:discover-usb',
  'settings:get',
  'settings:save',
];

const ALLOWED_CHANNELS_SEND = [
  'open-printer-settings',
  'toggle-fullscreen',
];

const ALLOWED_CHANNELS_ON = [
  'native-print-receipt',
  'update-available',
  'update-downloaded',
  'printer-error',
];

contextBridge.exposeInMainWorld('posDesktop', {
  // Identity
  isDesktop: true,
  version: require('../../package.json').version,

  // Printer
  printReceipt: (receiptData) => {
    if (!receiptData || typeof receiptData !== 'object') return Promise.reject('Invalid receipt data');
    return ipcRenderer.invoke('printer:print-receipt', receiptData);
  },
  getPrinterStatus: () => ipcRenderer.invoke('printer:status'),
  testPrint: () => ipcRenderer.invoke('printer:test'),
  discoverPrinters: () => ipcRenderer.invoke('printer:discover-usb'),

  // Settings
  openPrinterSettings: () => ipcRenderer.send('open-printer-settings'),
  getSettings: () => ipcRenderer.invoke('settings:get'),
  saveSettings: (settings) => {
    if (!settings || typeof settings !== 'object') return Promise.reject('Invalid settings');
    // Only allow known setting keys to be saved
    const allowedKeys = ['printerType', 'printerIp', 'printerPort', 'kioskMode', 'autoStart'];
    const sanitized = {};
    for (const key of allowedKeys) {
      if (key in settings) sanitized[key] = settings[key];
    }
    return ipcRenderer.invoke('settings:save', sanitized);
  },

  // Window
  toggleFullscreen: () => ipcRenderer.send('toggle-fullscreen'),

  // Events (subscribe only — no arbitrary channel listening)
  onPrintReceipt: (callback) => {
    if (typeof callback !== 'function') return;
    ipcRenderer.on('native-print-receipt', (_event, url) => callback(url));
  },
  onUpdateAvailable: (callback) => {
    if (typeof callback !== 'function') return;
    ipcRenderer.on('update-available', (_event, version) => callback(version));
  },
  onUpdateDownloaded: (callback) => {
    if (typeof callback !== 'function') return;
    ipcRenderer.on('update-downloaded', (_event, version) => callback(version));
  },
  onPrinterError: (callback) => {
    if (typeof callback !== 'function') return;
    ipcRenderer.on('printer-error', (_event, error) => callback(error));
  },
});
