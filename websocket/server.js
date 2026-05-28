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

      // Handle authentication
      if (message.type === 'auth') {
        clients.set(clientId, { ws, userId: message.userId });
        ws.send(JSON.stringify({
          type: 'authenticated',
          userId: message.userId,
          timestamp: new Date().toISOString()
        }));
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

// Broadcast to all clients
function broadcast(message, excludeClientId = null) {
  clients.forEach((client, id) => {
    if (id !== excludeClientId && client.ws.readyState === WebSocket.OPEN) {
      client.ws.send(JSON.stringify(message));
    }
  });
}

// Broadcast to specific user
function broadcastToUser(userId, message) {
  clients.forEach((client, id) => {
    if (client.userId === userId && client.ws.readyState === WebSocket.OPEN) {
      client.ws.send(JSON.stringify(message));
    }
  });
}

// HTTP endpoint to trigger broadcasts (for Symfony to call)
server.on('request', (req, res) => {
  if (req.url === '/broadcast' && req.method === 'POST') {
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => {
      try {
        const data = JSON.parse(body);
        broadcastToUser(data.userId, {
          type: data.type || 'notification',
          message: data.message,
          data: data.data,
          timestamp: new Date().toISOString()
        });
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

server.listen(PORT, () => {
  console.log(`✅ WebSocket server running on port ${PORT}`);
  console.log(`   WebSocket URL: ws://localhost:${PORT}`);
  console.log(`   HTTP broadcast endpoint: http://localhost:${PORT}/broadcast`);
});