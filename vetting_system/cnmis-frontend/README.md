# CNMIS Frontend

Vue 3 SPA with TypeScript, Vuetify, and PWA support for the Change of Name Management Information System.

## 🚀 Quick Start

### Prerequisites
- Node.js 20.19.0 or >=22.12.0
- npm or yarn

### Installation

1. Install dependencies:
```bash
npm install
```

2. Create environment file:
```bash
cp .env.example .env
```

3. Update `.env` with your API URL:
```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

4. Run development server:
```bash
npm run dev
```

The app will be available at `http://localhost:5173`

## 📁 Project Structure

```
src/
├── api/              # API service layer (TypeScript)
├── stores/           # Pinia stores
├── router/           # Vue Router configuration
├── views/            # Page components
├── components/       # Reusable components
├── layouts/          # Layout components
├── types/            # TypeScript type definitions
└── main.ts           # App entry point
```

## 🛠️ Available Scripts

- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm run preview` - Preview production build
- `npm run type-check` - Type check TypeScript
- `npm run lint` - Lint code
- `npm run format` - Format code with Prettier
- `npm run test:unit` - Run unit tests

## 📚 Tech Stack

- **Vue 3** - Progressive JavaScript framework
- **TypeScript** - Type safety
- **Vite** - Build tool
- **Vue Router** - Routing
- **Pinia** - State management
- **Vuetify 3** - Material Design components
- **Axios** - HTTP client
- **Vue Toastification** - Toast notifications
- **PWA** - Progressive Web App support

## 🔗 API Integration

All API endpoints are configured in `src/api/`. The base URL is configured via environment variables.

## 📱 PWA Features

- Service worker for offline support
- Installable on mobile devices
- Auto-update on new version

## 🎨 UI Framework

Vuetify 3 Material Design components are available throughout the app.

## 📝 TypeScript

Full TypeScript support with type definitions for all API responses and Vue components.

## 🔐 Authentication

Authentication is handled via Laravel Sanctum. Tokens are stored in localStorage and automatically included in API requests.

## 📖 Documentation

See `SETUP_COMPLETE.md` for detailed setup information and examples.
