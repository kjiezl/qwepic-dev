module.exports = {
    proxy: 'localhost:8000',
    port: 7000,
    files: [
        'templates/**/*.twig',
        'assets/**/*',
        'src/**/*.php',
        'config/**/*'
    ],
    open: true,
    reloadDelay: 300,
    injectChanges: true,
    notify: true,
    browser: ['chrome']
};
