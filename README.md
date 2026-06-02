# Inseptum — PHP Backend

Lightweight PHP 8.1+ REST API with MySQL. No frameworks, no Composer required.

## Structure

```
backend/
├── index.php                  # Entry point — CORS, env, routing
├── .htaccess                  # Apache rewrite rules
├── .env.example               # Environment template
├── config/
│   └── Database.php           # PDO singleton
├── core/
│   ├── Controller.php         # Base controller (json/error helpers)
│   └── Router.php             # Simple path router with {param} support
├── helpers/
│   └── JwtHelper.php          # HS256 JWT (no dependencies)
├── models/
│   └── User.php               # User queries
├── controllers/
│   └── AuthController.php     # register / login / me / logout
└── migrations/
    └── 001_create_users.sql   # Initial schema
```

## Quick Start

### 1. Copy and fill the env file
```bash
cp .env.example .env
# Edit DB_*, JWT_SECRET, CORS_ALLOWED_ORIGINS
```

### 2. Run the migration
```bash
mysql -u root -p < migrations/001_create_users.sql
```

### 3. Point your web server at `backend/`

**Apache** — `.htaccess` is already configured.

**PHP built-in server (dev only)**
```bash
php -S localhost:8000 -t backend
```

---

## API Endpoints

All responses are `application/json`.

### `POST /api/auth/register`
```json
// Request
{ "name": "Alice", "email": "alice@example.com", "password": "secret123" }

// 201 Response
{ "message": "Registration successful", "token": "<jwt>", "user": { ... } }
```

### `POST /api/auth/login`
```json
// Request
{ "email": "alice@example.com", "password": "secret123" }

// 200 Response
{ "message": "Login successful", "token": "<jwt>", "user": { ... } }
```

### `GET /api/auth/me`
```
Authorization: Bearer <token>
```
```json
// 200 Response
{ "user": { "id": 1, "name": "Alice", "email": "alice@example.com", "created_at": "..." } }
```

### `POST /api/auth/logout`
```json
// 200 Response
{ "message": "Logged out successfully" }
// Client must discard the token (stateless JWT)
```

### `GET /api/health`
```json
{ "status": "ok", "time": "2024-01-01T00:00:00+00:00" }
```

---

## Adding a New Resource

1. Create `models/Post.php` extending nothing (plain class using `Database::getInstance()`).
2. Create `controllers/PostController.php` extending `App\Core\Controller`.
3. Register routes in `index.php`:
   ```php
   $router->get('/api/posts',        [PostController::class, 'index']);
   $router->post('/api/posts',       [PostController::class, 'store']);
   $router->get('/api/posts/{id}',   [PostController::class, 'show']);
   $router->put('/api/posts/{id}',   [PostController::class, 'update']);
   $router->delete('/api/posts/{id}',[PostController::class, 'destroy']);
   ```

## Auth Middleware Pattern

To protect a route, verify the JWT at the top of any controller method:

```php
public function store(array $params = []): void
{
    $token   = $this->jwt->fromHeader();
    $payload = $token ? $this->jwt->verify($token) : null;
    if (!$payload) {
        $this->error('Unauthorized', 401);
    }
    // $payload['sub'] = user ID
    ...
}
```

Or extract it into a `protected function requireAuth(): array` helper in `Controller.php`.
