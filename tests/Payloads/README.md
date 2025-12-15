# Webhook Test Payloads

This directory contains sample webhook payloads for testing.

## Generating Signatures

Use the Artisan command to generate valid HMAC signatures for testing:

### Basic Usage

```bash
# Generate signature with inline JSON
php artisan webhook:signature github '{"event":"test"}'

# Generate signature from file
php artisan webhook:signature github --file=tests/Payloads/github-push.json

# Generate for different providers
php artisan webhook:signature stripe --file=tests/Payloads/stripe-payment.json
php artisan webhook:signature custom '{"event":"user.created"}'
```

### Example Output

```
✓ Signature generated successfully!

Provider:     GitHub (github)
Secret Key:   abc123xyz789...
Header Name:  X-Hub-Signature-256

Signature:
sha256=f7bc83f430538424b13298e6aa6fb143ef4d59a14946175997479dbc2d1a3cd8

Copy and paste the signature above into your HTTP request header.
```

## Using with handle.http

1. Generate the signature:
   ```bash
   php artisan webhook:signature github --file=tests/Payloads/github-push.json
   ```

2. Copy the signature output

3. Paste into `tests/HttpRequests/handle.http`:
   ```http
   POST http://localhost:8000/api/webhooks/github
   X-Hub-Signature-256: sha256=f7bc83f430538424b13298e6aa6fb143ef4d59a14946175997479dbc2d1a3cd8

   {
     "ref": "refs/heads/main",
     ...
   }
   ```

4. **IMPORTANT:** The payload in the HTTP file MUST match EXACTLY the payload used to generate the signature (including whitespace, newlines, etc.)

## Tips

- Always use `--file` option to ensure exact payload matching
- The JSON in your HTTP request must match byte-for-byte with the file used to generate the signature
- Any difference in formatting (spaces, newlines) will cause signature validation to fail
- Run `php artisan db:seed` first to ensure webhook sources exist in the database

## Available Payload Files

- `github-push.json` - GitHub push event
- (Add more as needed)
