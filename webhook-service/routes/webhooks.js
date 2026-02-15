const express = require('express');
const router = express.Router();
const db = require('../config/db');
const verifyWebhook = require('../middleware/verifyWebhook');

router.post('/products/update', verifyWebhook, async (req, res) => {
    const topic = 'products/update';
    const shopDomain = req.get('X-Shopify-Shop-Domain') || req.get('x-shopify-shop-domain');
    const webhookId = req.get('X-Shopify-Webhook-Id') || req.get('x-shopify-webhook-id');

    console.log(`[WEBHOOK] Received: ${topic} from ${shopDomain} (ID: ${webhookId})`);

    res.status(200).json({ received: true });

    try {
        const existingCheck = await db.query(
            'SELECT id FROM webhook_events WHERE webhook_id = $1 AND topic = $2',
            [webhookId, topic]
        );

        if (existingCheck.rows.length > 0) {
            console.log(`[WEBHOOK] Duplicate ignored: ${webhookId}`);
            return;
        }

        await db.query(
            `INSERT INTO webhook_events 
       (topic, shop_domain, webhook_id, payload, received_at, processing_status) 
       VALUES ($1, $2, $3, $4, NOW(), $5)`,
            [topic, shopDomain, webhookId, JSON.stringify(req.body), 'pending']
        );

        console.log(`[WEBHOOK] Stored successfully: ${topic} - ${webhookId}`);

        await db.query(
            'UPDATE webhook_events SET processing_status = $1 WHERE webhook_id = $2',
            ['processed', webhookId]
        );

        console.log(`[WEBHOOK] Processed: ${webhookId}`);

    } catch (error) {
        console.error(`[WEBHOOK] Error processing ${webhookId}:`, error.message);

        try {
            await db.query(
                'UPDATE webhook_events SET processing_status = $1 WHERE webhook_id = $2',
                ['failed', webhookId]
            );
        } catch (updateError) {
            console.error('[WEBHOOK] Failed to update status:', updateError.message);
        }
    }
});

router.post('/orders/create', verifyWebhook, async (req, res) => {
    const topic = 'orders/create';
    const shopDomain = req.get('X-Shopify-Shop-Domain') || req.get('x-shopify-shop-domain');
    const webhookId = req.get('X-Shopify-Webhook-Id') || req.get('x-shopify-webhook-id');

    console.log(`[WEBHOOK] Received: ${topic} from ${shopDomain} (ID: ${webhookId})`);

    res.status(200).json({ received: true });

    try {
        const existingCheck = await db.query(
            'SELECT id FROM webhook_events WHERE webhook_id = $1 AND topic = $2',
            [webhookId, topic]
        );

        if (existingCheck.rows.length > 0) {
            console.log(`[WEBHOOK] Duplicate ignored: ${webhookId}`);
            return;
        }

        await db.query(
            `INSERT INTO webhook_events 
       (topic, shop_domain, webhook_id, payload, received_at, processing_status) 
       VALUES ($1, $2, $3, $4, NOW(), $5)`,
            [topic, shopDomain, webhookId, JSON.stringify(req.body), 'pending']
        );

        console.log(`[WEBHOOK] Stored successfully: ${topic} - ${webhookId}`);

        await db.query(
            'UPDATE webhook_events SET processing_status = $1 WHERE webhook_id = $2',
            ['processed', webhookId]
        );

        console.log(`[WEBHOOK] Processed: ${webhookId}`);

    } catch (error) {
        console.error(`[WEBHOOK] Error processing ${webhookId}:`, error.message);

        try {
            await db.query(
                'UPDATE webhook_events SET processing_status = $1 WHERE webhook_id = $2',
                ['failed', webhookId]
            );
        } catch (updateError) {
            console.error('[WEBHOOK] Failed to update status:', updateError.message);
        }
    }
});

module.exports = router;