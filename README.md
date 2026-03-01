# LeadLookup (Perfex CRM Module)

Internal-use module for **Perfex CRM (CodeIgniter)** to search leads by phone number and return structured lead information in **JSON**.

> ✅ Designed for internal integrations and quick lookups  
> ❗ Not intended as a public API

---

## Features

- Search leads by phone number (`tblleads.phonenumber`)
- Returns **all matching leads**
- For each lead returns:
  - Basic info (id, name, phone, status name, description, created/last contact)
  - **All custom fields** (key-value)
  - **All notes** (with staff full name)
  - **Latest 3 activities** (with staff full name if available)
- JSON-only responses
- Simple authentication via **static API key (query param)**

---

## Endpoint

### Lookup by Phone
**GET**
```
/leadlookup/by_phone?apikey=YOUR_STATIC_SECRET_KEY_HERE&phone=017XXXXXXXX
```

### Example
```
https://crm.alpha.net.bd/leadlookup/by_phone?apikey=YOUR_STATIC_SECRET_KEY_HERE&phone=01797810793
```

---

## Response Format

### ✅ Success (HTTP 200)
```json
{
  "status": "success",
  "data": [
    {
      "id": 15,
      "name": "John Doe",
      "phone": "017XXXXXXXX",
      "status": "New",
      "description": "...",
      "created_date": "YYYY-MM-DD HH:MM:SS",
      "last_contact": "YYYY-MM-DD HH:MM:SS",
      "custom_fields": {
        "Services": "Shared Hosting",
        "Brand": "alpha.net.bd"
      },
      "notes": [
        {
          "content": "Note 1",
          "date": "YYYY-MM-DD HH:MM:SS",
          "staff_name": "Admin User"
        }
      ],
      "latest_activities": [
        {
          "description": "Status changed",
          "date": "YYYY-MM-DD HH:MM:SS",
          "staff_name": "Staff Member"
        }
      ]
    }
  ]
}
```

### ❌ Error (HTTP 400 / 401 / 404)
> `data` is **always an array**, even for errors.

```json
{
  "status": "error",
  "data": [
    {
      "error": "unauthorized",
      "message": "Invalid or missing API key."
    }
  ]
}
```

---

## HTTP Status Codes

- `200` OK — lead(s) found
- `400` Bad Request — missing/invalid phone
- `401` Unauthorized — missing/invalid `apikey`
- `404` Not Found — no leads matched the phone

---

## Installation

1. Upload the module folder:
   ```
   /modules/leadlookup/
   ```

2. Activate the module from Perfex Admin:
   ```
   Setup → Modules → LeadLookup → Activate
   ```

3. Configure API key:
   Edit:
   ```
   modules/leadlookup/config/leadlookup.php
   ```

   Set:
   ```php
   'api_key' => 'YOUR_STATIC_SECRET_KEY_HERE',
   ```

---

## Configuration

File:
```
modules/leadlookup/config/leadlookup.php
```

Options:
- `api_key` (required): static key used in query parameter `apikey`
- `phone_match`:  
  - `like` (default): partial match for formatting differences
  - `exact`: exact string match

---

## Security (Important)

This module is for **internal use only**.

### ⚠️ Security Risks
Because the API key is passed as a **query parameter**, it can be exposed in:
- Server access logs
- Browser history
- Reverse proxy logs (e.g., Nginx)
- Monitoring tools / analytics
- Shared screenshots or copied URLs

### Recommendations (Strongly Suggested)
1. **Do not expose publicly**
   - Restrict access to internal networks only (VPN / private subnet)
   - Protect with firewall rules (allow only your office/IP ranges)

2. **Use HTTPS**
   - Always run behind TLS so the API key isn’t transmitted in plain text.

3. **Rotate API keys**
   - Treat the key like a password. Rotate if leaked.

4. **Avoid sharing URLs**
   - Never paste full URLs containing `apikey` in tickets/chats.

5. **Consider moving key to header (better)**
   - If you want improved security later, use:
     - `X-API-Key: <key>`
   - This avoids leaking into many logs (still not perfect but better).

### Data Exposure Warning
This endpoint can return:
- lead descriptions
- internal notes
- staff names
- activity history

Only grant access to trusted internal systems/users.

---

## Troubleshooting

### Getting `401 unauthorized`
- Check that you included:
  - `apikey=...`
- Confirm the key matches `config/leadlookup.php`

### Getting `404 not_found`
- No leads matched the phone number
- Try `phone_match = like` if numbers are stored with country code or formatting

---

## License
Internal / Private Use Only
