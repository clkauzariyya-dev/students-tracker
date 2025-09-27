# Classroom Public Chat (PHP + MySQL)

## Setup
1. Create database and tables:
   - Run `sql/setup.sql` on your MySQL server.
2. Edit DB credentials in `includes/db.php`.
3. Ensure web server user can write to `uploads/` folder:
   - `mkdir uploads && chmod 755 uploads` (or 775/777 if needed on dev)
4. Point browser to `public/index.php` to start login/register flow.

## How it works
- Login by mobile number. If not present, user registers with name.
- After login, user sees the public chat UI at `student/chat.php`.
- Messages & images are sent to `/api/send_message.php`. Images uploaded via `/api/upload_image.php` (max 5 MB).
- Messages from the current month are shown in real-time via AJAX polling.
- Older months are archived (hidden from main view) and can be viewed using the Archive button.

## Notes
- For true real-time, you can replace polling with WebSocket server (not included).
- Adjust polling interval in `student/chat.php` (`setInterval(fetchMessages, 2000)`).
- Secure the server (HTTPS), sanitize user input further if exposing publicly.
