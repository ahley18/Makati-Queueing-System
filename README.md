# Makati Queue Management System

A comprehensive queue management system for Makati City Government services with real-time updates, advanced analytics, and administrative controls.

## Features

- 🎫 **Ticket Management**: Create, update, and clear queue tickets with real-time status tracking
- 📊 **Advanced Analytics**: Comprehensive dashboard with charts, trends, and performance metrics
- 🖥️ **Counter Management**: Dynamic counter configuration with status monitoring
- 📈 **Real-time Updates**: WebSocket integration for live status updates across all interfaces
- 🎨 **Modern UI**: Beautiful, responsive interface with dark theme admin dashboard
- 📱 **Mobile Friendly**: Works on all devices and screen sizes
- 📋 **Data Export**: Excel export functionality for ticket history and analytics
- 🔧 **System Controls**: Enable/disable queue system with administrative oversight
- 🕐 **Date Filtering**: Filter analytics and history by specific dates
- 🔄 **Auto-refresh**: Automatic data updates for real-time monitoring

## Service Types

| Counter | Service | Code | Description |
|---------|---------|------|-------------|
| 1 | Business Permit | BP | Business permit applications |
| 2 | Real Estate Tax | RT | Real estate tax payments |
| 3 | Community Tax | CT | Community tax certificates |
| 4 | Health Certificate | HC | Health certificate issuance |
| 5 | VaxCert PH | VP | Vaccination certificate |
| 6 | Makatizen Card (General) | MC | General Makatizen card services |
| 7 | Makatizen Renewal | MR | Card renewal services |
| 8 | Makatizen Biometrics | MB | Biometric data capture |
| 9 | Makatizen Inquiry | MI | General inquiries and follow-ups |
| 10 | Makatizen Card Releasing | CR | Card pickup services |
| 11 | Makatizen GCash Concern | GC | GCash-related issues |

## System Components

### 1. Kiosk Interface (`makati-kiosk-system.html`)
- Public-facing ticket generation system
- Service selection with counter availability
- Real-time queue status display
- Mobile-responsive design

### 2. Queue Display (`queue-display-integrated.html`)
- Public display monitor for queue status
- Real-time updates of current serving tickets
- Counter status indicators
- Professional display interface

### 3. Admin Dashboard (`admin-dashboard.html`)
- **Overview Section**: Real-time statistics and charts
- **Counters Management**: Add, edit, and monitor counters
- **Ticket History**: Complete ticket tracking with filtering
- **Settings**: System controls and user management
- **Analytics**: In-depth performance analysis

### 4. Admin Login (`admin-login.html`)
- Secure authentication system
- Session management
- User access controls

## Quick Start

### Prerequisites

- Web server (Apache, Nginx, or built-in PHP server)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### Installation

1. **Clone or download the repository**
   ```bash
   git clone https://github.com/makati-city/queue-system.git
   cd makati-queue-system
   ```

2. **Set up the web server**
   - Place files in your web server directory
   - Ensure PHP is configured and running
   - Configure MySQL database connection

3. **Set up the database**
   ```sql
   CREATE DATABASE makati_queue_system;
   USE makati_queue_system;
   ```

4. **Configure database connection**
   - Update database credentials in `index.php`
   - Test connection with API endpoints

5. **Start the system**
   - Open `admin-login.html` to access admin panel
   - Open `makati-kiosk-system.html` for ticket generation
   - Open `queue-display-integrated.html` for public display

## API Endpoints

### Core Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `index.php?action=status` | Get all current queue statuses |
| POST | `index.php?action=enqueue` | Add new queue entry |
| PATCH | `index.php?action=status_update` | Update queue status |
| DELETE | `index.php?action=admin_clear` | Clear/complete a ticket |
| GET | `index.php?action=summary` | Get queue summary by counter |

### Admin Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `index.php?action=admin_analytics` | Get comprehensive analytics data |
| GET | `index.php?action=admin_counters` | Get counter information |
| GET | `index.php?action=admin_tickets` | Get all ticket history |
| POST | `index.php?action=add_counter` | Add new counter |
| PATCH | `index.php?action=edit_counter` | Edit counter settings |
| DELETE | `index.php?action=delete_counter` | Delete counter |
| PATCH | `index.php?action=set_system_status` | Enable/disable system |
| POST | `index.php?action=reset_system` | Reset system and clear tickets |
| DELETE | `index.php?action=delete_tickets` | Delete filtered tickets |

## Admin Dashboard Features

### Overview Section
- **Real-time Statistics**: Total tickets, currently serving, waiting, service rate
- **Date Filtering**: Filter overview data by specific dates
- **Hourly Distribution Chart**: Ticket creation patterns throughout the day
- **Performance Charts**: Service performance by counter
- **In-depth Analytics**: Toggle for advanced analytics with additional charts

### Counters Management
- **Counter Status**: Real-time status monitoring (Open/Closed/Break)
- **Counter Configuration**: Add, edit, and delete counters
- **Service Assignment**: Configure counter services and prefixes
- **Icon Support**: Custom emoji icons for each counter
- **Auto-refresh**: Automatic updates every second

### Ticket History
- **Complete History**: View all tickets with detailed information
- **Date Filtering**: Filter tickets by creation date
- **Export Functionality**: Export to Excel (full history or filtered data)
- **Data Management**: Delete filtered tickets with confirmation
- **Wait Time Analysis**: Calculate and display wait times

### Settings
- **User Management**: Update username and password
- **System Controls**: Enable/disable queue system globally
- **System Reset**: Clear all tickets and reset counters
- **Session Management**: Secure logout functionality

