# Debugging Login Issues

## Steps to Debug

### 1. Check Browser Console (F12)
Open browser DevTools and check:
- **Console tab**: Look for any JavaScript errors
- **Network tab**: 
  - Find the login request (`/api/v1/auth/login`)
  - Check the request payload
  - Check the response status and body
  - Look for CORS errors (red with CORS message)

### 2. Verify API is Running
```bash
cd cnmis-api
php artisan serve
```
Should show: `Laravel development server started: http://127.0.0.1:8000`

### 3. Test API Directly
Test the login endpoint with curl:
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"username":"admin","password":"Admin@123"}'
```

### 4. Check Frontend .env
Make sure `cnmis-frontend/.env` exists and has:
```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

### 5. Check CORS
If you see CORS errors in browser console:
- Make sure Laravel API is running
- Check `cnmis-api/config/cors.php` includes your frontend port
- Restart Laravel server after CORS changes

### 6. Verify User Exists
```bash
cd cnmis-api
php artisan tinker
```
Then run:
```php
User::where('username', 'admin')->first();
```

### 7. Check Laravel Logs
```bash
cd cnmis-api
tail -f storage/logs/laravel.log
```
Then try logging in and watch for errors.

## Common Issues

1. **CORS Error**: API not allowing frontend origin
2. **Network Error**: API server not running
3. **Wrong Credentials**: Username/password mismatch
4. **Validation Error**: Request format incorrect
5. **Database Issue**: User doesn't exist or password hash mismatch

## Quick Test

Run this in browser console after opening the login page:
```javascript
fetch('http://localhost:8000/api/v1/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    username: 'admin',
    password: 'Admin@123'
  })
})
.then(r => r.json())
.then(console.log)
.catch(console.error)
```

This will show you the exact API response.

