module.exports = {
  apps: [{
    name: 'inferno-websocket',
    script: 'server.js',
    instances: 1,
    autorestart: true,
    watch: false,
    env: {
      WS_PORT: 8080
    }
  }]
};