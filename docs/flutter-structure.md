# Flutter Apps Structure

## Shared Dependencies
- Firebase: Core, Auth, Firestore, Crashlytics, Analytics
- Google Maps Flutter
- OneSignal for notifications
- HTTP for API communication
- Stripe/Razorpay for payments

## Configuration Files
- `utils/Constants.dart` - URLs, API keys, settings
- `utils/Colors.dart` - Theme colors
- `firebase_options.dart` - Firebase config
- `config/secrets.dart` - Sensitive keys (gitignored)

## Key Services
- `LoggingService` - Enhanced logging with Firebase integration
- `OneSignalService` - Push notification handling
- `NotificationService` - Local notifications
- `ChatMessageService` - Real-time messaging

## Important Constants
```dart
const DOMAIN_URL = 'https://taxibal.xyz';
const mBaseUrl = "$DOMAIN_URL/api/";
const GOOGLE_MAP_API_KEY = Secrets.googleMapApiKey;
const currencySymbol = '\$';
const defaultCountry = 'MX';
