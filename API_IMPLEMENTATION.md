# REST API Implementation - Complete

A production-ready REST API has been successfully implemented for the Numz.ai application with full WHMCS-style functionality.

## Implementation Summary

### 1. API Controllers (`/app/Http/Controllers/Api/`)
- **ApiAuthController.php** - Authentication, login, register, API key management
- **ApiClientController.php** - Client/user management (CRUD operations)
- **ApiServiceController.php** - Hosting service management with lifecycle actions
- **ApiInvoiceController.php** - Invoice management and payment processing
- **ApiDomainController.php** - Domain registration, renewal, and transfer
- **ApiTicketController.php** - Support ticket management and replies
- **ApiProductController.php** - Product catalog retrieval

### 2. API Resources (`/app/Http/Resources/`)
- **ClientResource.php** - Client data transformation
- **ServiceResource.php** - Service data with relationships
- **InvoiceResource.php** - Invoice with items and calculations
- **InvoiceItemResource.php** - Individual invoice items
- **DomainResource.php** - Domain registration data
- **TicketResource.php** - Support ticket with replies
- **TicketReplyResource.php** - Individual ticket replies
- **ProductResource.php** - Product catalog data

### 3. API Middleware (`/app/Http/Middleware/`)
- **ApiLogger.php** - Logs all API requests to database
- **ApiVersionCheck.php** - API version validation
- **AuthenticateApiKey.php** - API key authentication (existing)

### 4. Webhooks System
- **Webhook.php** (Model) - Webhook subscriptions
- **WebhookDelivery.php** (Model) - Webhook delivery tracking
- **WebhookService.php** - Webhook dispatch and retry logic
- **Migration** - Database tables for webhooks

### 5. API Routes (`/routes/api.php`)
All routes prefixed with `/api/v1` and protected with:
- API key authentication (`api.key` middleware)
- Rate limiting (60 requests/minute)
- Request logging (`ApiLogger` middleware)

**Endpoint Categories:**
- Authentication: Login, register, API key management
- Clients: List, create, view, update, delete
- Services: List, create, activate, suspend, terminate, upgrade
- Invoices: List, create, pay, cancel, download PDF
- Domains: List, register, renew, transfer
- Tickets: List, create, reply, close, reopen
- Products: List, view catalog

### 6. Interactive Documentation (`/api/docs`)
A beautiful, interactive API documentation page accessible at `/api/docs` featuring:
- Quick navigation to all endpoint sections
- Complete endpoint reference with examples
- Request/response samples
- Code examples in PHP, Python, JavaScript, cURL
- Webhook event documentation
- Error code reference
- Authentication guide
- Rate limiting information

## Features Implemented

### Authentication
- Bearer token authentication (Sanctum)
- API key authentication (via X-API-Key header)
- API key management endpoints
- Secure key generation

### Rate Limiting
- 60 requests per minute per user
- Rate limit headers in responses
- 429 status code when limit exceeded

### Request Logging
- All API requests logged to database
- Tracks endpoint, method, status code, response time
- Sanitized request/response data
- User tracking

### Error Handling
- Consistent error response format
- HTTP status codes (200, 201, 400, 401, 404, 500)
- Detailed error messages
- Validation errors

### Webhooks
- Event-based notifications
- HMAC signature verification
- Automatic retry with exponential backoff
- Delivery tracking and logging

**Available Events:**
- invoice.created, invoice.paid, invoice.overdue, invoice.cancelled
- service.created, service.activated, service.suspended, service.terminated, service.upgraded
- domain.registered, domain.renewed, domain.transferred
- ticket.created, ticket.replied, ticket.closed
- client.created, client.updated
- payment.received, payment.failed

### Pagination
- All list endpoints support pagination
- Configurable items per page
- Metadata included in responses

### Filtering & Search
- Search clients by name, email, company
- Filter services by user, status
- Filter invoices by user, status
- Filter domains by user, status
- Filter tickets by user, status, department

### Data Relationships
- Resources include related data when loaded
- Efficient eager loading
- Nested resource transformation

## API Usage Examples

### Authentication
```bash
# Login
curl -X POST 'https://yourdomain.com/api/v1/auth/login' \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.com","password":"password"}'

# Create API Key
curl -X POST 'https://yourdomain.com/api/v1/auth/keys' \
  -H 'X-API-Key: your_api_key' \
  -H 'Content-Type: application/json' \
  -d '{"name":"Production Key"}'
```

### Client Management
```bash
# List Clients
curl -X GET 'https://yourdomain.com/api/v1/clients?per_page=20&search=john' \
  -H 'X-API-Key: your_api_key'

# Create Client
curl -X POST 'https://yourdomain.com/api/v1/clients' \
  -H 'X-API-Key: your_api_key' \
  -H 'Content-Type: application/json' \
  -d '{
    "name":"John Doe",
    "email":"john@example.com",
    "password":"secure_password",
    "company_name":"Acme Inc"
  }'
```

