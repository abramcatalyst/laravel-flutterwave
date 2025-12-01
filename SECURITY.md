# Security Policy

## Security Best Practices

This package implements several security measures to protect sensitive data and prevent common vulnerabilities.

### 1. Secret Key Protection

- **Never log secret keys**: All secret keys, passwords, and sensitive data are automatically redacted from logs
- **Environment variables**: Always store credentials in environment variables, never in code
- **Validation**: The package validates that required keys are present before making API requests

### 2. Webhook Security

- **Signature Verification**: All webhooks are verified using `hash_equals()` to prevent timing attacks
- **Secret Hash**: Webhook secret hash is required and validated
- **No CSRF Protection**: Webhook routes are excluded from CSRF protection (webhooks come from external sources)

### 3. Input Validation

- **Endpoint Sanitization**: All API endpoints are sanitized to prevent path traversal attacks
- **SSRF Protection**: Absolute URLs are blocked in endpoint paths
- **Data Validation**: Account numbers, bank codes, and BINs are validated before API calls

### 4. Logging Security

- **Sensitive Data Redaction**: The following fields are automatically redacted from logs:
  - `secret_key`, `secret`, `password`, `pin`, `cvv`
  - `card_number`, `account_number`, `bvn`, `token`
- **Sanitization**: All nested arrays are recursively sanitized

### 5. Error Handling

- **Information Disclosure**: Error messages don't expose sensitive system information
- **Generic Messages**: API failures return generic messages to prevent information leakage

### 6. Configuration Security

- **Default Values**: Sensitive configuration values default to empty strings
- **Environment-Based**: All sensitive data should come from environment variables
- **Validation**: Configuration is validated at service initialization

## Security Recommendations

1. **Use HTTPS**: Always use HTTPS in production environments
2. **Environment Variables**: Store all credentials in `.env` file (never commit to version control)
3. **Webhook Secret**: Set a strong webhook secret hash in your Flutterwave dashboard
4. **Rate Limiting**: Consider implementing rate limiting for webhook endpoints
5. **Logging**: Disable request logging in production (`FLUTTERWAVE_LOG_REQUESTS=false`)
6. **Firewall**: Restrict webhook endpoint access to Flutterwave IP ranges if possible
7. **Regular Updates**: Keep the package and dependencies updated

## Reporting Security Issues

If you discover a security vulnerability, please email **onucheabram@gmail.com** instead of using the issue tracker.

## Security Features Implemented

- ✅ Secret key validation
- ✅ Webhook signature verification with timing attack protection
- ✅ Endpoint path sanitization (path traversal prevention)
- ✅ SSRF protection (blocks absolute URLs)
- ✅ Sensitive data redaction in logs
- ✅ Input validation for financial data
- ✅ Secure error handling
- ✅ Configuration validation

## Known Limitations

- Webhook routes require CSRF exclusion (handled automatically)
- Rate limiting should be implemented at application level
- IP whitelisting should be configured at web server level

