'use strict';

const { Tray, Menu, BrowserWindow, ipcMain, nativeImage } = require('electron');
const path = require('path');
const { setupAutoStart } = require('./autostart');

let tray = null;
let printerSettingsWindow = null;

function setupTray(mainWindow, store) {
  const iconPath = path.join(__dirname, '../../assets/tray-icon.png');

  // Create a fallback icon if file doesn't exist
  let icon;
  try {
    icon = nativeImage.createFromPath(iconPath);
    if (icon.isEmpty()) throw new Error('empty');
  } catch {
    // 16x16 fallback icon
    icon = nativeImage.createEmpty();
  }

  tray = new Tray(icon);
  tray.setToolTip('ForeverKids POS');

  const buildMenu = () => Menu.buildFromTemplate([
    { label: 'ForeverKids POS', enabled: false, icon: null },
    { type: 'separator' },
    {
      label: 'Printer Settings',
      click: () => openPrinterSettings(store),
    },
    {
      label: 'Toggle Fullscreen',
      accelerator: 'F11',
      click: () => {
        const isK = mainWindow.isKiosk();
        mainWindow.setKiosk(!isK);
        mainWindow.setFullScreen(!isK);
      },
    },
    { type: 'separator' },
    {
      label: 'Auto-Start on Boot',
      type: 'checkbox',
      checked: store.get('autoStart', false),
      click: (menuItem) => {
        store.set('autoStart', menuItem.checked);
        setupAutoStart(store);
      },
    },
    { type: 'separator' },
    {
      label: 'Reload POS',
      click: () => mainWindow.reload(),
    },
    ...(process.env.NODE_ENV === 'development' ? [{
      label: 'DevTools',
      click: () => mainWindow.webContents.toggleDevTools(),
    }] : []),
    { type: 'separator' },
    {
      label: 'Quit',
      click: () => {
        mainWindow.destroy();
        if (printerSettingsWindow) printerSettingsWindow.destroy();
        require('electron').app.quit();
      },
    },
  ]);

  tray.setContextMenu(buildMenu());

  // Single-click restores the main window
  tray.on('click', () => {
    if (mainWindow.isMinimized()) mainWindow.restore();
    mainWindow.focus();
  });

  // Listen for IPC to open printer settings
  ipcMain.on('open-printer-settings', () => openPrinterSettings(store));

  return tray;
}

function openPrinterSettings(store) {
  // Prevent multiple settings windows
  if (printerSettingsWindow && !printerSettingsWindow.isDestroyed()) {
    printerSettingsWindow.focus();
    return;
  }

  printerSettingsWindow = new BrowserWindow({
    width: 480,
    height: 520,
    resizable: false,
    minimizable: false,
    maximizable: false,
    fullscreenable: false,
    autoHideMenuBar: true,
    title: 'Printer Settings',
    parent: global.mainWindow || null,
    modal: false,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: true,
    },
    icon: path.join(__dirname, '../../assets/icon.ico'),
  });

  printerSettingsWindow.loadFile(path.join(__dirname, '../renderer/printer-settings.html'));

  printerSettingsWindow.on('closed', () => {
    printerSettingsWindow = null;
  });
}

module.exports = { setupTray };
