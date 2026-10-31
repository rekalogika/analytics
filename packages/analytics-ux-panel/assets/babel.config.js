module.exports = {
    presets: [
        ['@babel/preset-env', {
            "modules": false
        }]
    ],
    assumptions: {
        superIsCallableConstructor: false,
    },
};