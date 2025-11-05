# EvenLeads Browser Extension - Installation Guide

## 📦 Ready-to-Use Extension Package

The extension has been **built and packaged** for you! You'll find it at:

```
evenleads-extension/.output/EvenLeads-Extension-v1.0.0.zip
```

## 🚀 Installation Steps

### For Chrome/Edge (Recommended)

1. **Extract the Extension**
   - Navigate to `evenleads-extension/.output/`
   - You'll see the `chrome-mv3` folder (already built)
   - Or extract `EvenLeads-Extension-v1.0.0.zip` if you want a fresh copy

2. **Open Chrome Extensions Page**
   - Open Chrome/Edge
   - Navigate to `chrome://extensions/` (or `edge://extensions/`)
   - Enable **Developer mode** (toggle in top-right corner)

3. **Load the Extension**
   - Click **Load unpacked**
   - Navigate to and select the `evenleads-extension/.output/chrome-mv3/` folder
   - The extension should now appear in your extensions list

4. **Pin the Extension** (Optional)
   - Click the puzzle icon (🧩) in Chrome toolbar
   - Find "EvenLeads" and click the pin icon
   - The extension icon will appear in your toolbar

## 🎮 How to Use

### 1. **Open the Sidebar**
You can open the EvenLeads sidebar in **three ways**:

- **Click the floating "1L" button** (bottom-right corner of any webpage)
- **Press the keyboard shortcut**: `Ctrl+Shift+L` (Windows/Linux) or `Cmd+Shift+L` (Mac)
- **Click the extension icon** in your browser toolbar

### 2. **Sign In**
- The sidebar will open with a "Sign in with EvenLeads" button
- Click the button to open the OAuth login page
- Enter your credentials from your EvenLeads account
- After successful login, you'll see a welcome message
- The sidebar will automatically show your campaigns

### 3. **Managing Campaigns**
- View all your active campaigns
- Select which campaign should receive leads
- Click "Campaigns" or "Account" tabs at the bottom to switch views

### 4. **Account Settings**
- View your subscription details
- Check plan limits (campaigns, leads, syncs, etc.)
- Access dashboard and manage subscription
- Sign out when needed

## ⚙️ Configuration

### API URL Setup
By default, the extension connects to `http://localhost:8000`. To change this:

1. Open Chrome DevTools (`F12`)
2. Go to Console tab
3. Run this command:
```javascript
chrome.storage.local.set({'api:url': 'https://your-domain.com'})
```

Replace `https://your-domain.com` with your actual EvenLeads backend URL.

### Laravel Backend Setup

The extension requires these routes to be available on your Laravel backend:

- `GET /auth/extension` - OAuth login page
- `POST /auth/extension` - Handle login
- `GET /auth/extension/callback` - OAuth callback

These routes have already been added to your Laravel application in `routes/web.php`.

## 🔧 Development Mode

If you want to make changes to the extension:

```bash
cd evenleads-extension

# Install dependencies (already done)
npm install

# Start development mode with hot reload
npm run dev

# Build for production
npm run build

# Create a ZIP for distribution
npm run zip
```

## ✨ Features

- ✅ **Sidebar UI** - Clean, responsive sidebar that slides in from the right
- ✅ **Floating Icon** - Always-visible "1L" button in bottom-right corner
- ✅ **Keyboard Shortcut** - `Ctrl+Shift+L` to toggle sidebar
- ✅ **OAuth Authentication** - "Sign in with EvenLeads" button
- ✅ **Welcome Messages** - Shows "Welcome!" or "Welcome back!" after login
- ✅ **Campaign Management** - View and select active campaigns
- ✅ **Account Info** - View subscription, plan limits, and user details
- ✅ **Black & White Design** - Clean, professional styling with Tailwind CSS
- ✅ **Dark Mode Support** - Automatically adapts to system theme

## 📱 Supported Platforms

Currently monitoring:
- Facebook Groups
- LinkedIn (Feed, Profiles, Jobs, Search)
- Reddit
- Fiverr
- Upwork

## 🐛 Troubleshooting

### Extension not loading?
- Make sure you selected the `chrome-mv3` folder, not the parent folder
- Check that Developer mode is enabled
- Try reloading the extension

### Can't connect to backend?
- Verify your Laravel server is running
- Check the API URL in storage (see Configuration section)
- Ensure CORS is configured correctly on your Laravel backend

### Sidebar not opening?
- Try clicking the floating "1L" button
- Use the keyboard shortcut `Ctrl+Shift+L`
- Check browser console for errors (`F12`)

### OAuth login not working?
- Ensure your Laravel backend has the extension routes configured
- Check that the API URL in extension storage matches your backend
- Verify the `/auth/extension` route is accessible

## 📂 File Structure

```
evenleads-extension/
├── .output/
│   ├── chrome-mv3/              ← Load this folder in Chrome
│   │   ├── manifest.json
│   │   ├── background.js
│   │   ├── content-scripts/
│   │   │   ├── sidebar.js       ← Sidebar + floating icon
│   │   │   └── sidebar.css
│   │   ├── icon/
│   │   │   ├── 16.png
│   │   │   ├── 32.png
│   │   │   ├── 48.png
│   │   │   └── 128.png
│   │   ├── oauth-callback.html  ← OAuth callback page
│   │   └── popup.html
│   └── EvenLeads-Extension-v1.0.0.zip  ← Distribution package
├── entrypoints/
├── components/
├── utils/
└── package.json
```

## 🎉 You're All Set!

The extension is now ready to use. Visit any supported platform (Facebook, LinkedIn, etc.) and click the floating "1L" button or press `Ctrl+Shift+L` to get started!

For support, please contact the EvenLeads team.
