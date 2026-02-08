# A+SIS - Student Information Management System

## Overview
A+SIS is a robust, secure, and user-friendly Student Information Management System with role-based access control. The system supports multiple user roles including System Admin, Registrar Officer, Teacher, Student, and Transcript Officer.

## Recent Enhancements

### 🗄️ Database Migration (MySQL → SQLite)
- **Converted from MySQL to SQLite** for lightweight, portable deployment
- **No external database server required** - embedded database solution
- **Enhanced data integrity** with foreign key constraints and CHECK constraints
- **Optimized performance** with strategic indexes on frequently queried columns

### 🔒 Security Enhancements

#### Authentication & Session Management
- **Session timeout** - Automatic logout after 30 minutes of inactivity
- **Client-side warnings** - Users warned 2 minutes before session expires
- **Rate limiting** - Maximum 5 failed login attempts within 15 minutes
- **Account lockout** - Temporary lockout after excessive failed attempts
- **Secure session regeneration** on successful login
- **CSRF protection** - Token-based validation for all state-changing operations

#### Audit & Tracking
- **Comprehensive audit logging** - All user actions tracked with:
  - User ID, action type, entity details
  - IP address and user agent information
  - Timestamp for compliance reporting
- **Login attempt tracking** - Monitor failed and successful login attempts
- **User session tracking** - Track active sessions per user

#### Permission System
- **Role-based access control** with hierarchical permissions
- **Role verification** on protected pages
- **Active user checks** - Deactivated accounts cannot log in

### 🎨 User Experience Improvements

#### Accessibility (WCAG 2.1 Compliant)
- **ARIA labels and roles** throughout the application
- **Semantic HTML** with proper heading hierarchy
- **Skip navigation link** for keyboard users
- **Focus management** with visible focus indicators
- **Screen reader support** with live regions for dynamic content
- **Keyboard navigation** fully supported with shortcuts

#### Mobile Optimization
- **Responsive design** adapts to all screen sizes
- **Touch-friendly** interface with appropriate target sizes
- **Mobile-first CSS** with progressive enhancement
- **Optimized layouts** for mobile, tablet, and desktop

#### Enhanced Feedback
- **Toast notifications** for user actions (success, error, warning, info)
- **Loading states** with spinners during async operations
- **Form validation** with real-time client-side feedback
- **Error messages** with helpful, actionable information

### 📊 Data Management Features

#### Table Functionality
- **Search/Filter** - Real-time table search across all columns
- **Sortable columns** - Click headers to sort ascending/descending
- **Export to CSV** - Download data for external analysis
- **Responsive tables** - Optimized display on mobile devices

#### Form Validation
- **Client-side validation** - Instant feedback on form inputs
- **Server-side validation** - Security through double validation
- **Pattern matching** - Email, phone, and custom format validation
- **Required field indicators** - Clear visual cues

### 🎨 UI/UX Consistency

#### Design System
- **Consistent color palette** with CSS custom properties
- **Reusable components** - Buttons, cards, modals, badges
- **Smooth animations** for state transitions
- **Visual hierarchy** with proper spacing and typography

#### Components
- **Modal dialogs** with focus trapping and keyboard support
- **Toast notifications** with auto-dismiss
- **Loading spinners** for async operations
- **Status badges** with semantic colors (success, error, warning, info)

## Technical Stack

- **Backend**: PHP 8.3+ with strict types
- **Database**: SQLite 3 (embedded)
- **Frontend**: Vanilla JavaScript (ES6+)
- **Styling**: Modern CSS with custom properties
- **Architecture**: MVC-inspired with separation of concerns

## File Structure

