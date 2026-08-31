# Date Helpers

Roolith ships with [nesbot/carbon](https://carbon.nesbot.com/) `2.73.0` out of the box.
You do not need to install anything.
Carbon is already required in `composer.json` and autoloaded via `vendor/autoload.php`.
It extends PHP's `DateTime` and gives you fluent creation, formatting, comparison, and human diffs.

The framework itself uses Carbon in two places.
Inside `app/Utils/functions.php` it is imported for `getCurrentDateTime` and `getCurrentDate`.
Inside `app/Core/Storage.php` it type-hints `Carbon` for `setCookie`.

See the upstream [Introduction](https://carbon.nesbot.com/guide/getting-started/introduction.html) and [Reference](https://carbon.nesbot.com/docs/) for the full API.
This recipe shows the patterns you will use most inside Roolith.

## Quick Start

Import the class and call a static factory.
All examples below assume this import.

```php
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
```

No service provider or config is needed.
Just call `Carbon::now()` wherever you need a date.

## Framework Helpers

Roolith wraps the two most common cases.

```php
// inside app/Utils/functions.php
getCurrentDateTime(); // "2026-08-31 14:22:10" via Carbon::now()->toDateTimeString()
getCurrentDate();     // "2026-08-31" via Carbon::now()->toDateString()
```

Use them for timestamps you store in the database.
Use Carbon directly when you need more control.

```php
Carbon::now()->toDateTimeString(); // 2026-08-31 14:22:10
Carbon::now()->toDateString();     // 2026-08-31
Carbon::now()->toIso8601String();  // 2026-08-31T14:22:10-06:00
```

## Timezone

Roolith sets the default timezone inside `index.php`.

```php
date_default_timezone_set('America/Edmonton');
```

This value is the default for every `Carbon::now()`, `getCurrentDateTime()`, `date()` and `strtotime()` call that does not pass an explicit timezone.
Change it once and the whole application follows.

### Option 1 - Edit index.php Directly

This is the simplest change and matches the current framework default.

```php
<?php

use App\Core\System;

const APP_ROOT = __DIR__;
date_default_timezone_set('Asia/Dhaka'); // your timezone
// date_default_timezone_set('UTC'); // or UTC if you store everything in UTC

session_start();

require_once __DIR__ . '/vendor/autoload.php';

$app = new System();
$app->bootstrap()->processRequest()->complete();
```

Pick any identifier from the [PHP timezone list](https://www.php.net/manual/en/timezones.php).
Common choices are `UTC`, `America/New_York`, `America/Edmonton`, `Europe/London`, `Asia/Dhaka`, and `Asia/Tokyo`.
Keep `index.php` as the single source of truth if your app runs in one timezone.

### Option 2 - Make It Configurable (Recommended)

Read the timezone from `config/config.php` so it can vary per environment and stay out of code.
This pairs well with the [Using Dot ENV](/using-dot-env) recipe.

Add a key to `config/config.php`.

```php
<?php
return [
    "baseUrl" => "http://localhost:8080/",
    "timezone" => $_ENV['APP_TIMEZONE'] ?? 'America/Edmonton',
    // or without dotenv: "timezone" => "Asia/Dhaka",
    "database" => null,
    "forceNonWww" => true,
    "version" => time(),
];
```

If you use `.env`, add the variable there.

```ini
APP_TIMEZONE=Asia/Dhaka
# APP_TIMEZONE=UTC
```

Then read it inside `index.php` before the framework boots.

```php
<?php

use App\Core\System;

const APP_ROOT = __DIR__;

require_once __DIR__ . '/vendor/autoload.php';

// Load .env first if you use it
if (class_exists(Dotenv\Dotenv::class)) {
    Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
}

// Read timezone from config or env, fallback to Edmonton
$config = require __DIR__ . '/config/config.php';
date_default_timezone_set($config['timezone'] ?? $_ENV['APP_TIMEZONE'] ?? 'America/Edmonton');

// Alternatively, read directly from Config after it is available inside System
// and call date_default_timezone_set() at the top of System::__construct().

session_start();

$app = new System();
$app->bootstrap()->processRequest()->complete();
```

Another clean place is at the top of `app/Core/System.php` inside `__construct()`.

```php
public function __construct()
{
    require_once APP_ROOT . "/constant.php";
    require_once APP_ROOT . "/app/Utils/functions.php";

    $timezone = \Roolith\Configuration\Config::get('timezone') ?? 'America/Edmonton';
    date_default_timezone_set($timezone);

    $this->db = null;
    $this->registerCustomError();
}
```

Use whichever entry point you prefer.
The key point is to call `date_default_timezone_set()` before any `Carbon::now()` or `getCurrentDateTime()` call.

### Option 3 - Keep Storage in UTC and Convert on Display

Many teams store everything in `UTC` and convert only when rendering.
Set the default to `UTC` and pass the user timezone per instance.

```php
date_default_timezone_set('UTC');

// later
Carbon::now('Asia/Dhaka');
Carbon::create(2026, 8, 31, 10, 0, 0, 'Asia/Dhaka');

$utc = Carbon::now(); // UTC
$dhaka = $utc->copy()->setTimezone('Asia/Dhaka');
echo $dhaka->toDateTimeString(); // 2026-08-31 20:22:10 in Dhaka

// one liner for a model timestamp
echo Carbon::parse($user->created_at, 'UTC')->setTimezone('Asia/Dhaka')->isoFormat('LLL');
```

This is the most portable setup.
It avoids daylight saving surprises and makes Docker and servers behave the same.

## Creating Instances

```php
Carbon::now();                          // now
Carbon::today();                        // today at 00:00:00
Carbon::tomorrow();                     // tomorrow at 00:00:00
Carbon::yesterday();                    // yesterday at 00:00:00

Carbon::parse('2026-08-31');
Carbon::parse('2026-08-31 10:30:00');
Carbon::parse('first day of next month');

Carbon::create(2026, 8, 31, 10, 30, 0, 'America/Edmonton');
Carbon::createFromFormat('Y-m-d H:i', '2026-08-31 10:30');
Carbon::createFromTimestamp(1725091200);
Carbon::createFromTimestampMs(1725091200000);

Carbon::hasTestNow(); // false unless you set a test now
```

`parse()` is the most flexible.
It understands almost any English string that `strtotime()` does.

Invalid strings throw `Carbon\Exceptions\InvalidFormatException`.
Wrap `parse()` or `createFromFormat()` in a try / catch when the input comes from the user or the request.

## Formatting

Carbon inherits `format()` and adds readable helpers.

```php
$dt = Carbon::create(2026, 8, 31, 14, 22, 10);

$dt->format('Y-m-d H:i:s');   // 2026-08-31 14:22:10
$dt->toDateString();           // 2026-08-31
$dt->toDateTimeString();       // 2026-08-31 14:22:10
$dt->toFormattedDateString();  // Aug 31, 2026
$dt->toDayDateTimeString();    // Sun, Aug 31, 2026 2:22 PM
$dt->toIso8601String();        // 2026-08-31T14:22:10-06:00
$dt->toAtomString();           // 2026-08-31T14:22:10-06:00 (RFC 3339)
$dt->toRfc2822String();        // Sun, 31 Aug 2026 14:22:10 -0600

// Locale aware, uses symfony/translation
$dt->locale('en')->isoFormat('dddd, MMMM D, YYYY'); // Sunday, August 31, 2026
$dt->locale('fr')->isoFormat('dddd D MMMM YYYY');   // dimanche 31 aout 2026
$dt->locale('es')->isoFormat('LL');                 // 31 de agosto de 2026
```

In views, format on the way out and keep the raw Carbon instance in the controller or model.

```php
// controller
return $this->view('users/show', [
    'user' => $user,
    'joined' => Carbon::parse($user->created_at)->isoFormat('LL'),
]);
```

```php
<!-- views/users/show.php -->
<p>Joined <?= $joined ?></p>
<p>Raw <?= Carbon\Carbon::parse($user->created_at)->format('Y-m-d') ?></p>
```

## Getters and Setters

```php
$dt = Carbon::now();

$dt->year;        // 2026
$dt->month;       // 8
$dt->day;         // 31
$dt->hour;        // 14
$dt->minute;      // 22
$dt->second;      // 10
$dt->dayOfWeek;   // 0 (Sun) .. 6 (Sat)
$dt->dayName;     // Sunday
$dt->monthName;   // August
$dt->quarter;     // 3
$dt->daysInMonth; // 31
$dt->isLeapYear();// bool

$dt->year = 2027;
$dt->month = 1;
$dt->setDate(2027, 1, 15);
$dt->setTime(9, 0, 0);
$dt->setDateTime(2027, 1, 15, 9, 0, 0);
```

Fluent setters are also available and return the instance for chaining.

```php
Carbon::now()->year(2027)->month(1)->day(15)->hour(9)->minute(0)->second(0);
```

## Manipulation

All modifiers mutate the instance when using `Carbon` and return a new instance when using `CarbonImmutable`.
Chain them for readability.

```php
Carbon::now()->addDay();
Carbon::now()->addDays(5);
Carbon::now()->subWeek();
Carbon::now()->addMonths(2);
Carbon::now()->subYears(1);

Carbon::now()->addHours(3)->addMinutes(15);
Carbon::now()->subDays(10)->startOfDay();
Carbon::now()->addMonth()->endOfMonth();

Carbon::now()->startOfDay();   // 00:00:00
Carbon::now()->endOfDay();     // 23:59:59
Carbon::now()->startOfWeek();  // Monday 00:00:00 (depends on locale)
Carbon::now()->endOfWeek();
Carbon::now()->startOfMonth();
Carbon::now()->endOfMonth();
Carbon::now()->startOfYear();
Carbon::now()->endOfYear();
```

Prefer `CarbonImmutable` in services and models to avoid accidental mutation.

```php
use Carbon\CarbonImmutable;

$now = CarbonImmutable::now(); // 2026-08-31 14:22:10
$tomorrow = $now->addDay();    // new instance, $now unchanged
$now->isSameDay($tomorrow);    // false
```

Convert between the two when needed.

```php
$mutable = CarbonImmutable::now()->toMutable();   // Carbon
$immutable = Carbon::now()->toImmutable();        // CarbonImmutable

$mutable->isMutable();   // true
$immutable->isImmutable();// true
```

## Comparison

```php
$a = Carbon::parse('2026-08-31 10:00:00');
$b = Carbon::parse('2026-08-31 12:00:00');

$a->eq($b);        // false (equal)
$a->ne($b);        // true  (not equal)
$a->lt($b);        // true  (less than)
$a->gt($b);        // false (greater than)
$a->lte($b);       // true
$a->gte($b);       // false
$a->between($a, $b); // true, inclusive

$a->isPast();      // true if < now
$a->isFuture();    // false if > now
$a->isToday();
$a->isTomorrow();
$a->isYesterday();
$a->isWeekend();
$a->isWeekday();
$a->isLeapYear();
$a->isSameDay($b);
$a->isSameMonth($b);
```

Useful guard in controllers and middleware.

```php
if (Carbon::parse($user->expires_at)->isPast()) {
    return $this->view('errors/expired');
}
```

## Difference

```php
$from = Carbon::parse('2026-08-01');
$to = Carbon::parse('2026-08-31');

$to->diffInDays($from);    // 30
$to->diffInHours($from);   // 720
$to->diffInMinutes($from);
$to->diffInSeconds($from);
$to->diffInWeeks($from);
$to->diffInMonths($from);
$to->diffInYears($from);
$to->diffInDays($from, false); // -30 when absolute is false and $to < $from

$to->diff($from); // DateInterval
```

### Difference for Humans

Human readable diffs are built on `symfony/translation`.

```php
Carbon::now()->subMinutes(5)->diffForHumans();              // 5 minutes ago
Carbon::now()->addHours(2)->diffForHumans();                // 2 hours from now
Carbon::parse('2026-08-20')->diffForHumans();               // 1 week ago (relative to now)

Carbon::now()->diffForHumans(Carbon::parse('2026-08-20')); // 1 week after
Carbon::now()->subDays(3)->diffForHumans(null, true);      // 3 days (short, no ago/from now)

Carbon::now()->locale('fr')->diffForHumans(); // il y a 5 minutes
Carbon::now()->locale('bn')->diffForHumans(); // 5 minutes translated if available
```

Options: pass a reference date, `true` for short absolute diff, control parts and syntax.

```php
Carbon::now()->subDays(10)->diffForHumans(['parts' => 2]); // 1 week 3 days ago
CarbonInterval::make('1 month 3 days')->forHumans();       // 1 month 3 days
```

## CarbonInterval and CarbonPeriod

For durations and ranges, use the specialized classes.

```php
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;

// interval
$interval = CarbonInterval::days(3);
$interval->forHumans(); // 3 days

$interval = CarbonInterval::make('2 weeks 3 days');
echo $interval->totalHours; // 408

// period - iterate over dates
$period = CarbonPeriod::create('2026-08-01', '2026-08-05');
foreach ($period as $date) {
    echo $date->toDateString() . PHP_EOL;
}
// 2026-08-01 ... 2026-08-05

// every 2 days, exclude end
$period = CarbonPeriod::create('2026-08-01', '2 days', '2026-08-10');
foreach ($period as $date) {
    echo $date->format('Y-m-d') . ' ';
}

// filter weekdays only
$period = CarbonPeriod::create('2026-08-01', '2026-08-10')->filter(fn ($d) => $d->isWeekday());
```

## Localization

Carbon bundles translations via `symfony/translation`.

```php
Carbon::setLocale('fr');
Carbon::now()->locale('fr')->diffForHumans(); // il y a ...
Carbon::now()->locale('ja')->isoFormat('LLLL'); // 2026 8 31 ...

// globally
Carbon::setLocale('bn');
```

Check [available translations](https://carbon.nesbot.com/docs/#api-localization) for the full list.

## Practical Recipes

### Save to Database

Store as `Y-m-d H:i:s` and parse on read.
This matches MySQL `DATETIME` and the existing helper `getCurrentDateTime()`.

```php
use Carbon\Carbon;
use App\Models\User;

// create
User::raw()->table('users')->insert([
    'name' => 'Hadi',
    'created_at' => Carbon::now()->toDateTimeString(),
    'expires_at' => Carbon::now()->addDays(7)->toDateTimeString(),
]);

// read and format
$user = User::raw()->table('users')->where('id', 1)->first();
$created = Carbon::parse($user->created_at);
echo $created->isoFormat('LL'); // August 31, 2026
echo $created->diffForHumans(); // 2 hours ago
```

### Expiry and Cookie

Roolith's cookie helper expects a `Carbon` instance inside `app/Core/Storage.php`.

```php
use Carbon\Carbon;
use App\Core\Storage;

Storage::setCookie('remember', $token, Carbon::now()->addDays(30));
Storage::setCookie('banner', '1', Carbon::now()->addHours(6));
```

### Human Diff in a View

```php
<?php
use Carbon\Carbon;
?>
<ul>
<?php foreach ($posts as $post): ?>
    <li>
        <?= $post->title ?> -
        <?= Carbon::parse($post->created_at)->diffForHumans() ?>
    </li>
<?php endforeach ?>
</ul>
```

### Age or Tenure

```php
$dob = Carbon::parse('1995-06-15');
$age = $dob->age; // 31
$tenure = Carbon::parse($user->joined_at)->diffInYears(Carbon::now());
```

### Testing with Fixed Now

Freeze time in tests so assertions are deterministic.

```php
use Carbon\Carbon;

Carbon::setTestNow(Carbon::create(2026, 8, 31, 10, 0, 0));
Carbon::now()->toDateTimeString(); // 2026-08-31 10:00:00 always

// after test
Carbon::setTestNow(); // clear
```

For immutable code, use `CarbonImmutable::setTestNow()`.

## Notes

- Carbon `2.73.0` targets PHP `^7.1.8 || ^8.0`, which matches Roolith `4.0.0` requirement `php >=8.0`.
- `Carbon` mutates, `CarbonImmutable` does not.
- Prefer `CarbonImmutable` in domain logic and models to avoid shared state bugs.
- Always store UTC in the database and convert to display timezone with `setTimezone()` or by passing the timezone to `parse()`.
- Inside `index.php` the default is set to `America/Edmonton`.
- Change it inside `index.php` directly, or make it configurable via `config/config.php` and `APP_TIMEZONE` as shown above.
- You must call `date_default_timezone_set()` before any Carbon call, otherwise `Carbon::now()` will use the old timezone.
- Set `Carbon::setTestNow()` in tests rather than mocking `time()`.
- For full method lists, see [Getters](https://carbon.nesbot.com/docs/#api-getters), [Setters](https://carbon.nesbot.com/docs/#api-setters), [Comparison](https://carbon.nesbot.com/docs/#api-comparison), and [Difference](https://carbon.nesbot.com/docs/#api-difference).
