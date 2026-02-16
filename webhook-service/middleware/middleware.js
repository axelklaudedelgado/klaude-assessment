const unknownEndpoint = (req, res) => {
    res.status(404).json({
        error: 'Endpoint not found',
        path: req.path,
        method: req.method
    });
};

const errorHandler = (err, req, res, next) => {
    console.error('[ERROR] Server error:', err.message);
    console.error('[ERROR] Stack:', err.stack);

    res.status(500).json({
        error: 'Internal server error',
        message: process.env.NODE_ENV === 'development' ? err.message : undefined
    });
};

module.exports = {
    unknownEndpoint,
    errorHandler
};