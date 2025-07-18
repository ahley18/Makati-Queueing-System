# Queue Management System Backend - Quick Start Guide

## 🚀 Getting Started

### Prerequisites
- Node.js 16+ 
- MySQL 8.0+
- Redis 6+
- Docker (optional)

### Option 1: Local Development Setup

1. **Clone and Install**
```bash
git clone https://github.com/makati-city/queue-backend.git
cd queue-backend
npm install
```

2. **Configure Environment**
```bash
cp .env.example .env
# Edit .env with your database credentials
```

3. **Setup Database**
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE makati_queue_system;"

# Run migrations (automatic on server start)
npm start
```

4. **Start Services**
```bash
# Start Redis
redis-server

# Start the backend
npm run dev
```

### Option 2: Docker Setup

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f backend

# Stop services
docker-compose down
```

---

## 📡 Frontend Integration

### Connect Kiosk UI to Backend

1. **Update Kiosk Frontend Configuration**
```javascript
// In your kiosk HTML file, add:
<script src="https://cdn.socket.io/4.6.1/socket.io.min.js"></script>
<script>
  const API_URL = 'http://localhost:3000';
  const socket = io(API_URL);
  
  // Update generateTicket function
  async function generateTicket(service, counter) {
    try {
      const response = await fetch(`${API_URL}/api/tickets/create`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ serviceId: counter })
      });
      
      const data = await response.json();
      if (data.success) {
        showTicketModal(data.ticket);
      }
    } catch (error) {
      console.error('Error creating ticket:', error);
    }
  }
  
  // Listen for real-time updates
  socket.on('ticketCalled', (data) => {
    // Update display when tickets are called
    updateQueueDisplay(data);
  });
</script>
```

2. **Update Display Monitor**
```javascript
// For the queue display screen
async function refreshDisplay() {
  const response = await fetch(`${API_URL}/api/display`);
  const data = await response.json();
  
  if (data.success) {
    updateCounterDisplay(data.counters);
  }
}

// Refresh every 5 seconds
setInterval(refreshDisplay, 5000);
```

---

## 🖥️ Counter Staff Interface

### Basic HTML Interface for Staff
```html
<!DOCTYPE html>
<html>
<head>
  <title>Counter Staff - Queue Management</title>
</head>
<body>
  <h1>Counter <span id="counterNumber">1</span></h1>
  <div id="currentTicket">No ticket serving</div>
  
  <button onclick="callNext()">Call Next</button>
  <button onclick="completeService()">Complete Service</button>
  
  <script>
    const counterNumber = 1; // Set your counter number
    const staffId = 'STAFF001'; // Set staff ID
    
    async function callNext() {
      const response = await fetch(`/api/counters/${counterNumber}/call-next`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ staffId })
      });
      
      const data = await response.json();
      if (data.ticket) {
        document.getElementById('currentTicket').innerText = 
          `Serving: ${data.ticket.ticket_number}`;
      }
    }
    
    async function completeService() {
      // Implementation for completing service
    }
  </script>
</body>
</html>
```

---

## 🔧 Common Tasks

### Check System Health
```bash
curl http://localhost:3000/health
```

### View Real-time Logs
```bash
# Docker
docker-compose logs -f backend

# Local
tail -f logs/combined.log
```

### Reset Daily Counters
```bash
# Connect to Redis
redis-cli
> KEYS ticket_counter:*
> FLUSHDB  # Careful: This clears all Redis data
```

### Database Queries
```sql
-- View today's tickets
SELECT * FROM queue_tickets 
WHERE DATE(created_at) = CURDATE() 
ORDER BY created_at DESC;

-- Check counter status
SELECT cs.*, s.service_name, qt.ticket_number 
FROM counter_status cs
JOIN services s ON cs.counter_number = s.counter_number
LEFT JOIN queue_tickets qt ON cs.current_ticket_id = qt.id;

-- Daily statistics
SELECT 
  s.service_name,
  COUNT(qt.id) as total_tickets,
  AVG(qt.actual_wait_time) as avg_wait_time
FROM queue_tickets qt
JOIN services s ON qt.service_id = s.id
WHERE DATE(qt.created_at) = CURDATE()
GROUP BY s.id;
```

---

## 📊 Monitoring & Analytics

### Setup Monitoring Dashboard
```javascript
// analytics-dashboard.html
async function loadAnalytics() {
  const response = await fetch('/api/analytics?date=' + today);
  const data = await response.json();
  
  // Display charts using Chart.js or similar
  displayServiceStats(data.analytics);
}
```

### Export Daily Reports
```bash
# Add to cron for daily exports
node scripts/export-daily-report.js
```

---

## 🚨 Troubleshooting

### Common Issues

1. **Port Already in Use**
```bash
# Find process using port 3000
lsof -i :3000
# Kill process
kill -9 <PID>
```

2. **Database Connection Failed**
- Check MySQL is running: `systemctl status mysql`
- Verify credentials in `.env`
- Check database exists

3. **Redis Connection Failed**
- Check Redis is running: `redis-cli ping`
- Verify Redis configuration

4. **WebSocket Not Connecting**
- Check CORS settings
- Ensure nginx/proxy supports WebSocket upgrade
- Check firewall rules

---

## 🔐 Security Checklist

- [ ] Change default database passwords
- [ ] Set strong JWT_SECRET in production
- [ ] Enable HTTPS with valid SSL certificates
- [ ] Configure firewall rules
- [ ] Set up rate limiting
- [ ] Enable logging and monitoring
- [ ] Regular database backups
- [ ] Update dependencies regularly

---

## 📞 Support

- **Documentation**: `/docs`
- **API Status**: `/health`
- **Logs**: Check `/logs` directory
- **Issues**: Submit via GitHub Issues

---

## 🎯 Next Steps

1. **Customize Services**: Edit service configuration in database
2. **Add Authentication**: Implement staff login system
3. **Enable SMS Notifications**: Integrate SMS gateway
4. **Setup Monitoring**: Add Prometheus/Grafana
5. **Deploy to Production**: Use PM2 or systemd for process management