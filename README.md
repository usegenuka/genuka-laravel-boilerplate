# Genuka Laravel Boilerplate

A Laravel boilerplate for integrating Genuka OAuth authentication and webhook handling into your application.

## Features

- **OAuth 2.0 Integration**: Complete OAuth flow with authorization code exchange
- **JWT Session Management**: Secure session handling with firebase/php-jwt
- **Double Cookie Security**: Session + refresh cookies for secure token refresh
- **Webhook Handling**: Event-driven architecture for Genuka webhooks
- **Company Management**: Database integration with company data synchronization
- **Service Provider Pattern**: Clean architecture with contracts and facades
- **Security**: HMAC signature validation for webhooks and OAuth callbacks
- **Token Encryption**: Secure token storage with Laravel's encryption
- **Error Handling**: Comprehensive error handling and logging
- **Type Safety**: Full PHP type hints and documentation

## Prerequisites

- PHP 8.2 or higher
- Composer
- Laravel 12.x
- Database (SQLite, MySQL, PostgreSQL)
- Genuka account with OAuth credentials

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/genuka/genuka-laravel-boilerplate.git
cd genuka-laravel-boilerplate
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Configure Genuka OAuth

Edit your `.env` file and add your Genuka OAuth credentials:

```env
GENUKA_URL=https://api.genuka.com
GENUKA_CLIENT_ID=your_client_id_here
GENUKA_CLIENT_SECRET=your_client_secret_here
GENUKA_REDIRECT_URI=http://localhost:8000/api/auth/callback
GENUKA_DEFAULT_REDIRECT=/dashboard
GENUKA_ENCRYPT_TOKENS=true
```

