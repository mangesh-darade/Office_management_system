# Jitsi Meet Setup for Office Management System

## Overview

The Chats module uses Jitsi Meet as the SFU (Selective Forwarding Unit) for multi-party video meetings. Configure Jitsi under **Settings → API Integrations → Jitsi Meet**.

**Elintpos production:**
- OMS portal: `https://internalportal.elintpos.in/` (login + chat only)
- Jitsi Account ID must be the **Meet server** domain: `meet.jit.si` (test) or `meet.elintpos.in` (self-host)
- Do **not** use `internalportal.elintpos.in` as Jitsi Account ID

## Quick test (no self-host)

1. Go to **API Integrations → Add Integration**
2. Service Type: **Jitsi Meet**
3. Account ID / Domain: `meet.jit.si`
4. Auth Token: leave blank
5. Save and set as default + active
6. Open **Chat → Join Meeting** in a group conversation

## Self-hosted (Docker)

```bash
git clone https://github.com/jitsi/docker-jitsi-meet
cd docker-jitsi-meet
cp env.example .env
```

Edit `.env`:

- `PUBLIC_URL=https://jitsi.yourcompany.com`
- Enable JWT if you want secure rooms:
  - `ENABLE_AUTH=1`
  - `AUTH_TYPE=jwt`
  - `JWT_APP_ID=oms_jitsi`
  - `JWT_APP_SECRET=<generated-secret>`

```bash
./gen-passwords.sh
docker compose up -d
```

In OMS API Integrations:

| Field | Value |
|-------|-------|
| Domain (Account ID) | `jitsi.yourcompany.com` |
| JWT App ID (From Name) | `oms_jitsi` |
| Auth Token | value of `JWT_APP_SECRET` |

## Recording (Jibri)

Self-hosted recording requires Jibri alongside docker-jitsi-meet. See [Jitsi Jibri documentation](https://jitsi.github.io/handbook/docs/devops-guide/devops-guide-videoresolution).

When Jibri is configured, moderators see the **Record** button in the meeting toolbar.

## Host controls

Users with moderator JWT (conversation creator, meeting starter, or admin role_id=1) can:

- Mute all participants
- Enable waiting room (lobby)
- Start/stop recording (when Jibri or cloud recording is available)

## Troubleshooting

| Issue | Fix |
|-------|-----|
| "Jitsi is not configured" | Add active Jitsi integration in API Integrations |
| Black video / no connect | Check HTTPS on Jitsi domain; browser must allow camera/mic |
| JWT errors | Ensure app_id and secret match Jitsi server `.env` |
| Group call falls back to 1:1 | Group calls require Jitsi; configure integration first |
