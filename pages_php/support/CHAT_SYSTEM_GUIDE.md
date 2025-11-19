# Live Chat & Support Ticket Management System

## Overview
This is a comprehensive live chat and support ticket management system for the VVU SRC Management System. It allows admins and super admins to manage support requests through real-time chat sessions with students and other users.

## Features

### For Administrators & Super Admins:
- **Agent Status Management**: Set your availability status (Online, Busy, Away, Offline)
- **Chat Session Management**: View and manage all active, waiting, and historical chat sessions
- **Real-time Notifications**: Get notified when new chat sessions are created
- **Auto-Assignment**: Automatically assign waiting chats to available agents
- **Quick Responses**: Use pre-defined templates for common responses
- **Session Monitoring**: Track session details, priority levels, and customer information
- **Multi-chat Support**: Handle multiple concurrent chat sessions

### For Students & Users:
- **Live Chat**: Instant communication with support agents
- **Session History**: View past chat conversations
- **Priority Levels**: Mark urgent requests for faster response
- **Real-time Updates**: See when agents are typing and when messages are read

## Installation

### Step 1: Database Setup
1. Navigate to `pages_php/support/initialize_chat_system.php` in your browser
2. Click the "Initialize Chat System" button
3. Wait for the setup to complete successfully

**OR** manually run the SQL script:
- Import `sql/chat_system_tables.sql` into your database

### Step 2: Verify Installation
- Check that the following tables were created:
  - `chat_sessions`
  - `chat_messages`
  - `chat_participants`
  - `chat_agent_status`
  - `chat_quick_responses`
  - `chat_files`
  - `chat_session_tags`

### Step 3: Access the System
- **Chat Management**: `pages_php/support/chat-management.php` (Admin/Super Admin only)
- **Live Chat**: `pages_php/support/live-chat.php` (All users)

## Usage Guide

### For Administrators/Agents:

#### 1. Set Your Agent Status
1. Go to Chat Management page
2. Select your status from the dropdown:
   - **Online**: Ready to accept new chats
   - **Busy**: Can handle existing chats but no new assignments
   - **Away**: Temporarily unavailable
   - **Offline**: Not accepting any chats
3. Set your maximum concurrent chats (1-10)
4. Enable/disable auto-assignment
5. Click "Update" to save

#### 2. Manage Chat Sessions
The management interface has three tabs:

**Active Chats Tab:**
- Shows all chats currently assigned to you
- View customer name, subject, priority, and last activity time
- Click "View" to open the chat window

**Waiting Tab:**
- Shows unassigned chats waiting for an agent
- Click "Assign" to take ownership of a chat
- Chats are ordered by start time (oldest first)

**History Tab:**
- Shows all completed chat sessions
- Review past conversations and ratings

#### 3. Responding to Chats
1. Click "View" on any chat session
2. The chat window will open (can be in a new window)
3. Type your message in the input box
4. Press Enter or click the send button
5. Use the lightning bolt icon for quick responses
6. Click the X icon to end the chat session

#### 4. Auto-Assignment
When enabled:
- System automatically assigns waiting chats to available agents
- Considers agent's current chat count and max capacity
- Prioritizes agents with fewer active chats

### For Users/Students:

#### 1. Starting a Chat Session
1. Go to the Live Chat page
2. Click "Start Chat" button
3. Wait for an agent to be assigned
4. Begin your conversation

#### 2. During the Chat
- Type your message and press Enter to send
- You'll see when the agent is typing
- Messages are marked as read automatically
- View the connection status in the header

#### 3. Ending the Chat
- Click the "End Chat" button when your issue is resolved
- Optionally provide a rating and feedback

## API Endpoints

The system uses `chat_api.php` for all backend operations:

### Session Management
- `start_session`: Create a new chat session
- `get_session`: Retrieve session details
- `end_session`: Close a chat session
- `get_agent_sessions`: Get sessions for current agent

### Messaging
- `send_message`: Send a chat message
- `get_messages`: Retrieve chat messages
- `mark_messages_read`: Mark messages as read

### Agent Operations
- `update_agent_status`: Update agent availability
- `get_agent_status`: Get current agent status
- `assign_session`: Manually assign a session to an agent
- `get_quick_responses`: Retrieve quick response templates
- `get_session_stats`: Get statistics for the current agent

## Database Schema

### chat_sessions
Stores chat session information
- `session_id`: Unique session identifier
- `user_id`: ID of the user who initiated the chat
- `assigned_agent_id`: ID of the assigned agent
- `status`: waiting, active, or ended
- `priority`: low, medium, high, or urgent
- `subject`: Chat subject
- `rating`: User rating (1-5)
- `feedback`: User feedback

### chat_messages
Stores all chat messages
- `message_id`: Unique message identifier
- `session_id`: Associated session
- `sender_id`: User who sent the message
- `message_text`: Message content
- `message_type`: text, system, file, or image
- `is_read`: Read status

### chat_agent_status
Tracks agent availability
- `agent_id`: Agent user ID
- `status`: online, busy, away, or offline
- `max_concurrent_chats`: Maximum chats agent can handle
- `current_chat_count`: Current active chats
- `auto_assign`: Auto-assignment preference

## Configuration

### Auto-Refresh Settings
- Chat sessions refresh every 10 seconds
- Messages poll every 3 seconds when chat is active
- Notifications check every 5 seconds

### Notification Sounds
- Enabled by default for new chat sessions
- Plays when new chats enter the waiting queue
- Plays when active chat count increases

## Troubleshooting

### Chat sessions not appearing
1. Verify database tables exist
2. Check agent status is set to "Online"
3. Ensure auto-assign is enabled
4. Refresh the page

### Messages not sending
1. Check session is in "active" status
2. Verify you're a participant in the session
3. Check database connection
4. Look for JavaScript errors in console

### Auto-assignment not working
1. Verify agent status is "online"
2. Check auto_assign is enabled
3. Ensure max_concurrent_chats is not exceeded
4. Check that agent exists in chat_agent_status table

## Security Features

- **Authentication Required**: All endpoints require login
- **Role-Based Access**: Admin/Super Admin only for management features
- **Session Validation**: Users can only access their own sessions (unless admin)
- **SQL Injection Protection**: All inputs are sanitized
- **CORS Protection**: API endpoints have proper CORS headers

## Best Practices

### For Agents:
1. Set realistic max concurrent chat limits
2. Update status when taking breaks
3. Use quick responses for efficiency
4. End sessions promptly when resolved
5. Provide clear, helpful responses

### For Users:
1. Provide clear description of your issue
2. Stay engaged during the chat
3. Provide feedback after session ends
4. Check FAQ before starting a chat

## Future Enhancements

Potential features for future versions:
- File attachment support
- Chat transcripts via email
- Advanced analytics and reporting
- Canned responses customization
- Chat transfer between agents
- Customer satisfaction surveys
- Integration with ticketing system
- Mobile app support
- Video/voice chat capability

## Support

For issues or questions about the chat system, contact the system administrator or super admin.

## Version History

**Version 1.0** (Current)
- Initial release
- Basic chat functionality
- Agent management
- Auto-assignment
- Quick responses
- Real-time updates
