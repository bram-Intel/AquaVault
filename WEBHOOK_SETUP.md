# AquaVault Capital - Webhook Setup Guide

## Overview

This guide explains how to set up and configure Paystack webhooks for automatic withdrawal status updates in AquaVault Capital.

## Webhook Events Handled

The system now automatically handles the following Paystack webhook events:

### Payment Events
- `charge.success` - Investment payment successful
- `charge.failed` - Investment payment failed

### Transfer Events (New)
- `transfer.success` - Withdrawal transfer completed successfully
- `transfer.failed` - Withdrawal transfer failed
- `transfer.reversed` - Withdrawal transfer reversed

## Webhook URL Configuration

### Production URL
```
https://aqua.jenniferfan.us/api/webhook.php
```

### Test URL (if using test environment)
```
https://your-test-domain.com/api/webhook.php
```

## Paystack Dashboard Configuration

### 1. Access Paystack Dashboard
1. Log in to your Paystack dashboard
2. Navigate to **Settings** → **Webhooks**

### 2. Add Webhook URL
1. Click **Add Webhook**
2. Enter your webhook URL: `https://aqua.jenniferfan.us/api/webhook.php`
3. Select the following events:
   - `charge.success`
   - `charge.failed`
   - `transfer.success`
   - `transfer.failed`
   - `transfer.reversed`

### 3. Save Configuration
1. Click **Save**
2. Copy the webhook secret key (if provided)
3. Update your `config/paystack.php` if needed

## Security Features

### Webhook Signature Verification
The webhook handler automatically verifies Paystack signatures to prevent unauthorized access:

```php
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
$expected_signature = hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY);

if (!hash_equals($expected_signature, $signature)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit();
}
```

### Error Logging
All webhook events are logged for debugging and monitoring:
- Successful events
- Failed events
- Signature mismatches
- Processing errors

## Automatic Withdrawal Processing

### Transfer Success Flow
1. Admin processes withdrawal → Status: `processing`
2. Paystack initiates transfer
3. Paystack sends `transfer.success` webhook
4. System automatically updates status to `completed`
5. User wallet balance is updated
6. Transaction record is marked as completed

### Transfer Failure Flow
1. Admin processes withdrawal → Status: `processing`
2. Paystack transfer fails
3. Paystack sends `transfer.failed` webhook
4. System automatically updates status to `failed`
5. Failure reason is recorded
6. Transaction record is marked as failed

### Transfer Reversal Flow
1. Transfer is reversed by Paystack
2. Paystack sends `transfer.reversed` webhook
3. System automatically updates status to `failed`
4. If withdrawal was completed, amount is refunded to user wallet
5. Reversal reason is recorded

## Testing Webhook Integration

### Using the Admin Test Tool
1. Access admin panel
2. Navigate to **Test Webhook** (new page)
3. Select event type (success/failed/reversed)
4. Enter transfer code from recent withdrawal
5. Click **Send Test Webhook**
6. Check withdrawal status updates

### Manual Testing
You can also test webhooks manually using curl:

```bash
curl -X POST https://aqua.jenniferfan.us/api/webhook.php \
  -H "Content-Type: application/json" \
  -H "X-Paystack-Signature: YOUR_SIGNATURE" \
  -d '{
    "event": "transfer.success",
    "data": {
      "transfer_code": "TRF_xxxxxxxxxx",
      "reference": "TST_1234567890",
      "amount": 50000,
      "currency": "NGN",
      "status": "success"
    }
  }'
```

## Monitoring and Debugging

### Log Files
Check these locations for webhook activity:
- `error_log` - General system errors
- Server error logs - Webhook processing errors
- Paystack dashboard - Webhook delivery status

### Common Issues

#### 1. Webhook Not Receiving Events
- Verify webhook URL is correct and accessible
- Check Paystack dashboard for delivery status
- Ensure server can receive POST requests
- Verify SSL certificate is valid

#### 2. Signature Verification Failing
- Check `PAYSTACK_SECRET_KEY` in config
- Ensure webhook secret matches Paystack dashboard
- Verify signature header is being sent correctly

#### 3. Withdrawal Status Not Updating
- Check webhook logs for processing errors
- Verify transfer code exists in database
- Ensure withdrawal is in 'processing' status
- Check database connection and permissions

## Benefits of Webhook Integration

### For Admins
- **Automated Processing**: No manual status updates needed
- **Real-time Updates**: Immediate status changes
- **Reduced Workload**: Less manual intervention required
- **Better Tracking**: Complete audit trail of all events

### For Users
- **Faster Updates**: Immediate notification of withdrawal status
- **Better Experience**: Real-time status tracking
- **Transparency**: Clear visibility into withdrawal progress

### For System
- **Reliability**: Automatic handling of all transfer events
- **Consistency**: Standardized status updates
- **Audit Trail**: Complete logging of all webhook events
- **Error Handling**: Graceful handling of failures and reversals

## Production Checklist

Before going live, ensure:

- [ ] Webhook URL is configured in Paystack dashboard
- [ ] All required events are selected
- [ ] Webhook signature verification is enabled
- [ ] SSL certificate is valid and working
- [ ] Error logging is properly configured
- [ ] Test webhook integration works correctly
- [ ] Monitor webhook delivery in Paystack dashboard
- [ ] Set up alerts for webhook failures

## Support

For webhook-related issues:
1. Check error logs first
2. Verify Paystack dashboard webhook status
3. Test webhook integration using admin tool
4. Contact Paystack support for delivery issues
5. Review this guide for troubleshooting steps

## Future Enhancements

Potential improvements for the webhook system:
- Email notifications for status changes
- SMS notifications for critical events
- Webhook retry mechanism
- Advanced monitoring dashboard
- Integration with external monitoring tools
