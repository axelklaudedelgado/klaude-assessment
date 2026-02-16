const crypto = require('crypto');

function verifyWebhook(req, res, next) {
    const hmacHeader = req.get('X-Shopify-Hmac-Sha256') ||
        req.get('x-shopify-hmac-sha256');

    if (!hmacHeader) {
        console.error('[HMAC] Missing HMAC header');
        return res.status(401).json({ error: 'Missing HMAC signature' });
    }

    const rawBody = req.rawBody;

    if (!rawBody) {
        console.error('[HMAC] Missing raw body');
        return res.status(400).json({ error: 'Missing request body' });
    }

    const hash = crypto
        .createHmac('sha256', process.env.SHOPIFY_API_SECRET)
        .update(rawBody, 'utf8')
        .digest('base64');

    const hmacBuffer = Buffer.from(hmacHeader);
    const hashBuffer = Buffer.from(hash);

    if (hmacBuffer.length !== hashBuffer.length) {
        console.error('[HMAC] Verification failed: length mismatch');
        return res.status(401).json({ error: 'Invalid HMAC signature' });
    }

    const isValid = crypto.timingSafeEqual(hmacBuffer, hashBuffer);

    if (!isValid) {
        console.error('[HMAC] Verification failed: signature mismatch');
        return res.status(401).json({ error: 'Invalid HMAC signature' });
    }

    console.log('[HMAC] Verification successful');
    next();
}

module.exports = verifyWebhook;