```
SIMS/
├── api/                    # API endpoints
│   ├── login.php          # Authentication endpoint
│   ├── logout.php         # Logout endpoint
│   └── whoami.php         # Current user info
├── assets/                # Static assets
│   ├── styles.css         # Enhanced responsive styles
│   ├── app.js            # Login page scripts
│   ├── dashboard.js      # Dashboard functionality
│   └── utils.js          # Reusable utilities
├── lib/                   # Core libraries
│   ├── db.php            # Database & security functions
│   └── utils.php         # Export & helper functions
├── data/                  # SQLite database (auto-created)
│   └── sims.db           # Main database file
├── config.php            # Application configuration
├── index.html            # Login page
├── dashboard.php         # Main dashboard
├── users.php             # User management (demo)
└── .gitignore           # Git ignore rules
```

## Database Schema

### Tables

#### users
- User accounts with roles and authentication
- Fields: id, username, password_hash, role, full_name, email, is_active, created_at, last_login, updated_at
- Constraints: UNIQUE username, CHECK role validation

#### audit_logs
- Complete audit trail of user actions
- Fields: id, user_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at
- Indexes: user_id, created_at

#### login_attempts
- Login attempt tracking for security
- Fields: id, username, ip_address, success, attempted_at
- Indexes: username, attempted_at

#### user_sessions
- Active session tracking
- Fields: id, user_id, session_id, ip_address, user_agent, last_activity, created_at
- Indexes: user_id

## Configuration

Edit `config.php` to customize:

```php
return [
    'db_path' => __DIR__ . '/data/sims.db',
    'session_timeout' => 1800,        // 30 minutes
    'max_login_attempts' => 5,         // Max attempts
    'lockout_duration' => 900,         // 15 minutes
];
```

## Security Features

### Password Security
- Passwords hashed using PHP's `password_hash()` with bcrypt
- Minimum 6 characters (configurable)
- No password stored in plain text

### Session Security
- Session regeneration on login
- HTTP-only cookies
- Secure flag for HTTPS
- Session timeout with activity tracking

### Input Validation
- All user inputs sanitized
- SQL injection prevention via prepared statements
- XSS prevention via output escaping
- CSRF token validation

## Installation

1. **Requirements**
   - PHP 8.3 or higher
   - SQLite3 extension enabled
   - Web server (Apache, Nginx, or PHP built-in)

2. **Setup**
   ```bash
   # Clone or download the repository
   cd SIMS
   
   # Start PHP development server
   php -S localhost:8000
   ```

3. **First Login**
   - URL: `http://localhost:8000`
   - Username: `admin`
   - Password: `admin123`

4. **Change Default Password**
   - Immediately change the default admin password after first login

## Usage

### For System Administrators
- Manage user accounts and roles
- View system audit logs
- Export data for compliance reporting
- Monitor login attempts and security

### For Registrar Officers
- Manage student records
- Process admissions
- Update enrollment status

### For Teachers
- View class rosters
- Enter and manage grades
- Track student attendance

### For Students
- View enrolled courses
- Check grades and GPA
- Access academic records

### For Transcript Officers
- Generate official transcripts
- Verify academic records
- Process transcript requests

## API Endpoints

### POST /api/login.php
Authenticate user and create session
```javascript
{
  "username": "admin",
  "password": "admin123"
}
```

### POST /api/logout.php
Destroy current session

### GET /api/whoami.php
Get current user information

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Accessibility

This application follows WCAG 2.1 Level AA guidelines:
- Keyboard navigation supported
- Screen reader compatible
- Sufficient color contrast (4.5:1 minimum)
- Focus indicators visible
- Error identification and recovery

## Performance

- Optimized database queries with indexes
- Minimal JavaScript dependencies
- Efficient CSS with modern features
- Lazy loading where applicable

## Future Enhancements

- [ ] Two-factor authentication (2FA)
- [ ] Advanced reporting with charts
- [ ] Email notifications
- [ ] Document upload/management
- [ ] Bulk user import/export
- [ ] Advanced permission granularity
- [ ] API rate limiting
- [ ] Real-time notifications with WebSockets

## License

Copyright © 2024 A+SIS. All rights reserved.

## Support

For issues or questions, please contact your system administrator.
