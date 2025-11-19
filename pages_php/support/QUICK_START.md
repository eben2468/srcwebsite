# Live Chat System - Quick Start Guide

## 🚀 Getting Started in 3 Steps

### Step 1: Initialize the Database (One-time setup)
**Option A - Web Interface (Easiest):**
1. Open your browser
2. Go to: `http://localhost/vvusrc/pages_php/support/initialize_chat_system.php`
3. Click "Initialize Chat System"
4. Wait for success message

**Option B - Direct SQL Import:**
1. Open phpMyAdmin
2. Select database: `vvusrc`
3. Import file: `sql/chat_system_tables.sql`

---

### Step 2: Start Using as Agent (Admin/Super Admin)
1. Go to: `http://localhost/vvusrc/pages_php/support/chat-management.php`
2. Set your status to **"Online"**
3. Set max chats to **5** (or your preference)
4. Check **Auto-assign**
5. Click **"Update"**

**You're now ready to receive chats!**

---

### Step 3: Test the System
**As User/Student:**
1. Go to: `http://localhost/vvusrc/pages_php/support/live-chat.php`
2. Click **"Start Chat"**
3. Wait for agent assignment
4. Send a test message

**As Agent (in another window):**
1. Chat appears in **"Waiting"** tab
2. Click **"Assign"** (or auto-assigned if enabled)
3. Click **"View"** to open chat
4. Reply to the message

---

## 📍 Important URLs

| Page | URL | Who Can Access |
|------|-----|----------------|
| **Initialize System** | `/pages_php/support/initialize_chat_system.php` | Super Admin only |
| **Chat Management** | `/pages_php/support/chat-management.php` | Admin, Super Admin |
| **Live Chat** | `/pages_php/support/live-chat.php` | All logged-in users |
| **Test Suite** | `/pages_php/support/test_chat_system.html` | All users |
| **Setup Database** | `/pages_php/support/setup_chat_database.php` | Admin, Super Admin |

---

## 🎯 Quick Tips

### For Agents:
- ⚡ Use **Quick Responses** (lightning icon) for common replies
- 🔔 Sound notifications alert you to new chats
- 📊 Check **Active Chats** tab regularly
- ✅ Set status to **"Offline"** when unavailable

### For Users:
- 💬 Be clear and specific in your first message
- ⏱️ Wait for agent assignment (usually < 30 seconds)
- ⭐ Rate your experience after chat ends
- 📝 Check chat history in your profile

---

## ⚙️ Default Settings

```
Max Concurrent Chats: 5
Auto-assign: Enabled
Refresh Rate: 10 seconds
Message Poll: 3 seconds
Notification Check: 5 seconds
```

---

## 🔧 Troubleshooting

**Problem:** Chat system not working
**Solution:** Run initialization script first

**Problem:** No chats appearing
**Solution:** Set agent status to "Online"

**Problem:** Can't send messages
**Solution:** Ensure session is "Active"

**Problem:** Tables don't exist
**Solution:** Import `chat_system_tables.sql`

---

## ✅ System Check

Run these checks to verify everything is working:

1. **Database**: Tables exist ✓
   ```sql
   SHOW TABLES LIKE 'chat_%';
   ```

2. **Agent Status**: Is Online ✓
   ```sql
   SELECT * FROM chat_agent_status WHERE agent_id = YOUR_USER_ID;
   ```

3. **Quick Responses**: Available ✓
   ```sql
   SELECT COUNT(*) FROM chat_quick_responses WHERE is_active = 1;
   ```

---

## 📞 Need Help?

- 📖 Full documentation: `CHAT_SYSTEM_GUIDE.md`
- 🔬 Run tests: `test_chat_system.html`
- 📋 Implementation details: `IMPLEMENTATION_SUMMARY.md`

---

## 🎉 You're All Set!

The chat system is now fully operational. Start by setting your agent status to "Online" and wait for incoming chats, or test it yourself by opening the live chat in another browser window!

**Happy Chatting! 💬**
