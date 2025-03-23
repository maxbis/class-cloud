# Interactive Classroom Participation System

A real-time interactive system that allows teachers to create collaborative brainstorming sessions where students can submit and visualize bullet points in a cloud-like structure.

## Features

- Teacher dashboard for session control and monitoring
- Student interface for bullet point submission
- Real-time cloud visualization of bullet points
- Keyword-based organization of content
- Secure access control with session codes
- Modern, responsive design

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser with JavaScript enabled

## Installation

1. Clone this repository to your web server directory
2. Create a MySQL database and import the `database.sql` file
3. Configure your database connection in `config/database.php`
4. Set up your web server to point to the project directory
5. Access the application through your web browser

## Directory Structure

```
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
│   └── database.php
├── includes/
│   ├── functions.php
│   └── auth.php
├── teacher/
│   ├── dashboard.php
│   └── session-control.php
├── student/
│   ├── join.php
│   └── submit.php
├── api/
│   └── endpoints.php
└── index.php
```

## Usage

1. Teacher creates a new session and sets an access code
2. Students join using the access code and their name
3. Students submit bullet points
4. Real-time cloud visualization updates automatically
5. Teacher can moderate content and control the session

## Security

- All user inputs are sanitized and validated
- Session-based authentication
- SQL injection prevention
- XSS protection

## License

MIT License 