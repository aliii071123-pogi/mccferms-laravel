# Flutter API Connection

FERMS keeps PostgreSQL in Supabase. Flutter clients should call the Laravel API, not the Supabase Data API. The Laravel connection uses the server-side Supabase database role; `anon` and `authenticated` Data API roles have no access to FERMS tables.

## Microsoft Sign-In

For native mobile, open `GET {API_BASE_URL}/auth/microsoft?client=mobile` in the system browser. For Flutter Web, navigate to `GET {API_BASE_URL}/auth/microsoft?client=web` (`client=flutter_web` remains a supported alias). A regular Laravel web login should omit the `client` query parameter. In all cases Microsoft redirects to Laravel's registered callback at `{APP_URL}/auth/microsoft/callback`; Laravel then sends the one-time code to the configured client callback.

- Native mobile returns to `FERMS_MOBILE_CALLBACK_URI` (for example, an app deep link).
- Flutter Web returns to `FERMS_FLUTTER_WEB_URL?code=...`.

Exchange the code once:

```http
POST {API_BASE_URL}/api/v1/auth/exchange
Accept: application/json
Content-Type: application/json

{"code":"<one-time-code>"}
```

The response contains `token`, `expires_at`, and `user`. Store the bearer token in the platform's secure storage. Codes expire after three minutes and cannot be reused. Tokens expire after `FERMS_API_TOKEN_DAYS` (30 days by default).

If the student's organization profile is incomplete, reservation endpoints return `403` with `profile_required: true`. Load managed organization choices from `GET /api/v1/organizations`, then complete the profile with `PUT /api/v1/profile` and the selected `organization` name and `position` strings. The profile update rejects organization names that are not in the managed list.

## API Endpoints

Send `Accept: application/json` and `Authorization: Bearer <token>` on authenticated requests.

| Method | Path | Purpose |
| --- | --- | --- |
| POST | `/api/v1/auth/exchange` | Exchange a mobile sign-in code for a token |
| GET | `/api/v1/me` | Current account and role |
| DELETE | `/api/v1/auth/session` | Revoke the current token |
| GET | `/api/v1/dashboard` | Dashboard counts |
| PUT | `/api/v1/profile` | Update a student's organization and position |
| GET | `/api/v1/facilities` | Facilities and upcoming schedule blocks |
| GET | `/api/v1/equipment` | Equipment inventory and upcoming borrowing blocks |
| GET | `/api/v1/organizations` | Organization and institute choices for student profiles |
| GET | `/api/v1/calendar?month=YYYY-MM` | Privacy-safe facility schedule for the selected month |
| GET | `/api/v1/reservations` | Current user's requests, or all requests for staff/admin |
| POST | `/api/v1/reservations` | Create a request |
| GET | `/api/v1/reservations/{id}` | Request details |
| PUT | `/api/v1/reservations/{id}` | Edit the owner's pending request |
| DELETE | `/api/v1/reservations/{id}/cancel` | Cancel the owner's pending request |
| PUT | `/api/v1/reservations/{id}/decision` | Admin approval/rejection |
| DELETE | `/api/v1/reservations/{id}` | Admin request deletion |
| POST/PUT/DELETE | `/api/v1/facilities[/{id}]` | Staff+ facility management |
| POST/PUT/DELETE | `/api/v1/equipment[/{id}]` | Staff+ equipment management |

Facility and equipment create/update requests require `staff_in_charge` (staff member name). Equipment `category` is optional.

Reservation creation fields are `facility_id` (nullable when borrowing equipment only), `duration_days` (1–7), `days` (one entry per event day, each with a `date` (`YYYY-MM-DD`), `start_time` and `end_time` (`HH:mm`, Manila local time)), `pax`, `purpose`, `description`, and `equipment` (an object mapping equipment IDs to requested quantities). Each event day has its own date and time range. At least one facility or positive equipment quantity is required. Facility/time conflicts return a validation error asking the requester to coordinate with facility staff; no requester or activity details are disclosed.

Use `GET /api/v1/calendar?month=YYYY-MM` to retrieve shared facility bookings for a month. The response includes only facility name, start/end time, and pending/booked status.

Use `GET /api/v1/reservations?view=history` for completed/closed history; the default lists upcoming and pending requests. Admin review payload: `{"status":"approved"}` or `{"status":"rejected"}`.

## Deployment Settings

- Register the exact callback URL `{APP_URL}/auth/microsoft/callback` in the Microsoft Entra app.
- Set `MICROSOFT_CLIENT_ID`, `MICROSOFT_CLIENT_SECRET`, and `MICROSOFT_TENANT_ID` in the server environment. `FERMS_SUPERADMIN_EMAIL` identifies the bootstrap super-admin.
- Set `FERMS_FLUTTER_WEB_URL` to the Flutter Web app base URL (including its forwarded port origin when using Codespaces). This is the post-login destination and is not the Microsoft Entra redirect URI.
- Register the mobile callback scheme as an Android intent filter and iOS URL type. Set `FERMS_MOBILE_CALLBACK_URI` to that callback. Prefer verified app/universal links for production deployments.
- The configured Flutter Web origin is automatically included in CORS. Set `FERMS_CORS_ALLOWED_ORIGINS` only for additional browser origins (comma-separated); native mobile clients do not rely on browser CORS.
- Run `php artisan migrate --force` against the Supabase connection after deploying code. Keep `DB_CONNECTION=pgsql` and the Supabase pooler credentials; never ship Supabase service-role or database credentials in Flutter.