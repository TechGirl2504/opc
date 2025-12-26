# Run Migration to Add `return_reason` Column

The `return_reason` column is missing from the `vetting_records` table. You need to run the migration to add it.

## Run the Migration

Execute this command in your terminal:

```bash
cd cnmis-api
php artisan migrate
```

This will run all pending migrations, including the one that adds the `return_reason` column to the `vetting_records` table.

## Verify the Migration

After running the migration, you can verify the column was added:

```bash
php artisan tinker
```

Then run:
```php
Schema::hasColumn('vetting_records', 'return_reason');
```

This should return `true` if the column exists.

## Alternative: Run Specific Migration

If you only want to run the specific migration:

```bash
php artisan migrate --path=database/migrations/2025_12_27_000000_add_return_reason_to_vetting_records_table.php
```

## After Migration

Once the migration is complete, the send-back functionality should work correctly. Try sending back a vetting record again.

