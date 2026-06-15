# ClickPo AI Chatbot — Build Plan & Technical Specification

_WordPress plugin for the ClickPo marketing site (`www.clickpo.io`). Standalone project — NOT part of the ClickPo SaaS repo._

---

## 1. Goal
A fully custom Hebrew (RTL) AI chatbot that:
- Answers visitors **only** from an approved internal knowledge base (no hallucinated product facts).
- Recommends the most suitable ClickPo package/plan and links to its signup page.
- **Info-only — NO lead capture.** It does not collect name/email/phone.
- Saves every conversation so the admin can review them for tracking.
- Protects against spam/abuse.
- Is fully configurable from the WordPress admin (knowledge, packages, branding, model, spam).

**AI provider:** Google **Gemini** (REST API from PHP). Default model: `gemini-2.5-flash` (cheap, fast, strong Hebrew). Model + key set in admin.

---

## 2. Plugin identity
- **Slug:** `clickpo-ai-chatbot`
- **Text domain:** `clickpo-ai-chatbot`
- **Main constant prefix:** `CLICKPO_BOT_`
- **PHP class prefix:** `ClickPo_Bot_`
- **DB table prefix:** `wp_clickpo_bot_*`
- **Min:** WordPress 6.0+, PHP 7.4+

---

## 3. File structure
```
clickpo-ai-chatbot/
├── clickpo-ai-chatbot.php          # Main plugin file: header, constants, autoload, activation hook
├── uninstall.php                   # Drop tables / clean options on uninstall (opt-in)
├── includes/
│   ├── class-bot-core.php          # Bootstrap, hooks, loads everything
│   ├── class-bot-activator.php     # Creates DB tables (dbDelta), default settings
│   ├── class-bot-db.php            # All DB read/write (sessions, messages, knowledge, logs)
│   ├── class-bot-settings.php      # Get/set settings (wp_options, single array)
│   ├── class-bot-rest.php          # Registers REST routes + handlers
│   ├── class-bot-ai.php            # Gemini client: build prompt, call API, parse response
│   ├── class-bot-knowledge.php     # KB retrieval (select relevant entries for the prompt)
│   ├── class-bot-recommender.php   # Package recommendation logic
│   ├── class-bot-spam.php          # Rate limiting, honeypot, blocklist, message caps
│   └── class-bot-security.php      # Nonce, sanitize, capability helpers
├── admin/
│   ├── class-bot-admin.php         # Admin menu + page routing
│   ├── views/
│   │   ├── dashboard.php            # Overview: stats, recent chats
│   │   ├── knowledge.php            # KB CRUD UI
│   │   ├── conversations.php        # Conversation log viewer
│   │   ├── packages.php             # Package/plan definitions for the recommender
│   │   ├── settings.php             # API/model, spam, behavior
│   │   └── appearance.php           # Widget branding (icon, colors, text)
│   └── assets/admin.css | admin.js
├── public/
│   ├── class-bot-widget.php         # Enqueues + renders the floating widget container
│   └── assets/
│       ├── widget.css               # RTL chat UI styling
│       └── widget.js                # Chat logic: open/close, send, render, typing
├── languages/                       # he_IL .po/.mo
└── BUILD_PLAN.md
```

---

## 4. Database schema (5 tables)

**`wp_clickpo_bot_sessions`** — one row per conversation
| col | type | notes |
|---|---|---|
| id | BIGINT PK AI | |
| session_uid | CHAR(36) | UUID sent to client, used by REST |
| created_at | DATETIME | |
| last_active_at | DATETIME | |
| ip_hash | CHAR(64) | hashed IP (privacy) |
| user_agent | VARCHAR(255) | |
| status | VARCHAR(20) | open / closed / flagged |

_No lead/contact columns — the bot is info-only._

**`wp_clickpo_bot_messages`** — every message
| col | type | notes |
|---|---|---|
| id | BIGINT PK AI | |
| session_id | BIGINT FK | |
| role | VARCHAR(10) | user / assistant / system |
| content | LONGTEXT | |
| tokens | INT | optional usage tracking |
| created_at | DATETIME | |

**`wp_clickpo_bot_knowledge`** — editable knowledge base
| col | type | notes |
|---|---|---|
| id | BIGINT PK AI | |
| title | VARCHAR(255) | |
| content | LONGTEXT | the approved fact/answer |
| category | VARCHAR(100) | grouping |
| keywords | TEXT | comma list, aids retrieval |
| embedding | LONGTEXT | nullable — JSON vector (Phase 2 RAG) |
| is_active | TINYINT | |
| updated_at | DATETIME | |

**`wp_clickpo_bot_logs`** — security/spam/error events
| col | type | notes |
|---|---|---|
| id | BIGINT PK AI | |
| type | VARCHAR(30) | rate_limit / blocked / api_error / flagged |
| session_id | BIGINT | nullable |
| detail | TEXT | |
| created_at | DATETIME | |

**`wp_clickpo_bot_packages`** — editable plans for the recommender (change often)
| col | type | notes |
|---|---|---|
| id | BIGINT PK AI | |
| name | VARCHAR(255) | product/plan name |
| monthly_price | VARCHAR(50) | monthly subscription price |
| includes | LONGTEXT | what the plan contains (details) |
| signup_url | VARCHAR(255) | link to sign up |
| sort_order | INT | display order |
| is_active | TINYINT | |
| updated_at | DATETIME | |

Settings live in one option: `clickpo_bot_settings` (array). Knowledge & packages may also be cached.

