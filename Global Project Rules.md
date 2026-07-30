# Easytsk V2 - Global Project Rules & AI Directives

## 1. Project Context
You are building a mobile-first Progressive Web App (PWA) for a micro-tasking and reward platform (Easytsk V2). The platform involves financial transactions, gamification, anti-fraud mechanisms, and user retention systems. 

## 2. Technology Stack
*   **Backend:** Laravel 11.x, PHP 8.2+
*   **Frontend:** Vue.js 3 (Composition API `<script setup>`), Inertia.js
*   **Styling:** Tailwind CSS (Dark theme focus)
*   **Database:** MySQL
*   **Caching/Queue:** Redis

## 3. Global AI Directives (CRITICAL)
*   **No Hallucination:** NEVER assume database table names or columns. ALWAYS strictly follow the `database_schema.md` file.
*   **Follow Core Logics:** For any business logic (withdrawals, pending balances, locked rewards, anti-fraud), you MUST read and implement exactly what is written in `core_logics.md`.
*   **Production-Ready Code:** Do not write "TODO" or placeholder logic. Write complete, secure, and production-ready code.
*   **Language:** Write all code, comments, and variable names in English. Use meaningful names (e.g., `processWithdrawal` instead of `doWithdraw`).

## 4. Backend & Security Rules (Laravel)
*   **Financial Transactions:** Any operation modifying user balances (`main_balance`, `pending_balance`, `locked_balance`) MUST be wrapped in a Database Transaction (`DB::transaction`). This prevents money duplication during server lag.
*   **Validation:** NEVER trust user input. Always use Laravel Form Requests (`php artisan make:request`) for data validation before hitting the controller logic.
*   **Queues & Events:** Use Laravel Events/Listeners for non-blocking tasks (like auto-deleting images after approval, updating referral progress). Use Queues/Jobs for heavy tasks.
*   **API Security:** All webhook routes (like Offerwall postbacks) must verify IP addresses and HMAC signatures.
*   **Data Types:** Cast decimal columns correctly in Eloquent models. Prevent negative balances unless explicitly allowed (like chargeback penalties).

## 5. Frontend Rules (Vue.js & Inertia)
*   **Mobile-First Design:** Write Tailwind classes primarily for mobile views, then scale up for desktop (e.g., `w-full md:w-1/2`).
*   **State Management:** Use Inertia's shared data for global states (User Auth, Balances).
*   **UI/UX:** Use Skeleton Loaders instead of loading spinners for better UX.
*   **Interactions:** Add micro-interactions (Vue transition tags) for task completions and balance updates.
*   **API Calls:** Use Inertia links and forms for internal routing. Use Axios only for specific background API calls if necessary.

## 6. Anti-Fraud Implementation Reminders
*   Always implement Cloudflare Turnstile on public forms (Login, Register).
*   Use FingerprintJS for device hashing.
*   Enforce the 24-hour withdrawal cooldown strictly at the controller level, not just the UI level.
*   Implement global image hashing (MD5/SHA256) for all uploaded proof screenshots before storing them locally.