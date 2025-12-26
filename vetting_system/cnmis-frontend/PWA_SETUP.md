# PWA Setup Instructions

## Installation

The PWA plugin has been configured. To complete the setup:

1. **Install the PWA plugin** (if not already installed):
   
   **Important:** Since you're using Vite 7, you need to use the `--legacy-peer-deps` flag:
   ```bash
   npm install -D vite-plugin-pwa --legacy-peer-deps
   ```
   
   This is because `vite-plugin-pwa` currently doesn't officially support Vite 7 yet, but it should work fine with the legacy peer deps flag. The plugin is actively being updated to support Vite 7.

2. **Create PWA Icons**:
   You need to create the following icon files in the `public` folder:
   - `pwa-192x192.png` - 192x192 pixels (for Android)
   - `pwa-512x512.png` - 512x512 pixels (for Android)
   - `apple-touch-icon.png` - 180x180 pixels (for iOS)
   - `mask-icon.svg` - SVG icon for Safari (optional)

   You can use online tools like:
   - https://realfavicongenerator.net/
   - https://www.pwabuilder.com/imageGenerator
   - Or create them using design tools like Figma, Photoshop, etc.

   Recommended icon design:
   - Use the CNMIS logo or a simple "CNMIS" text
   - Use the primary color (#1976d2) as background
   - Ensure icons are clear and recognizable at small sizes

   **Quick generator (included in this repo):**
   - This project includes a tiny script that generates valid PNG icons (solid CNMIS primary color) into `public/`.
   - Run:
   ```bash
   npm run generate:pwa-icons
   ```
   - It will create:
     - `public/pwa-192x192.png`
     - `public/pwa-512x512.png`
     - `public/apple-touch-icon.png`

3. **Build and Test**:
   ```bash
   npm run generate:pwa-icons
   npm run build
   npm run preview
   ```

   Then test the PWA:
   - Open in Chrome/Edge
   - Check the install prompt appears
   - Test offline functionality
   - Verify service worker is registered

## Install on a mobile device over LAN (Network URL)

If you open the app as `http://192.168.x.x:4174/` on your phone and **don’t see the install prompt**, that is expected:

- **Chrome/Edge require a secure context (HTTPS)** for PWA installability on non-localhost origins.
- `http://localhost:4174/` is treated as secure for development, but `http://192.168...` is **not**.

### Options

1) **Recommended: serve preview over HTTPS**
- Use a trusted local certificate (e.g. `mkcert`) and run preview with HTTPS.
- After switching to HTTPS, open `https://192.168.x.x:4174/` on the phone and the install prompt should appear.

2) **iOS Safari note**
- iOS Safari does **not** show the Chrome-style install prompt. Use:
  - Share → **Add to Home Screen**

## Features Implemented

✅ **Service Worker**: Automatically caches app assets for offline use
✅ **Auto Update**: App automatically updates when new version is available
✅ **Install Prompt**: Shows install prompt to users (can be dismissed)
✅ **Update Notification**: Notifies users when updates are available
✅ **Offline Support**: App works offline with cached data
✅ **API Caching**: API responses are cached for offline access
✅ **Image Caching**: Images are cached for faster loading

## Configuration

The PWA is configured in `vite.config.ts`:
- **Register Type**: `autoUpdate` - automatically updates when new version is available
- **Theme Color**: `#1976d2` (Vuetify primary color)
- **Display Mode**: `standalone` - app appears as standalone app
- **Cache Strategy**:
  - API calls: NetworkFirst (tries network, falls back to cache)
  - Images: CacheFirst (uses cache first, then network)

## Testing PWA

1. **Install Test**:
   - Build the app: `npm run build`
   - Serve: `npm run preview`
   - Open in Chrome/Edge
   - Look for install icon in address bar
   - Or use the install prompt

2. **Offline Test**:
   - Install the app
   - Open DevTools > Network tab
   - Check "Offline" checkbox
   - Refresh the app
   - App should still work with cached data

3. **Update Test**:
   - Make a change to the app
   - Rebuild: `npm run build`
   - Reload the installed app
   - Should see update notification

## Browser Support

- ✅ Chrome/Edge (Desktop & Mobile)
- ✅ Safari (iOS 11.3+)
- ✅ Firefox (Desktop & Mobile)
- ⚠️ Safari (Desktop) - Limited support

## Troubleshooting

1. **Service Worker not registering**:
   - Ensure you're using HTTPS (or localhost for development)
   - Check browser console for errors
   - Clear browser cache and try again

2. **Icons not showing**:
   - Ensure icon files exist in `public` folder
   - Check file names match exactly
   - Verify file sizes are correct

3. **Install prompt not showing**:
   - App may already be installed
   - Check if browser supports PWA
   - Try in incognito mode

4. **Updates not working**:
   - Check service worker is registered
   - Clear cache and reinstall
   - Check browser console for errors

