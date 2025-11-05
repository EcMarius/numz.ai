# EvenLeads Browser Extension

A browser extension for collecting leads from Facebook, LinkedIn, Fiverr, and Upwork based on your EvenLeads campaign settings.

## Features

- **OAuth Authentication**: Securely connect with your EvenLeads account
- **Campaign Sync**: Automatically syncs your active campaigns
- **Platform Detection**: Monitors Facebook, LinkedIn, Fiverr, and Upwork
- **Real-time Lead Collection**: Detects and submits leads based on keywords
- **Plan Validation**: Checks subscription status and enforces plan limits
- **Black & White Design**: Clean, professional interface
- **Account Management**: View account info, subscription details, and plan limits

## Tech Stack

- **WXT Framework**: Modern browser extension framework
- **React**: UI components
- **TypeScript**: Type safety
- **Tailwind CSS**: Styling
- **Chrome Storage API**: Data persistence

## Development

### Prerequisites

- Node.js 18+ and npm
- A running EvenLeads installation

### Setup

1. Install dependencies:
```bash
npm install
```

2. Configure API URL:
   - Edit `wxt.config.ts` and update the API URL
   - Update the host_permissions with your EvenLeads domain

3. Start development server:
```bash
npm run dev
```

4. Load the extension:
   - Open Chrome/Edge: `chrome://extensions`
   - Enable "Developer mode"
   - Click "Load unpacked"
   - Select the `.output/chrome-mv3` directory

### Build for Production

```bash
npm run build
npm run zip
```

## Project Structure

```
evenleads-extension/
├── entrypoints/
│   ├── background.ts          # Background service worker
│   ├── content/               # Content scripts for each platform
│   │   ├── facebook.ts
│   │   ├── linkedin.ts
│   │   ├── fiverr.ts
│   │   └── upwork.ts
│   └── popup/                 # Extension popup UI
│       ├── App.tsx
│       ├── index.html
│       ├── main.tsx
│       └── style.css
├── components/                # React components
│   ├── LoginScreen.tsx
│   ├── CampaignList.tsx
│   └── AccountInfo.tsx
├── utils/                     # Utility functions
│   ├── api.ts
│   └── storage.ts
├── types/                     # TypeScript types
│   └── index.ts
├── package.json
├── tsconfig.json
├── tailwind.config.js
└── wxt.config.ts
```

## Content Scripts

Each platform has its own content script that:

1. Detects when you're on a relevant page (e.g., Facebook group)
2. Checks for active campaigns monitoring that page
3. Scans posts/listings for matching keywords
4. Calculates confidence scores
5. Submits leads to the EvenLeads API

### Current Implementation Status

- **Facebook**: ✅ Basic implementation (needs DOM selector updates)
- **LinkedIn**: 🔜 To be implemented
- **Fiverr**: 🔜 To be implemented
- **Upwork**: 🔜 To be implemented

## API Endpoints Required

The extension expects these API endpoints from your EvenLeads backend:

```typescript
POST   /api/auth/login                    // Login with email/password
GET    /api/auth/user                     // Get current user
GET    /api/auth/subscription              // Get subscription details
POST   /api/auth/logout                    // Logout
GET    /api/auth/validate-plan             // Validate plan & check limits
GET    /api/campaigns                      // List user's campaigns
GET    /api/campaigns/:id                  // Get single campaign
POST   /api/campaigns/:id/leads            // Submit single lead
POST   /api/campaigns/:id/leads/bulk       // Submit multiple leads
```

## Security Features

- Token-based authentication
- Secure storage using Chrome Storage API
- Plan validation before lead submission
- Automatic token refresh
- HTTPS-only communication

## Customization

### Adding New Platforms

1. Create a new content script in `entrypoints/content/[platform].ts`
2. Follow the pattern from `facebook.ts`
3. Update the platform selectors and DOM parsing logic
4. Add platform configuration to `types/index.ts`

### Styling

The extension uses Tailwind CSS with a black & white theme:
- Customize colors in `tailwind.config.js`
- Modify component styles in respective `.tsx` files

### API Configuration

Update the API base URL in:
- `wxt.config.ts` (host_permissions)
- Default API URL in `utils/storage.ts`

## Browser Support

- ✅ Chrome (MV3)
- ✅ Edge (MV3)
- 🔜 Firefox (with `npm run build:firefox`)

## Troubleshooting

### Extension won't load
- Check that you've run `npm install`
- Ensure the `.output` directory exists
- Verify developer mode is enabled

### Can't login
- Check the API URL is correct
- Open DevTools > Console for error messages
- Verify your EvenLeads backend is running

### Leads not being detected
- Check that you're on a monitored platform
- Verify you have active campaigns with keywords
- Open DevTools > Console to see detection logs

## Contributing

1. Update the relevant content script for the platform
2. Test thoroughly on the actual platform
3. Update this README with any changes
4. Submit a pull request

## License

Proprietary - Part of EvenLeads