---

## 5. REST API (namespace `clickpo-bot/v1`)
Public (nonce-protected, rate-limited):
- `POST /session` → create session, return `session_uid`.
- `POST /message` → `{ session_uid, message }` → runs AI orchestration, returns `{ reply, cta?, recommendation? }`.

Admin (capability `manage_options`):
- `GET/POST/PUT/DELETE /knowledge` → KB CRUD.
- `GET/POST/PUT/DELETE /packages` → plan CRUD (name, price, includes, signup_url).
- `GET /conversations`, `GET /conversations/{id}` → saved chats for review.
- `GET/POST /settings`.

Every public route: verify nonce, sanitize input, run spam checks **before** calling Gemini.

---

## 6. AI orchestration (the core flow)
On `POST /message`:
1. **Load session** + last N messages (context window, e.g. last 10).
2. **Spam gate** — rate limit, length cap, blocklist, honeypot. Fail → log + generic refusal, no API call.
3. **Knowledge retrieval** (`class-bot-knowledge`):
   - **Phase 1 (simple):** inject all active KB entries (or keyword-matched subset) into the system prompt. Works well for a modest KB.
   - **Phase 2 (RAG, optional):** Gemini `text-embedding-004` → cosine similarity in PHP → top-K entries only. Scales to large KBs.
4. **Build prompt:**
   - **System prompt** (Hebrew rules): reply only in Hebrew; answer ONLY from the provided knowledge; never invent product/pricing details; if unknown, say so politely (no lead capture); recommend the best package when intent is detected; emit a CTA (signup link) when appropriate.
   - **Knowledge block** (retrieved entries).
   - **Packages block** (plan list + who each suits).
   - **Conversation history** + new user message.
5. **Call Gemini** (`generativelanguage.googleapis.com/v1beta/models/{model}:generateContent`), API key from settings (server-side only, never exposed). Timeout + 1 retry.
6. **Recommendation** (`class-bot-recommender`): detect plan intent → pick best package → attach structured CTA (label + URL).
7. **Persist** user + assistant messages; update `last_active_at`.
8. **Return** `{ reply, cta?, recommendation? }`.

Structured output: instruct Gemini to optionally return a small JSON tail (recommended_package, cta) parsed server-side, with a plain-text fallback.

---

## 7. Frontend widget
- **Visibility gate:** setting `visibility` = `admins` (default, testing) or `everyone` (live). In `admins` mode the widget loads only for logged-in administrators (`manage_options`); the header shows a "מצב בדיקה" badge. Flip to `everyone` in Settings to launch.
- Floating launcher button (bottom corner, configurable side for RTL) injected via `wp_footer`.
- Panel: header (logo/title), scrollable messages, input + send, typing indicator.
- Pure vanilla JS (no build step) + `fetch` to REST. RTL by default.
- Assistant messages can render CTA buttons (from `cta`) and a recommendation card (plan name, price, signup link).
- No lead form — info-only.
- All colors/icon/welcome text/launcher position from settings (CSS variables).

---

## 8. Anti-spam & abuse
- **Rate limit:** max messages per session + per IP-hash per minute/hour (transients).
- **Length cap** on each message.
- **Honeypot** hidden field in the widget.
- **Blocklist:** keywords / blocked IP-hashes in settings.
- **Nonce** on every request; sessions expire after inactivity.
- All blocks logged to `wp_clickpo_bot_logs`.

---

## 9. Admin dashboard
- **Dashboard:** total chats, messages, blocked attempts, recent conversations.
- **Knowledge:** add/edit/delete entries (title, content, category, keywords, active).
- **Packages:** add/edit/delete plans (name, monthly price, what's included, signup link, order, active).
- **Conversations:** searchable saved chats, view full transcript, flag/delete.
- **Settings:** Gemini API key + model, context length, spam thresholds, behavior rules.
- **Appearance:** launcher icon, colors, welcome message, position, header title.

---

## 10. Security
- Server-side API key only (never in client JS).
- `sanitize_*` on all input, `esc_*` on all output, `wpdb->prepare` everywhere.
- Capability checks (`manage_options`) on all admin routes.
- Nonces on public + admin REST.
- IP stored hashed; option to auto-purge old conversations (GDPR-friendly).

---

## 11. Implementation phases
1. **Scaffold + UI** — plugin file, activation/DB, admin menu, empty widget.
2. **Knowledge base** — KB table + CRUD admin + packages editor.
3. **Conversation engine** — sessions/messages, REST, Gemini integration, system prompt, recommender.
4. **Logging dashboard** — conversation viewer + stats.
5. **Anti-spam layer** — rate limit, honeypot, blocklist, logs.
6. **Customization** — appearance settings wired to the widget.
7. **Testing & polish** — Hebrew RTL QA, edge cases, performance, security pass.

---

## 12. Decisions (confirmed)
- **Packages:** dynamic admin CRUD — name, monthly price, what's included, signup link. ✅
- **Knowledge:** user enters all content in admin. ✅
- **No lead capture.** Bot is info-only; conversations saved for admin review. ✅
- **No Freemius.** Free/internal plugin. ✅
- **Deploy:** user gives site access; I push new versions directly. ✅

## 13. Still needed before / during build
1. **Gemini API key** — user will send it.
2. **Site access details** — how I deploy to the WP marketing site: SFTP/SSH credentials, or WP admin login + host (e.g. Cloudways/other). Also a staging vs production note.
3. **Plan content** — actual plans + knowledge entered later in admin (not blocking the build).
