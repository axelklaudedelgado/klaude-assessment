const express = require('express');
require('dotenv').config();

const webhookRoutes = require('./routes/webhooks');
const middleware = require('./middleware/middleware');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.json({
    verify: (req, res, buf) => {
        req.rawBody = buf.toString('utf8');
    }
}));

app.use('/webhooks', webhookRoutes);

app.use(middleware.unknownEndpoint);
app.use(middleware.errorHandler);

app.listen(PORT, () => {
    console.log(`[SERVER] Shopify Webhook Service running on port ${PORT}`);
    console.log(`[SERVER] Products webhook: http://localhost:${PORT}/webhooks/products/update`);
    console.log(`[SERVER] Orders webhook: http://localhost:${PORT}/webhooks/orders/create`);
});