// websocket/server.js
const WebSocket = require('ws');
const http = require('http');

const PORT = process.env.WS_PORT || 8080;

// Create HTTP server
const server = http.createServer();
const wss = new WebSocket.Server({ server });

// Store connected clients
const clients = new Map();

console.log(`WebSocket server starting on port ${PORT}...`);

wss.on('connection', (ws, req) => {
  const clientId = Date.now();
  clients.set(clientId, { ws, userId: null });
  console.log(`Client ${clientId} connected`);

  // Send welcome message
  ws.send(JSON.stringify({
    type: 'connected',
    clientId: clientId,
    message: 'Connected to Inferno Games WebSocket server',
    timestamp: new Date().toISOString()
  }));

  // Handle incoming messages
  ws.on('message', (data) => {
    try {
      const message = JSON.parse(data);
      console.log(`Received from ${clientId}:`, message);

      if (message.type === 'auth') {
        clients.set(clientId, { ws, userId: message.userId });
        ws.send(JSON.stringify({
          type: 'authenticated',
          userId: message.userId,
          timestamp: new Date().toISOString()
        }));
        console.log(`Client ${clientId} authenticated as user ${message.userId}`);
      }

      // Echo back for testing
      ws.send(JSON.stringify({
        type: 'echo',
        original: message,
        timestamp: new Date().toISOString()
      }));

    } catch (err) {
      console.error('Error parsing message:', err);
    }
  });

  ws.on('close', () => {
    console.log(`Client ${clientId} disconnected`);
    clients.delete(clientId);
  });

  ws.on('error', (err) => {
    console.error(`Client ${clientId} error:`, err);
  });
});

// Broadcast to specific user
function broadcastToUser(userId, message) {
  clients.forEach((client, id) => {
    if (client.userId === userId && client.ws.readyState === WebSocket.OPEN) {
      client.ws.send(JSON.stringify(message));
    }
  });
}

// HTTP endpoint for Symfony to trigger broadcasts
server.on('request', (req, res) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
    res.writeHead(200);
    res.end();
    return;
  }

  if (req.url === '/broadcast' && req.method === 'POST') {
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => {
      try {
        const data = JSON.parse(body);
        
        if (data.userId) {
          broadcastToUser(data.userId, {
            type: data.type || 'notification',
            message: data.message,
            data: data.data,
            timestamp: new Date().toISOString()
          });
        }
        
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ status: 'sent' }));
      } catch (err) {
        res.writeHead(400);
        res.end(JSON.stringify({ error: 'Invalid JSON' }));
      }
    });
  } else {
    res.writeHead(404);
    res.end();
  }
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`✅ WebSocket server running on port ${PORT}`);
  console.log(`   WebSocket URL: ws://0.0.0.0:${PORT}`);
});