You can obtain your OAuth credentials from your Genuka developer dashboard at [https://docs.genuka.com](https://docs.genuka.com).

### 6. Run Database Migrations

```bash
php artisan migrate
```

### 7. Start the Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`.

## Configuration

### Genuka Configuration

All Genuka-related configuration is located in `config/genuka.php`:

```php
return [
    'url' => env('GENUKA_URL', 'https://api.genuka.com'),
    'client_id' => env('GENUKA_CLIENT_ID'),
    'client_secret' => env('GENUKA_CLIENT_SECRET'),
    'redirect_uri' => env('GENUKA_REDIRECT_URI'),
    'default_redirect' => env('GENUKA_DEFAULT_REDIRECT', '/dashboard'),
    'encrypt_tokens' => env('GENUKA_ENCRYPT_TOKENS', true),
];
```

### Database Configuration

By default, the boilerplate uses SQLite for simplicity. To use MySQL or PostgreSQL:

1. Update your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=genuka_laravel
DB_USERNAME=root
DB_PASSWORD=
```

2. Run migrations:

```bash
php artisan migrate
```

## Project Structure

```
genuka-laravel-boilerplate/
├── app/
│   ├── Contracts/
│   │   └── GenukaServiceInterface.php    # Genuka service contract
│   ├── Facades/
│   │   └── Genuka.php                    # Genuka facade
│   ├── Http/Controllers/Auth/
│   │   ├── CallbackController.php        # OAuth callback handler
│   │   ├── CheckController.php           # Auth check endpoint
│   │   ├── LogoutController.php          # Logout handler
│   │   ├── MeController.php              # Current company info
│   │   ├── RefreshController.php         # Session refresh handler
│   │   └── WebhookController.php         # Webhook event handler
│   ├── Models/
│   │   └── Company.php                   # Company model
│   ├── Providers/
│   │   └── GenukaServiceProvider.php     # Genuka service provider
│   └── Services/
│       ├── Auth/
│       │   └── OAuthService.php          # OAuth business logic
│       ├── Genuka/
│       │   └── GenukaService.php         # Genuka API client
│       └── Session/
│           └── SessionService.php        # JWT session management
├── config/
│   └── genuka.php                        # Genuka configuration
├── database/migrations/
│   └── 2025_11_22_024926_create_companies_table.php   # Companies table migration
└── routes/
    └── api.php                           # API routes
```

## Usage

### OAuth 2.0 Flow

This boilerplate implements the **OAuth 2.0 Authorization Code Grant** flow with Genuka. Here's how it works:

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Your App   │     │    Genuka    │     │  Your Server │
│  (Frontend)  │     │   (OAuth)    │     │  (Laravel)   │
└──────┬───────┘     └──────┬───────┘     └──────┬───────┘
       │                    │                    │
       │ 1. User clicks     │                    │
       │    "Install App"   │                    │
       │───────────────────>│                    │
       │                    │                    │
       │ 2. User authorizes │                    │
       │    on Genuka       │                    │
       │<───────────────────│                    │
       │                    │                    │
       │ 3. Redirect to callback with:          │
       │    code, company_id, hmac, timestamp   │
       │────────────────────────────────────────>│
       │                    │                    │
       │                    │ 4. Validate HMAC   │
       │                    │    Exchange code   │
       │                    │<───────────────────│
       │                    │                    │
       │                    │ 5. Return tokens   │
       │                    │───────────────────>│
       │                    │                    │
       │ 6. Redirect with JWT session cookie    │
       │<────────────────────────────────────────│
       │                    │                    │
```

#### Step 1: Initiate OAuth Flow

Direct users to the Genuka authorization URL. This is typically done from the Genuka App Store or your app's install page:

```
https://api.genuka.com/oauth/authorize?
    client_id=YOUR_CLIENT_ID&
    redirect_uri=YOUR_REDIRECT_URI&
    response_type=code&
    scope=read_company,write_orders
```

**Parameters:**
| Parameter | Required | Description |
|-----------|----------|-------------|
| `client_id` | Yes | Your Genuka OAuth client ID |
| `redirect_uri` | Yes | Must match exactly with Genuka dashboard |
| `response_type` | Yes | Always `code` for authorization code flow |
| `scope` | No | Space-separated list of requested permissions |

#### Step 2: User Authorization

The user is presented with Genuka's authorization page where they can:
- Review the permissions your app is requesting
- Select which company to authorize (if they have multiple)
- Approve or deny the authorization

#### Step 3: Handle OAuth Callback

After authorization, Genuka redirects to your callback URL with these parameters:

**Callback URL**: `GET /api/auth/callback`

**Query Parameters:**

| Parameter | Required | Description |
|-----------|----------|-------------|
| `code` | Yes | One-time authorization code (expires in 5 minutes) |
| `company_id` | Yes | ULID of the authorized Genuka company |
| `timestamp` | Yes | Unix timestamp of the request |
| `hmac` | Yes | HMAC-SHA256 signature for security |
| `redirect_to` | Yes | URL-encoded destination after success |

**Example callback URL:**
```
https://yourapp.com/api/auth/callback?
    code=abc123&
    company_id=01hqydxwtxdj3kmzp3bz7jk73g&
    timestamp=1704067200&
    hmac=a1b2c3d4e5f6...&
    redirect_to=https%3A%2F%2Fyourapp.com%2Fdashboard
```

#### Step 4: HMAC Validation (Critical Security)

The callback validates the HMAC signature to prevent tampering. **This is the most critical security step.**

**IMPORTANT: Double URL Encoding**

Genuka uses a specific HMAC calculation that involves double URL encoding for the `redirect_to` parameter:

```php
// Genuka's HMAC calculation (what we must match):
// 1. urlencode(redirect_to) - First encoding
// 2. http_build_query() - Second encoding of the already-encoded value

// In your Laravel code:
$params = [
    'code' => $code,
    'company_id' => $companyId,
    'redirect_to' => $redirectTo, // Keep as received (already URL-encoded)
    'timestamp' => $timestamp,
];

ksort($params); // Sort alphabetically
$queryString = http_build_query($params); // This encodes redirect_to again

$expectedHmac = hash_hmac('sha256', $queryString, $clientSecret);

if (!hash_equals($expectedHmac, $receivedHmac)) {
    throw new Exception('Invalid HMAC signature');
}
```

**Common HMAC Mistakes:**
- Decoding `redirect_to` before validation (breaks double encoding)
- Not sorting parameters alphabetically
- Using wrong client secret
- Not using constant-time comparison (`hash_equals`)

#### Step 5: Token Exchange

After HMAC validation, exchange the authorization code for access tokens:

```php
POST https://api.genuka.com/oauth/token

Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&
code=abc123&
client_id=YOUR_CLIENT_ID&
client_secret=YOUR_CLIENT_SECRET&
redirect_uri=YOUR_REDIRECT_URI
```

**Response:**
```json
{
    "token_type": "Bearer",
    "expires_in": 31536000,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "refresh_token": "def50200..."
}
```

**Note:** `expires_in` is in **seconds** (standard OAuth2).

#### Step 6: Session Creation & Redirect

After successful token exchange:

1. Fetch company info from Genuka API
2. Store company and tokens in database
3. Create JWT session cookies
4. Redirect to the decoded `redirect_to` URL

The callback controller handles all of this automatically:

```php
// CallbackController.php - simplified flow
$company = $this->oauthService->handleCallback(
    code: $code,
    companyId: $companyId,
    timestamp: $timestamp,
    hmac: $hmac,
    redirectTo: $redirectToEncoded // URL-encoded for HMAC
);

$session = $this->sessionService->createSession($company->id);

return redirect(urldecode($redirectTo)) // Decode for actual redirect
    ->withCookie($session['cookies'][0])  // session cookie
    ->withCookie($session['cookies'][1]); // refresh_session cookie
```

### Protecting Routes with Middleware

Use the `auth.genuka` middleware to protect routes that require authentication:

```php
// routes/web.php
Route::middleware(['auth.genuka'])->group(function () {
    Route::get('/', function () {
        $company = request()->attributes->get('genuka_company');
        return view('dashboard', ['company' => $company]);
    });

    Route::get('/settings', [SettingsController::class, 'index']);
});
```

The middleware:
1. Reads the `session` cookie
2. Verifies the JWT signature
3. Fetches the company from database
4. Attaches company to `$request->attributes`
5. Returns 401 if not authenticated

### Using the Genuka Facade

The Genuka facade provides a clean interface to interact with the Genuka API:

```php
use App\Facades\Genuka;

// Set access token and get company information
$company = Genuka::setAccessToken($accessToken)->getCompany($companyId);

// Make custom API requests
$data = Genuka::setAccessToken($accessToken)->get('api/endpoint');

// POST request
$result = Genuka::setAccessToken($accessToken)->post('api/endpoint', [
    'key' => 'value'
]);
```

### Webhook Events

The boilerplate handles the following webhook events:

- `company.updated`: Company information changed
- `company.deleted`: Company was deleted
- `subscription.created`: New subscription created
- `subscription.updated`: Subscription modified
- `subscription.cancelled`: Subscription cancelled
- `payment.succeeded`: Payment processed successfully
- `payment.failed`: Payment processing failed

**Webhook URL**: `POST /api/auth/webhook`

**Webhook Signature**: The webhook controller validates the `X-Genuka-Signature` header using HMAC-SHA256.

#### Implementing Custom Webhook Handlers

Edit `app/Http/Controllers/Auth/WebhookController.php` to add your custom logic:

```php
protected function handleCompanyUpdated(array $event): void
{
    $companyId = $event['data']['id'];

    // Update company in database
    Company::find($companyId)?->update([
        'name' => $event['data']['name'],
        'description' => $event['data']['description'],
    ]);
}
```

### Accessing Company Data

```php
use App\Models\Company;

// Find company by ID
$company = Company::find($companyId);

// Find company by handle
$company = Company::where('handle', $handle)->first();

// Access decrypted access token
$accessToken = $company->access_token;
```

## Security

### Token Encryption

Access tokens are automatically encrypted when stored in the database using Laravel's encryption. This is controlled by the `GENUKA_ENCRYPT_TOKENS` environment variable.

### HMAC Validation

OAuth callbacks validate HMAC signatures to ensure request authenticity. The implementation follows the official Genuka OAuth guide:

**Critical Implementation Details:**

1. Build params array with ALL query parameters (including `redirect_to`)
2. Sort parameters alphabetically by key (`ksort`)
3. Build query string (`http_build_query`)
4. Calculate HMAC SHA-256

```php
// Build params object with ALL query parameters
$params = [
    'code' => $code,
    'company_id' => $companyId,
    'redirect_to' => $redirectTo, // Keep URL-encoded as received
    'timestamp' => $timestamp,
];

// Sort parameters alphabetically by key
ksort($params);

// Build query string
$queryString = http_build_query($params);

// Calculate expected HMAC
$expectedHmac = hash_hmac('sha256', $queryString, config('genuka.client_secret'));

// Compare HMACs in constant time to prevent timing attacks
if (!hash_equals($expectedHmac, $hmac)) {
    throw new \Exception('Invalid HMAC signature');
}
```

**Important Notes:**
- Use `redirect_to` value exactly as received (URL-encoded) for HMAC verification
- Decode `redirect_to` only for the actual HTTP redirect
- Never skip HMAC validation
- Use constant-time comparison (`hash_equals`) to prevent timing attacks

### Timestamp Validation

OAuth callbacks validate that timestamps are within 5 minutes to prevent replay attacks.

## API Endpoints

### Auth Endpoints

| Method | Endpoint             | Auth | Description                     |
| ------ | -------------------- | ---- | ------------------------------- |
| GET    | `/api/auth/callback` | No   | OAuth callback handler          |
| GET    | `/api/auth/check`    | No   | Check if authenticated          |
| POST   | `/api/auth/refresh`  | No   | Refresh expired session         |
| GET    | `/api/auth/me`       | Yes  | Get current company info        |
| POST   | `/api/auth/logout`   | Yes  | Logout and destroy session      |
| POST   | `/api/auth/webhook`  | No   | Webhook event handler           |

## Authentication

### Double Cookie Security Pattern

This boilerplate uses a secure **double cookie pattern** for session management:

| Cookie            | Duration | Purpose                          |
| ----------------- | -------- | -------------------------------- |
| `session`         | 7 hours  | Access protected routes          |
| `refresh_session` | 30 days  | Securely refresh expired sessions |

Both cookies are **HTTP-only** (not accessible via JavaScript) and **signed JWT** (cannot be forged).

### Session Refresh (No Reinstall Required)

When the session expires, the client can securely refresh it:

```
POST /api/auth/refresh
// No body required! The refresh_session cookie is sent automatically
```

**Security Flow:**
1. Client calls `POST /api/auth/refresh` with no body
2. Server reads `refresh_session` cookie (HTTP-only, inaccessible to JS)
3. Server verifies the JWT signature (cannot be forged)
4. Server extracts `companyId` from the verified JWT
5. Server retrieves Genuka `refresh_token` from database
6. Server calls Genuka API with `refresh_token` + `client_secret`
7. Server updates tokens in database
8. Server creates new `session` + `refresh_session` cookies

**Why this is secure:**
- No data sent in request body (nothing to forge)
- `companyId` comes from a signed JWT cookie (tamper-proof)
- Cookies are HTTP-only (not accessible via JavaScript/XSS)
- Genuka `refresh_token` is never exposed to the client
- Genuka API validates with `client_secret` (server-side only)

### Using Session in Controllers

```php
use App\Services\Session\SessionService;

class MyController extends Controller
{
    public function __construct(
        protected SessionService $sessionService
    ) {}

    public function index()
    {
        // Check if authenticated
        if (!$this->sessionService->isAuthenticated()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get current company
        $company = $this->sessionService->getAuthenticatedCompany();

        // Get company ID only
        $companyId = $this->sessionService->getCurrentCompanyId();

        return response()->json($company);
    }
}
```

### Handling 401 Errors (Frontend)

```javascript
async function fetchData() {
    try {
        const response = await fetch('/api/auth/me');
        if (response.status === 401) {
            // Try to refresh the session
            const refreshResponse = await fetch('/api/auth/refresh', {
                method: 'POST',
                credentials: 'include', // Important for cookies
            });

            if (refreshResponse.ok) {
                // Retry the original request
                return await fetch('/api/auth/me');
            } else {
                // Redirect to reinstall
                window.location.href = '/install';
            }
        }
        return response.json();
    } catch (error) {
        console.error('Request failed:', error);
    }
}
```

## Development

### Running Tests

```bash
php artisan test
```

### Code Quality

```bash
# Laravel Pint (code formatting)
./vendor/bin/pint
```


Logs are stored in `storage/logs/laravel.log`.

## Deployment

### Environment Variables

Ensure all required environment variables are set in production:

```env
APP_ENV=production
APP_DEBUG=false
GENUKA_URL=https://api.genuka.com
GENUKA_CLIENT_ID=your_production_client_id
GENUKA_CLIENT_SECRET=your_production_client_secret
GENUKA_REDIRECT_URI=https://yourdomain.com/api/auth/callback
GENUKA_ENCRYPT_TOKENS=true
```

### Optimize for Production

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

## Troubleshooting

### Common Issues

#### 1. "Invalid HMAC signature" Error

**Cause**: Client secret mismatch or timestamp expired.

**Solution**:

- Verify `GENUKA_CLIENT_SECRET` matches your Genuka dashboard
- Check server time is synchronized (NTP)
- Ensure callback happens within 5 minutes

#### 2. "Failed to exchange code for token"

**Cause**: Invalid authorization code or OAuth configuration.

**Solution**:

- Verify `GENUKA_CLIENT_ID` and `GENUKA_CLIENT_SECRET`
- Ensure `GENUKA_REDIRECT_URI` matches exactly with Genuka dashboard
- Check authorization code hasn't been used already


#### 3. Access Token Decryption Fails

**Cause**: `APP_KEY` changed after storing encrypted tokens.

**Solution**:

- Never change `APP_KEY` in production
- If changed, tokens must be re-encrypted or re-obtained

### Debug Mode

Enable debug mode in `.env` for detailed error messages:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

**Warning**: Never enable debug mode in production.

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Resources

- [Genuka Documentation](https://docs.genuka.com)
- [Genuka API Reference](https://api.genuka.com/docs)
- [Laravel Documentation](https://laravel.com/docs)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For issues and questions:

- [GitHub Issues](https://github.com/genuka/genuka-laravel-boilerplate/issues)
- [Genuka Support](https://docs.genuka.com/support)

---

Made with ❤️ for the Genuka developer community
