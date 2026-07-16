# Google Calendar Reminder — PHP Backend Code

Same flow as Python: OAuth + Calendar API → email reminder to filled Gmail.

Requires Composer package:

```bash
composer require google/apiclient:^2.15
```

---

## Required files

| File | Purpose |
|------|---------|
| `credentials.json` | OAuth Desktop / Web client from Google Cloud |
| `token.json` | Saved after first Google login |

Enable **Google Calendar API** in Google Cloud Console.

---

## Full backend code (`CalendarService.php`)

```php
<?php
/**
 * Google Calendar email reminder backend (PHP).
 * Place credentials.json next to this file (or set paths below).
 */

require_once __DIR__ . '/vendor/autoload.php';

class CalendarService
{
    private const SCOPES = ['https://www.googleapis.com/auth/calendar'];

    private string $credentialsFile;
    private string $tokenFile;

    public function __construct(
        ?string $credentialsFile = null,
        ?string $tokenFile = null
    ) {
        $base = __DIR__;
        $this->credentialsFile = $credentialsFile ?: $base . '/credentials.json';
        $this->tokenFile = $tokenFile ?: $base . '/token.json';
    }

    /**
     * OAuth login and return Google Calendar service.
     */
    public function getCalendarService(): Google_Service_Calendar
    {
        if (!is_file($this->credentialsFile)) {
            throw new RuntimeException(
                "credentials.json not found.\n"
                . "1) Google Cloud Console -> APIs & Services -> Credentials\n"
                . "2) Create OAuth client\n"
                . "3) Download JSON and save as credentials.json"
            );
        }

        $client = new Google_Client();
        $client->setApplicationName('Calendar Email Reminder');
        $client->setScopes(self::SCOPES);
        $client->setAuthConfig($this->credentialsFile);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        if (is_file($this->tokenFile)) {
            $accessToken = json_decode(file_get_contents($this->tokenFile), true);
            $client->setAccessToken($accessToken);
        }

        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken();
            if ($refreshToken) {
                $client->fetchAccessTokenWithRefreshToken($refreshToken);
            } else {
                // CLI first-time auth: open URL, paste code
                $authUrl = $client->createAuthUrl();
                echo "Open this URL in browser:\n{$authUrl}\n";
                echo "Enter verification code: ";
                $authCode = trim(fgets(STDIN));

                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                if (isset($accessToken['error'])) {
                    throw new RuntimeException('OAuth error: ' . $accessToken['error']);
                }
                $client->setAccessToken($accessToken);
            }

            file_put_contents(
                $this->tokenFile,
                json_encode($client->getAccessToken(), JSON_PRETTY_PRINT)
            );
        }

        return new Google_Service_Calendar($client);
    }

    /**
     * Create calendar event with email reminder + attendee invite.
     *
     * @param Google_Service_Calendar $service
     * @param string $title
     * @param DateTimeInterface $startDt  timezone-aware preferred
     * @param string $email
     * @param int $durationMinutes
     * @param int $reminderMinutes  minutes before start (email + popup)
     * @param string $description
     * @param string $calendarId
     * @param string $timezone
     * @return Google_Service_Calendar_Event
     */
    public function createReminderEvent(
        Google_Service_Calendar $service,
        string $title,
        DateTimeInterface $startDt,
        string $email,
        int $durationMinutes = 30,
        int $reminderMinutes = 30,
        string $description = '',
        string $calendarId = 'primary',
        string $timezone = 'Asia/Kolkata'
    ): Google_Service_Calendar_Event {
        $endDt = (new DateTimeImmutable('@' . $startDt->getTimestamp()))
            ->setTimezone(new DateTimeZone($timezone))
            ->modify('+' . $durationMinutes . ' minutes');

        $startLocal = (new DateTimeImmutable('@' . $startDt->getTimestamp()))
            ->setTimezone(new DateTimeZone($timezone));

        $event = new Google_Service_Calendar_Event([
            'summary' => $title,
            'description' => $description !== '' ? $description : ('Reminder for ' . $email),
            'start' => [
                'dateTime' => $startLocal->format(DateTimeInterface::RFC3339),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $endDt->format(DateTimeInterface::RFC3339),
                'timeZone' => $timezone,
            ],
            'attendees' => [
                ['email' => $email],
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => $reminderMinutes],
                    ['method' => 'popup', 'minutes' => $reminderMinutes],
                ],
            ],
        ]);

        return $service->events->insert($calendarId, $event, [
            'sendUpdates' => 'all',
        ]);
    }

    /**
     * List upcoming events.
     *
     * @return Google_Service_Calendar_Event[]
     */
    public function listUpcomingEvents(
        Google_Service_Calendar $service,
        string $calendarId = 'primary',
        int $maxResults = 10
    ): array {
        $result = $service->events->listEvents($calendarId, [
            'timeMin' => gmdate('c'),
            'maxResults' => $maxResults,
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ]);

        return $result->getItems() ?: [];
    }
}
```

