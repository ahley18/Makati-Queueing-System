# Makati Queue Management System API Documentation

## Base URL
```
http://localhost:3000
```

## Authentication
Currently, the API does not require authentication. In production, consider implementing JWT tokens or API keys.

## API Endpoints

### 1. Get All Current Queue Statuses
**GET** `/status`

Returns the current status of all counters and their active tickets.

**Response:**
```json
{
  "success": true,
  "counters": [
    {
      "counter_number": 1,
      "service_code": "BP",
      "service_name": "Business Permit",
      "code": "BP-001",
      "status": "Now Serving",
      "created_at": "2024-01-15T10:30:00.000Z",
      "called_at": "2024-01-15T10:35:00.000Z",
      "served_at": "2024-01-15T10:36:00.000Z"
    },
    {
      "counter_number": 2,
      "service_code": "RT",
      "service_name": "Real Estate Tax",
      "code": null,
      "status": "Closed",
      "created_at": null,
      "called_at": null,
      "served_at": null
    }
  ]
}
```

**Status Values:**
- `Now Serving`: Ticket is currently being served
- `Called`: Ticket has been called but not yet served
- `Waiting`: Ticket is in queue
- `Completed`: Ticket has been completed
- `Closed`: Counter is closed with no active ticket

---

### 2. Add New Queue Entry
**POST** `/enqueue`

Creates a new ticket for a specific service/counter.

**Request Body:**
```json
{
  "service_type": "Business Permit",
  "counter": 1
}
```

**Response:**
```json
{
  "success": true,
  "ticket_code": "BP-002",
  "message": "Ticket BP-002 created successfully for Business Permit",
  "estimated_wait_time": 15
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Counter not found"
}
```

---

### 3. Update Queue Status
**PATCH** `/status/{counter}`

Updates the status of a ticket at a specific counter.

**Request Body:**
```json
{
  "code": "BP-002",
  "status": "Now Serving"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Ticket BP-002 status updated to Now Serving"
}
```

**Status Values:**
- `waiting`: Ticket is in queue
- `called`: Ticket has been called
- `serving`: Ticket is currently being served
- `completed`: Ticket has been completed

---

### 4. Clear or Close a Ticket
**DELETE** `/clear/{ticket_code}`

Marks a ticket as completed and clears it from the system.

**Response:**
```json
{
  "success": true,
  "message": "Ticket BP-002 completed successfully"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Ticket not found"
}
```

---

### 5. Get Queue Summary by Counter
**GET** `/summary`

Returns a comprehensive summary of queue statistics for all counters.

**Response:**
```json
{
  "success": true,
  "summary": [
    {
      "counter_number": 1,
      "service_name": "Business Permit",
      "service_code": "BP",
      "waiting_count": 5,
      "called_count": 1,
      "serving_count": 1,
      "completed_count": 12,
      "active_count": 7
    },
    {
      "counter_number": 2,
      "service_name": "Real Estate Tax",
      "service_code": "RT",
      "waiting_count": 3,
      "called_count": 0,
      "serving_count": 0,
      "completed_count": 8,
      "active_count": 3
    }
  ],
  "details": [
    {
      "counter_number": 1,
      "ticket_number": "BP-001",
      "status": "serving",
      "created_at": "2024-01-15T10:30:00.000Z",
      "called_at": "2024-01-15T10:35:00.000Z",
      "served_at": "2024-01-15T10:36:00.000Z"
    },
    {
      "counter_number": 1,
      "ticket_number": "BP-002",
      "status": "called",
      "created_at": "2024-01-15T10:40:00.000Z",
      "called_at": "2024-01-15T10:45:00.000Z",
      "served_at": null
    }
  ]
}
```

---

## Service Type Mapping

