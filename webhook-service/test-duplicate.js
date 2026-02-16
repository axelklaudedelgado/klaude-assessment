const crypto = require('crypto');
require('dotenv').config();

const secret = process.env.SHOPIFY_API_SECRET;
const body = JSON.stringify({
    id: 123456789,
    title: "Test Product",
    updated_at: "2024-02-15T10:00:00Z"
});

const hmac = crypto
    .createHmac('sha256', secret)
    .update(body, 'utf8')
    .digest('base64');

console.log('Run this command TWICE in PowerShell:');
console.log(`
Invoke-RestMethod -Uri "http://localhost:3000/webhooks/products/update" \`
  -Method POST \`
  -Headers @{
    "Content-Type"="application/json"
    "X-Shopify-Hmac-Sha256"="${hmac}"
    "X-Shopify-Shop-Domain"="test.myshopify.com"
    "X-Shopify-Webhook-Id"="test-duplicate-123"
  } \`
  -Body '${body}'
`);