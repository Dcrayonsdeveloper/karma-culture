'use strict';

const { app } = require('electron');
const log = require('electron-log');

/**
 * Configure Windows auto-start on boot using Electron's built-in API.
 * This manages the registry entry at HKCU\Software\Microsoft\Windows\CurrentVersion\Run.
 */
function setupAutoStart(store) {
  const shouldAutoStart = store.get('autoStart', false);

  try {
    app.setLoginItemSettings({
      openAtLogin: shouldAutoStart,
      path: app.getPath('exe'),
      args: [],
    });

    log.info(`Auto-start ${shouldAutoStart ? 'enabled' : 'disabled'}`);
  } catch (err) {
    log.error('Failed to set auto-start:', err.message);
  }
}

module.exports = { setupAutoStart };