| Prefix | Service | Counter |
|--------|---------|---------|
| BP | Business Permit | 1 |
| RT | Real Estate Tax | 2 |
| CT | Community Tax | 3 |
| HC | Health Certificate | 4 |
| VP | VaxCert PH | 5 |
| MC | Makatizen Card (General) | 6 |
| MR | Makatizen Renewal | 7 |
| MB | Makatizen Biometrics | 8 |
| MI | Makatizen Inquiry / Follow-ups | 9 |
| CR | Makatizen Card Releasing | 10 |
| GC | Makatizen GCash Concern | 11 |

---

## Additional Endpoints

### Get All Services
**GET** `/api/services`

Returns all available services.

### Get Ticket Status
**GET** `/api/tickets/{ticket_number}/status`

Returns detailed status of a specific ticket.

### Call Next Ticket
**POST** `/api/counters/{counter_number}/call-next`

Calls the next ticket in queue for a specific counter.

### Complete Ticket
**POST** `/api/tickets/{ticket_id}/complete`

Marks a ticket as completed.

### Get Display Data
**GET** `/api/display`

Returns data formatted for display screens.

### Get Analytics
**GET** `/api/analytics?date=2024-01-15`

Returns analytics data for a specific date.

---

## WebSocket Events

The system uses WebSocket for real-time updates:

- `newTicket`: Emitted when a new ticket is created
- `ticketCalled`: Emitted when a ticket is called
- `ticketCompleted`: Emitted when a ticket is completed
- `statusUpdated`: Emitted when ticket status is updated
- `ticketCleared`: Emitted when a ticket is cleared

---

## Error Handling

All endpoints return consistent error responses:

```json
{
  "success": false,
  "error": "Error message description"
}
```

Common HTTP Status Codes:
- `200`: Success
- `400`: Bad Request (missing required fields)
- `404`: Not Found (ticket/counter not found)
- `500`: Internal Server Error

---

## Database Schema

### services
- `id` (INT, PRIMARY KEY)
- `counter_number` (INT, UNIQUE)
- `service_name` (VARCHAR)
- `service_code` (VARCHAR)
- `icon` (VARCHAR)
- `color` (VARCHAR)
- `is_active` (BOOLEAN)
- `created_at` (TIMESTAMP)

### queue_tickets
- `id` (INT, PRIMARY KEY)
- `ticket_number` (VARCHAR, UNIQUE)
- `service_id` (INT, FOREIGN KEY)
- `status` (ENUM: 'waiting', 'called', 'serving', 'completed', 'cancelled')
- `priority` (INT)
- `created_at` (TIMESTAMP)
- `called_at` (TIMESTAMP)
- `served_at` (TIMESTAMP)
- `completed_at` (TIMESTAMP)
- `estimated_wait_time` (INT)
- `actual_wait_time` (INT)

### counter_status
- `id` (INT, PRIMARY KEY)
- `counter_number` (INT, UNIQUE)
- `current_ticket_id` (INT, FOREIGN KEY)
- `staff_id` (VARCHAR)
- `status` (ENUM: 'open', 'closed', 'break')
- `updated_at` (TIMESTAMP)

### queue_analytics
- `id` (INT, PRIMARY KEY)
- `date` (DATE)
- `service_id` (INT, FOREIGN KEY)
- `total_tickets` (INT)
- `avg_wait_time` (INT)
- `peak_hour` (INT)
- `completed_tickets` (INT)
- `cancelled_tickets` (INT)

---

## Usage Examples

### Creating a Ticket
```bash
curl -X POST http://localhost:3000/enqueue \
  -H "Content-Type: application/json" \
  -d '{
    "service_type": "Business Permit",
    "counter": 1
  }'
```

### Updating Ticket Status
```bash
curl -X PATCH http://localhost:3000/status/1 \
  -H "Content-Type: application/json" \
  -d '{
    "code": "BP-001",
    "status": "Now Serving"
  }'
```

### Getting Queue Status
```bash
curl http://localhost:3000/status
```

### Getting Summary
```bash
curl http://localhost:3000/summary
```

### Clearing a Ticket
```bash
curl -X DELETE http://localhost:3000/clear/BP-001
``` 