---

## How to call (example)

```php
<?php

require_once __DIR__ . '/CalendarService.php';

$calendar = new CalendarService();
$service = $calendar->getCalendarService();

$start = new DateTimeImmutable('2026-07-16 10:00:00', new DateTimeZone('Asia/Kolkata'));

$event = $calendar->createReminderEvent(
    $service,
    'Client call',
    $start,
    'you@gmail.com',
    30,   // duration minutes
    30,   // remind email X minutes before
    'Bring documents'
);

echo "Reminder created\n";
echo "Title : " . $event->getSummary() . "\n";
echo "Link  : " . $event->getHtmlLink() . "\n";
```

---

## Simple form handler (optional backend endpoint)

```php
<?php
/**
 * POST: email, title, when (Y-m-d\TH:i), minutes, description
 * Returns JSON: { "status": "success|error", "message": "", "data": {} }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/CalendarService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'POST only',
        'data' => new stdClass(),
    ]);
    exit;
}

$email = trim($_POST['email'] ?? '');
$title = trim($_POST['title'] ?? '');
$whenRaw = trim($_POST['when'] ?? '');
$minutes = (int) ($_POST['minutes'] ?? 30);
$description = trim($_POST['description'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'Valid email required',
        'data' => new stdClass(),
    ]);
    exit;
}

if ($title === '' || $whenRaw === '') {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'Title and date/time required',
        'data' => new stdClass(),
    ]);
    exit;
}

try {
    $timezone = new DateTimeZone('Asia/Kolkata');
    // HTML datetime-local → 2026-07-16T10:00
    $start = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $whenRaw, $timezone);
    if ($start === false) {
        throw new InvalidArgumentException('Invalid date/time format');
    }

    $calendar = new CalendarService();
    $service = $calendar->getCalendarService();
    $event = $calendar->createReminderEvent(
        $service,
        $title,
        $start,
        $email,
        30,
        max(0, $minutes),
        $description
    );

    echo json_encode([
        'status' => 'success',
        'message' => 'Reminder created',
        'data' => [
            'summary' => $event->getSummary(),
            'htmlLink' => $event->getHtmlLink(),
            'email' => $email,
            'reminder_minutes' => max(0, $minutes),
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'data' => new stdClass(),
    ]);
}
```

---

## Functions

| Method | Purpose |
|--------|---------|
| `getCalendarService()` | OAuth + Calendar client |
| `createReminderEvent(...)` | Create event + email reminder |
| `listUpcomingEvents(...)` | Upcoming events list |

---

## Flow

1. `getCalendarService()` → Google OAuth (`token.json`)
2. `createReminderEvent()` → Calendar `events.insert`
3. Google sends invite + email reminder (`minutes` before)

---

## Note (Web OAuth)

CLI example uses **paste auth code**.  
For browser UI in CodeIgniter / PHP site, use **Web application** OAuth client and redirect URI (e.g. `http://localhost/your-app/google/callback`) instead of STDIN code paste.