## Analytics Features

### Overview Analytics
- **Daily Trends**: 7-day ticket creation trends
- **Service Distribution**: Donut chart showing ticket distribution by service
- **Wait Time Analysis**: Bar chart of wait time categories
- **Peak Hour Analysis**: Hourly ticket creation patterns

### Advanced Analytics
- **Real-time Data**: Live updates from ticket database
- **Interactive Charts**: Chart.js powered visualizations
- **Performance Metrics**: Service efficiency and throughput analysis
- **Export Capabilities**: Excel export for all analytics data

## Data Export Features

### Excel Export Options
- **Full History Export**: Complete ticket database export
- **Filtered Data Export**: Export tickets for specific dates
- **Analytics Export**: Export chart data and statistics
- **Custom Formatting**: Properly formatted Excel files with headers

### Export Formats
- **Ticket History**: Ticket number, service, status, wait time, counter, timestamps
- **Analytics Data**: Chart data with proper formatting
- **Counter Information**: Counter configurations and status

## System Controls

### Global System Status
- **Enable/Disable**: Toggle system-wide queue functionality
- **Visual Indicators**: Clear status banners and notifications
- **Action Blocking**: Prevent queue actions when system is disabled
- **Status Persistence**: System status maintained across sessions

### Data Management
- **Selective Deletion**: Delete tickets by date range
- **System Reset**: Complete system reset with confirmation
- **Data Backup**: Export before deletion operations
- **Audit Trail**: Track all administrative actions

## WebSocket Events

The system uses WebSocket for real-time updates:

- `newTicket`: New ticket created
- `ticketCalled`: Ticket called to counter
- `ticketCompleted`: Ticket completed
- `statusUpdated`: Ticket status updated
- `ticketCleared`: Ticket cleared
- `counterStatusChanged`: Counter status updated
- `systemStatusChanged`: System enable/disable status

## Database Schema

### Core Tables

- **counters**: Counter configurations and status
- **tickets**: Individual ticket records with full history
- **system_settings**: Global system configuration
- **analytics_cache**: Cached analytics data for performance

### Key Fields

- `ticket_code`: Unique ticket identifier (e.g., BP-001)
- `status`: Ticket status (Waiting, Now Serving, Done)
- `counter_number`: Counter assignment (1-11)
- `service_name`: Service type name
- `created_at`: Ticket creation timestamp
- `updated_at`: Last status update timestamp

## Configuration

### Counter Configuration
- **Counter Number**: Unique identifier (1-99)
- **Counter Name**: Service description
- **Counter Prefix**: Service code (BP, RT, CT, etc.)
- **Counter Icon**: Emoji icon for display
- **Enabled Status**: Show/hide in kiosk and display

### System Settings
- **System Enabled**: Global queue system toggle
- **Auto-refresh Intervals**: Real-time update frequencies
- **Session Timeout**: Admin session management
- **Export Settings**: Excel export configurations

## Security Features

### Authentication
- **Session-based**: Secure admin authentication
- **Password Protection**: Encrypted password storage
- **Session Timeout**: Automatic logout for security
- **Access Control**: Admin-only functions protection

### Data Protection
- **Input Validation**: All user inputs validated
- **SQL Injection Protection**: Prepared statements
- **XSS Prevention**: Output sanitization
- **CSRF Protection**: Cross-site request forgery prevention

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check MySQL service is running
   - Verify database credentials in `index.php`
   - Ensure database exists and tables are created

2. **WebSocket Connection Issues**
   - Check browser console for connection errors
   - Verify WebSocket server is running
   - Check firewall settings

3. **Admin Login Issues**
   - Clear browser cache and cookies
   - Check session storage settings
   - Verify admin credentials

4. **Export Functionality**
   - Ensure SheetJS library is loaded
   - Check browser download settings
   - Verify file permissions

### Performance Optimization

1. **Database Optimization**
   - Regular database maintenance
   - Index optimization for large datasets
   - Query optimization for analytics

2. **Caching Strategy**
   - Analytics data caching
   - Counter status caching
   - Session data optimization

## Development

### Local Development Setup

1. **PHP Development Server**
   ```bash
   php -S localhost:8000
   ```

2. **Database Setup**
   ```sql
   -- Create database and tables
   -- Import initial data
   ```

3. **Testing**
   - Test all API endpoints
   - Verify WebSocket connections
   - Check export functionality

### Code Structure

```
makati_queue/
├── index.php                 # Main API backend
├── admin-dashboard.html      # Admin interface
├── admin-login.html         # Admin authentication
├── makati-kiosk-system.html # Public kiosk
├── queue-display-integrated.html # Public display
├── package.json             # Dependencies
└── README.md               # This file
```

## Deployment

### Production Setup

1. **Web Server Configuration**
   - Configure Apache/Nginx for PHP
   - Set up SSL certificates
   - Configure proper file permissions

2. **Database Setup**
   - Production MySQL server
   - Regular backups
   - Performance optimization

3. **Security Measures**
   - HTTPS enforcement
   - Admin access restrictions
   - Regular security updates

### Monitoring

- **System Health**: Regular health checks
- **Performance Monitoring**: Response time tracking
- **Error Logging**: Comprehensive error tracking
- **Usage Analytics**: System usage statistics

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

MIT License - see LICENSE file for details.

## Support

For support and questions, please contact the Makati City IT Department.

---

**Version**: 3.0.0  
**Last Updated**: January 2025  
**Maintainer**: Makati City Government IT Department  
**Features**: Advanced Analytics, Counter Management, Data Export, System Controls 