### Service Operations
```bash
# Create Service
curl -X POST 'https://yourdomain.com/api/v1/services' \
  -H 'X-API-Key: your_api_key' \
  -H 'Content-Type: application/json' \
  -d '{
    "user_id":1,
    "hosting_product_id":1,
    "domain":"example.com",
    "billing_cycle":"monthly",
    "price":9.99
  }'

# Activate Service
curl -X POST 'https://yourdomain.com/api/v1/services/1/activate' \
  -H 'X-API-Key: your_api_key'
```

### Invoice Management
```bash
# Create Invoice
curl -X POST 'https://yourdomain.com/api/v1/invoices' \
  -H 'X-API-Key: your_api_key' \
  -H 'Content-Type: application/json' \
  -d '{
    "user_id":1,
    "due_date":"2024-12-31",
    "items":[
      {"description":"Hosting","quantity":1,"unit_price":9.99}
    ]
  }'

# Mark as Paid
curl -X POST 'https://yourdomain.com/api/v1/invoices/1/pay' \
  -H 'X-API-Key: your_api_key' \
  -H 'Content-Type: application/json' \
  -d '{"payment_method":"stripe","transaction_id":"txn_123"}'
```

## Database Migrations Required

Run the following command to create webhook tables:
```bash
php artisan migrate
```

This will create:
- `webhooks` table
- `webhook_deliveries` table

## Security Considerations

1. **API Keys**: Store securely, regenerate if compromised
2. **HTTPS**: All API requests must use HTTPS in production
3. **Rate Limiting**: Prevents abuse, 60 req/min default
4. **Input Validation**: All inputs validated before processing
5. **Webhook Signatures**: Verify using HMAC SHA256

## Testing the API

1. **Access Documentation**: Visit `/api/docs` in your browser
2. **Create API Key**: Login → Create API key via POST `/api/v1/auth/login`
3. **Test Endpoints**: Use Postman, cURL, or code examples
4. **Monitor Logs**: Check `api_usage_logs` table for request tracking

## Response Format

All API responses follow this structure:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

Error responses:
```json
{
  "success": false,
  "error": "Error type",
  "message": "Detailed error message"
}
```

## Next Steps

1. Run migrations: `php artisan migrate`
2. Access API docs: Visit `https://yourdomain.com/api/docs`
3. Create your first API key via login endpoint
4. Test endpoints using the documentation
5. Set up webhooks for real-time notifications
6. Integrate with your applications

## Files Created

### Controllers (7 files)
- `/app/Http/Controllers/Api/ApiAuthController.php`
- `/app/Http/Controllers/Api/ApiClientController.php`
- `/app/Http/Controllers/Api/ApiServiceController.php`
- `/app/Http/Controllers/Api/ApiInvoiceController.php`
- `/app/Http/Controllers/Api/ApiDomainController.php`
- `/app/Http/Controllers/Api/ApiTicketController.php`
- `/app/Http/Controllers/Api/ApiProductController.php`
- `/app/Http/Controllers/ApiDocumentationController.php`

### Resources (8 files)
- `/app/Http/Resources/ClientResource.php`
- `/app/Http/Resources/ServiceResource.php`
- `/app/Http/Resources/InvoiceResource.php`
- `/app/Http/Resources/InvoiceItemResource.php`
- `/app/Http/Resources/DomainResource.php`
- `/app/Http/Resources/TicketResource.php`
- `/app/Http/Resources/TicketReplyResource.php`
- `/app/Http/Resources/ProductResource.php`

### Middleware (2 files)
- `/app/Http/Middleware/ApiLogger.php`
- `/app/Http/Middleware/ApiVersionCheck.php`

### Models (2 files)
- `/app/Models/Webhook.php`
- `/app/Models/WebhookDelivery.php`

### Services (1 file)
- `/app/Services/WebhookService.php`

### Views (1 file)
- `/resources/views/api/docs.blade.php`

### Migrations (1 file)
- `/database/migrations/2024_01_01_000001_create_webhooks_table.php`

### Configuration
- Updated `/routes/api.php` with new API routes
- Updated `/routes/web.php` with documentation route
- Updated `/bootstrap/app.php` with middleware aliases

## Support

For questions or issues with the API:
- Review the interactive documentation at `/api/docs`
- Check the code examples provided
- Verify API key is valid and has proper permissions
- Check rate limiting headers if requests fail
- Review API usage logs in database

---

**Implementation Date:** {{ date('Y-m-d') }}
**API Version:** v1
**Status:** Production Ready ✓
