# Sabino website + Sabinox café helper

The customer page at `index.php` is a complete responsive Sabino coffee-shop website. It includes brand and story sections, the shop’s **current drinks and food menu with prices**, directions, hours, contact links, and an embedded Sabinox helper. It works without an account or AI API key. Customer chat history is kept in the browser session; **New chat** clears it.

The shop facts, profile hours, and official menu items live in `includes/sabino.php`. The menu is separated into Drinks and Food, then grouped by the categories used on Sabino’s boards. Sabinox currently reports Monday–Friday 9:00 AM–10:00 PM, Saturday 1:00 PM–9:00 PM, and Sunday closed; it asks visitors to confirm holiday and special-event hours.

Sabinox can return the complete menu or separate Drinks and Food lists. It also answers exact price questions such as “How much is Carbonara?” and correctly handles the solo and sharing prices for wings.

The existing account, admin, and knowledge-base platform remains available through `login.php`, `admin/`, and `workspace.php`. Staff tools require the MySQL setup described below; the customer FAQ page does not.

### Vercel deployment

Vercel serves the static site in `public/`, selected by `vercel.json`. The PHP source remains for XAMPP, but Vercel does not run the PHP admin tools or `api/sabino.php`. The Vercel chatbot answers the same core FAQs in the browser and keeps chat history in session storage. After changing the shop facts or menu in `includes/sabino.php`, run `php build-static.php`, commit the updated `public/` files, and redeploy.

## Original platform setup

A complete, production-quality AI chatbot platform designed to run locally on Windows using XAMPP. This system features a full Retrieval-Augmented Generation (RAG) architecture, allowing you to build a custom knowledge base for your AI.

## 📋 Features
- **Modern UI/UX**: Responsive, ChatGPT-like interface.
- **RAG Architecture**: Answers questions based on your custom knowledge base (manual entries + uploaded documents).
- **Multi-Provider AI**: Supports OpenAI, Anthropic (Claude), OpenRouter, or any OpenAI-compatible API.
- **Document Processing**: Upload TXT, CSV, PDF, and DOCX files. The system parses, chunks, and indexes the text.
- **Persistent Conversations**: Chat history is saved, and previous context is intelligently sent to the AI.
- **Admin Dashboard**: Comprehensive management of users, knowledge, documents, and bot settings.
- **Security First**: PDO prepared statements, CSRF protection, rate limiting, and secure file handling.

---

## 🚀 Installation Instructions (XAMPP on Windows)

### 1. Install XAMPP
1. Download XAMPP for Windows from [apachefriends.org](https://www.apachefriends.org/index.html).
2. Install it (usually to `C:\xampp`).
3. Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.

### 2. Prepare the Application
1. Place this entire project folder into your XAMPP htdocs directory: `C:\xampp\htdocs\chatbot`.
2. Ensure you have the `.env` file in the root directory. If not, edit the `.env` file and add your database and AI API credentials.
3. Open the `php.ini` file (via XAMPP Control Panel -> Apache -> Config -> PHP (php.ini)) and ensure the following extensions are uncommented (remove the `;` at the start of the line):
   - `extension=pdo_mysql`
   - `extension=curl`
   - `extension=mbstring`
   - `extension=zip` (required for DOCX parsing)
   - `extension=fileinfo`
4. Restart Apache.

### 3. Database Setup
1. Open your browser and go to `http://localhost/phpmyadmin/`.
2. Click **New** on the left sidebar to create a new database.
3. Name it `chatbot_db` and select the collation `utf8mb4_unicode_ci`. Click **Create**.
4. You can either:
   - Import the `database/schema.sql` file via the phpMyAdmin **Import** tab.
   - OR, navigate to `http://localhost/chatbot/setup.php` which will automatically test your connection, import the schema, and create necessary directories.

### 4. Finalizing Setup
1. Navigate to `http://localhost/chatbot/setup.php`.
2. If all checks pass, your system is ready.
3. **IMPORTANT:** For security, delete `setup.php` after successful installation!
4. Go to `http://localhost/chatbot/login.php` and register a new account. The **first** account registered automatically becomes the Administrator.

---

## ⚙️ Configuration

### AI Provider Setup
1. Log in with your admin account.
2. Click the **Admin Dashboard** link in the sidebar or go to `http://localhost/chatbot/admin/dashboard.php`.
3. Navigate to **Bot Settings** > **AI API**.
4. Select your provider (e.g., OpenAI).
5. Enter your API Key (e.g., from `platform.openai.com`).
6. Set the model (e.g., `gpt-4o-mini`).
7. Click **Save API Settings** and use **Test Connection** to verify.

### Chatbot Personality
1. In the Admin Dashboard, go to **Bot Settings** > **Chatbot**.
2. Set the Bot Name, Personality, and custom System Instructions.
3. Choose the Knowledge Mode:
   - **Hybrid**: Uses the knowledge base first, falls back to general AI knowledge.
   - **Knowledge Base Only**: Strictly refuses to answer questions not covered in the uploaded knowledge.

---

## 📚 Managing Knowledge

### Adding Manual Knowledge
1. Go to **Admin > Knowledge Base**.
2. Click **Add Knowledge**.
3. Provide a title, optional category/tags, and the full text content.
4. The system automatically chunks and indexes the content for RAG retrieval.

### Uploading Documents
1. Go to **Admin > Documents**.
2. Drag and drop a supported file (TXT, CSV, PDF, DOCX).
3. The system parses the document, extracts the text, and splits it into searchable chunks.
4. *Note on PDFs*: Native PHP extraction works for simple PDFs. For complex PDFs, install `poppler-utils` (`pdftotext`) and add it to your system PATH for advanced text extraction.

---

## 🧠 How the RAG System Works
1. **User Query**: The user asks a question in the chat interface.
2. **Retrieval**: The backend performs a full-text search (with LIKE fallback) against the indexed `knowledge_chunks` and `knowledge_items` in the MySQL database.
3. **Ranking**: Results are ranked by relevance score and deduplicated.
4. **Context Building**: The top `N` chunks (configurable in Bot Settings) are formatted into a context block.
5. **Prompt Injection**: The AI receives a system prompt containing your strict instructions + the context block + recent conversation history + the user's question.
6. **Source Attribution**: If the AI uses the context, the system provides a list of the sources used below the message.

---

## 🛡️ Security Considerations
- **Environment Variables**: The `.env` file contains sensitive data. Ensure the `.htaccess` file is working to prevent direct browser access to `.env`.
- **Uploads**: Files uploaded to `uploads/` are secured via an `.htaccess` file that blocks direct execution.
- **Passwords**: All passwords are hashed using bcrypt.
- **API Keys**: Stored in the database and never exposed to the frontend JS.

## 🐛 Troubleshooting
- **API Errors**: Check your AI API key and verify you have available quota/credits.
- **Upload Errors**: Check your `php.ini` settings for `upload_max_filesize` and `post_max_size` (should be >= 10M).
- **Blank Pages / 500 Errors**: Turn on `DEBUG_MODE=true` in the `.env` file to see detailed error outputs. Check the logs in `logs/` directory.

Enjoy your custom AI Chatbot Platform!
