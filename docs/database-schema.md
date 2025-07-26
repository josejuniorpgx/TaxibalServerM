# Database Schema Reference

## Core Tables Overview

### Users & Authentication
- `users` - Main user table (riders, drivers, admins)
- `user_details` - Extended info (vehicle data, addresses)

### Ride Management
- `ride_requests` - Core ride system
- `ride_request_histories` - State change tracking
- `ride_request_ratings` - Bidirectional ratings

### Financial System
- `payments` - Completed transactions
- `wallets` - Driver balances
- `wallet_histories` - Transaction audit trail

### Geographic & Services
- `regions` - Service areas with polygon coordinates
- `services` - Transport types per region

### Quality & Support
- `complaints` + `complaint_comments` - Ticket system
- `sos` - Emergency contacts by region

## Key Relationships
- Users can be riders, drivers, or admins
- Rides support scheduling, multiple destinations, bidding
- Financial tracking with full audit trails
