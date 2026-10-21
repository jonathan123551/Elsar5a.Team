# WhatsApp delivery state machine

## Business labels

| Evidence | Internal state | Admin label |
|---|---|---|
| Approved booking, template job queued or running | `template_status=sending` | جاري الإرسال… |
| Meta accepted the template request | `template_status=sent` | بانتظار نقر العميل |
| Meta confirmed delivery of that template | `template_status=delivered` | بانتظار نقر العميل |
| Customer pressed the receive-ticket CTA | `claimed_at` is set; ticket remains `pending` until its send job claims it | جاري التجهيز |
| Meta accepted the QR image request | `delivery_status=sent`, `whatsapp_sent=true` | أُرسلت للشبكة |
| Meta confirmed the QR image delivered it | `delivery_status=delivered`, `last_delivered_at` set | تم الاستلام |
| Meta confirmed the QR image was read | `delivery_status=read`, `last_delivered_at` set | تم الاستلام |
| Provider returned an error or failed webhook | `delivery_status=failed` / `template_status=failed`; raw/translated reason retained | فشل التسليم; details show reason |

`claimed_at` is evidence of a customer action only. It never counts as ticket delivery. HTTP 2xx is provider acceptance (`sent`), not recipient delivery. Only a status webhook matched by message ID can set `last_delivered_at` or upgrade delivery to `delivered`/`read`.

## Write/read matrix

| Field | Writers and condition | Webhook can overwrite? | Stale job protection | Admin read/derivation |
|---|---|---|---|---|
| `tickets.delivery_status` | CTA sets `pending`; image job claims `sending`, accepted API sets `sent`, failures set `failed`; Meta status webhook sets `sent`, `delivered`, `read`, or `failed` by WAMID | Yes, by exact WAMID. Strong `delivered`/`read` cannot be downgraded by weaker status callbacks | Acceptance uses an atomic `CASE`; failure writes exclude delivered/read | `Ticket` helpers; `Booking::delivery_badge`; poll endpoint; detail view |
| `tickets.whatsapp_sent` | Set true on API acceptance or delivered/read webhook; false on a failed/eligible new send path | Yes | Failure writers skip confirmed delivered/read rows | Eligibility and aggregate counts; not a receipt signal |
| `tickets.whatsapp_message_id` | Image send response's `messages[0].id` | Exact lookup key for subsequent status webhook | Acceptance write preserves delivered/read status while storing ID; a callback arriving before this write is held in `whatsapp_message_status_events` and reconciled after ID persistence | Exposed in DB; not currently shown in main table |
| `tickets.last_delivered_at` | Only delivered/read Meta status webhook | It is set by the webhook itself | Send job and admin direct resend no longer populate it | Detail poll timestamp; not used alone as receipt evidence |
| `tickets.claimed_at` | Inbound receive-ticket CTA, after normalized phone identifies exactly one approved booking | No | CTA only updates still-eligible unsent tickets; duplicate message IDs are deduplicated | Customer-action detail and “جاري التجهيز”; never receipt |
| `tickets.delivery_error` | Meta failure webhook, image job failure, resend direct/template failure | A later confirmed delivery clears it | Failed job updates cannot demote delivered/read | Booking detail and status tooltip |
| `tickets.resend_requested_at` | Admin resend action before direct attempt | No | Audit marker only | Ticket detail history |
| `bookings.template_status` | Approval queues `sending`; template job/API acceptance writes `sent`; Meta template WAMID webhook writes delivered/read/failed | Yes, correlated by template WAMID; phone fallback is accepted only when unique | Job acceptance is atomic; failed/sent webhook writes exclude delivered/read | `Booking` helpers and aggregate badge; delivered template still means awaiting CTA |
| `bookings.template_message_id` | Template API acceptance response | Exact lookup key for later template statuses | Acceptance preserves a stronger webhook state; early callbacks are held by WAMID and reconciled after ID persistence | Admin detail and webhook correlation |
| `bookings.template_sent_at` | Template accepted by provider | No | Set by accepted request; distinct from delivery time | Admin detail |
| `bookings.template_error` | Template API/webhook failure | Cleared on confirmed success | Failure writer excludes delivered/read | Main badge tooltip and booking details |

## Correlation and resend

Inbound CTA has no ticket-specific QR ID in the currently configured template. It therefore normalizes the sender number (including Egyptian local `01…` and international `20…` forms), selects eligible tickets under approved bookings, and proceeds only if they belong to one booking. Multiple candidate bookings are logged and left unchanged. Within that booking it queues all eligible tickets for that customer; each queue job uses an atomic compare-and-set claim to avoid duplicate sends.

When no CTA has been claimed, resend sends the approved template again (once per booking/recipient for bulk actions), regardless of the 24-hour window. After a CTA claim, resend tries the QR image directly. On Meta error 131047 (closed 24-hour window), it sends the approved ticket template and resets the current claim marker so the booking waits for a new CTA. A successful template recovery is not recorded as ticket failure. Other provider errors retain the mapped Arabic explanation and provider error code where available.

Webhook delivery statuses are also retained by WAMID briefly when they arrive before the send response's message ID has been persisted. The acceptance writer links that WAMID and applies the retained strongest status, closing the otherwise-unmatched fast-callback race.

## Historical migration caveat

The migration `2026_10_21_000001_reconcile_delivered_whatsapp_tickets_status.php` used `last_delivered_at` to promote `sent` to `delivered`. Older image-send code also populated that timestamp on HTTP acceptance. The migration is now a no-op for databases where it has not run. On an already-migrated database, no current column proves which rows were promoted versus webhook-confirmed; those rows cannot be safely rewritten without historical Meta webhook evidence.
