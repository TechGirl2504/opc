# Add Missing `sent_back` Vetting Status

The `sent_back` vetting status is required for the send-back functionality but may be missing from your database.

## Option 1: Run the Seeder (Recommended)

Run the VettingStatusSeeder to add all missing statuses:

```bash
cd cnmis-api
php artisan db:seed --class=VettingStatusSeeder
```

## Option 2: Manual Database Insert

If you prefer to add it manually, run this SQL:

```sql
INSERT INTO vetting_statuses (name, code, description, is_active, created_at, updated_at)
VALUES ('Sent Back', 'sent_back', 'Vetting has been sent back for clarifications', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Sent Back', description = 'Vetting has been sent back for clarifications', is_active = 1;
```

## Option 3: Use Tinker

```bash
cd cnmis-api
php artisan tinker
```

Then run:
```php
App\Models\VettingStatus::firstOrCreate(
    ['code' => 'sent_back'],
    [
        'name' => 'Sent Back',
        'description' => 'Vetting has been sent back for clarifications',
        'is_active' => true,
    ]
);
```

## Verification

After adding the status, verify it exists:

```bash
php artisan tinker
```

Then:
```php
App\Models\VettingStatus::where('code', 'sent_back')->first();
```

You should see the status record.

