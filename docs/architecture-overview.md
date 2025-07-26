## Información General

**Nombre del Proyecto:** Taxibal  
**Dominio:** taxibal.xyz  
**Tipo:** Plataforma de transporte on-demand

## Stack Tecnológico

### Backend Server
- **Tecnología:** Laravel (PHP)
- **Base de Datos:** MySQL (`taxibalx_taxibalM`)
- **Servidor:** VPS Hostinger
- **Función:** API REST y panel de administración

### Aplicaciones Móviles
- **Tecnología:** Flutter (Dart)
- **Plataformas:** iOS y Android
- **Rider App:** App para pasajeros
- **Driver App:** App para conductores

## Integraciones y Servicios

### Autenticación
- **Firebase Authentication:** Login con email/password, Google, Apple, teléfono (OTP)

### Base de Datos
- **MySQL:** Base de datos principal en VPS
- **Firestore:** Base de datos NoSQL para datos en tiempo real

### Mapas y Geolocalización
- **Google Maps API:** Mapas interactivos y navegación
- **Google Places API:** Autocompletado de direcciones
- **Google Directions API:** Cálculo de rutas
- **Google Geocoding API:** Conversión entre coordenadas y direcciones

### Notificaciones Push
- **OneSignal:** Notificaciones para Rider App
- **OneSignal:** Notificaciones para Driver App (canal separado)

### Analytics y Monitoreo
- **Google Analytics:** Métricas web
- **Firebase Analytics:** Métricas de apps móviles
- **Firebase Crashlytics:** Reporte de errores
- **LoggingService personalizado:** Sistema de logs avanzado

### Procesamiento de Pagos
- **Stripe:** Procesamiento de tarjetas de crédito/débito
- **Razorpay:** Gateway de pagos
- **Efectivo:** Pagos en efectivo

## Arquitectura de Comunicación

### Flujo de Datos:
```
[Rider App] ←→ [Laravel API] ←→ [Driver App]
                    ↕
              [Admin Panel]
                    ↕
              [MySQL Database]
                    ↕
              [Firestore] (tiempo real)
```

### Protocolos:
- **HTTP/HTTPS:** API REST
- **Firestore Listeners:** Datos en tiempo real
- **OneSignal:** Push notifications

## Infraestructura

### Hosting:
- **VPS Hostinger:** Servidor único
- **MySQL:** Base de datos en VPS
- **Laravel Backend + Admin:** Mismo servidor

### Monitoreo:
- **Google Analytics:** Métricas web
- **Firebase Analytics:** Métricas móviles
- **LoggingService:** Debugging y errores

## Arquitectura de Apps Flutter

### Estructura del Proyecto:
```
taxibal_app/
├── lib/
│   ├── utils/
│   │   ├── Constants.dart          // URLs y configuraciones
│   │   ├── Colors.dart            // Colores del tema
│   │   └── firebase_options.dart  // Config Firebase
│   ├── services/
│   │   ├── logging_service.dart    // Sistema de logs
│   │   ├── api_service.dart       // Comunicación API
│   │   ├── location_service.dart  // GPS
│   │   └── NotificationService.dart // OneSignal
│   ├── models/
│   ├── screens/
│   └── main.dart
├── android/
│   └── app/
│       └── google-services.json
├── ios/
│   └── Runner/
│       └── GoogleService-Info.plist
└── pubspec.yaml
```

### Dependencias Principales:
- **Firebase:** Core, Auth, Firestore, Crashlytics, Analytics
- **Google Maps:** Maps Flutter
- **OneSignal:** Push notifications
- **HTTP:** Comunicación con API
- **Stripe/Razorpay:** Procesamiento de pagos

## Seguridad

### Autenticación:
- **Firebase Auth:** JWT tokens
- **API Keys:** Google Maps, OneSignal
- **HTTPS:** Comunicaciones cifradas

### Datos:
- **Input Validation:** Sanitización de datos
- **Database Security:** Configuración MySQL segura
- **API Rate Limiting:** Protección contra ataques

---

**Nota:** Esta documentación describe únicamente las tecnologías y arquitectura técnica confirmadas del sistema Taxibal.
