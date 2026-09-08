# Date Helpers

Carbon 2 is built in, no install needed. It covers creation, formatting, comparison, and human diffs.

```php
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
```

See the upstream [Introduction](https://carbon.nesbot.com/guide/getting-started/introduction.html) and [Reference](https://carbon.nesbot.com/docs/) for the full API. This page shows the patterns you will use most.

## Framework Helpers

For timestamps you store in the database, use the built-in helpers:

```php
getCurrentDateTime(); // "2026-08-31 14:22:10"
getCurrentDate();     // "2026-08-31"
```

Use Carbon directly when you need more control:

```php
Carbon::now()->toDateTimeString(); // 2026-08-31 14:22:10
Carbon::now()->toDateString();     // 2026-08-31
Carbon::now()->toIso8601String();  // 2026-08-31T14:22:10-06:00
```

## Timezone

The framework sets the timezone automatically before any Carbon call: config `timezone` wins, then `APP_TIMEZONE` from `.env`, then `UTC`. Unknown values fall back to `UTC`.

```ini
# .env
APP_TIMEZONE=Asia/Dhaka
```

```php
// config/config.php (wins over .env when both are set)
'timezone' => 'Asia/Dhaka',
```

To store in `UTC` and convert only when rendering:

```php
$utc = Carbon::now(); // UTC
echo $utc->copy()->setTimezone('Asia/Dhaka')->toDateTimeString();

// one liner for a model timestamp
echo Carbon::parse($user->created_at, 'UTC')->setTimezone('Asia/Dhaka')->isoFormat('LLL');
```

Storing `UTC` keeps Docker and servers behaving the same across daylight saving changes.

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
```

`parse()` understands almost any English string that `strtotime()` does. It throws `Carbon\Exceptions\InvalidFormatException` on invalid input, so wrap it in try / catch when the value comes from the user or the request.

## Formatting

```php
$dt = Carbon::create(2026, 8, 31, 14, 22, 10);

$dt->format('Y-m-d H:i:s');   // 2026-08-31 14:22:10
$dt->toDateString();           // 2026-08-31
$dt->toDateTimeString();       // 2026-08-31 14:22:10
$dt->toFormattedDateString();  // Aug 31, 2026
$dt->toDayDateTimeString();    // Sun, Aug 31, 2026 2:22 PM
$dt->toIso8601String();        // 2026-08-31T14:22:10-06:00

// locale aware
$dt->locale('en')->isoFormat('dddd, MMMM D, YYYY'); // Sunday, August 31, 2026
$dt->locale('fr')->isoFormat('dddd D MMMM YYYY');   // dimanche 31 aout 2026
```

In views, format on the way out and keep the raw value in the controller:

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
```

## Getters and Setters

```php
$dt = Carbon::now();

$dt->year;        // 2026
$dt->month;       // 8
$dt->day;         // 31
$dt->hour;        // 14
$dt->dayOfWeek;   // 0 (Sun) .. 6 (Sat)
$dt->dayName;     // Sunday
$dt->monthName;   // August
$dt->quarter;     // 3
$dt->daysInMonth; // 31

$dt->year = 2027;
$dt->setDate(2027, 1, 15);
$dt->setTime(9, 0, 0);
```

Fluent setters chain and return the instance:

```php
Carbon::now()->year(2027)->month(1)->day(15)->hour(9)->minute(0)->second(0);
```

## Manipulation

`Carbon` mutates the instance, `CarbonImmutable` returns a new one. Prefer immutable in services and models to avoid accidental mutation.

```php
Carbon::now()->addDay();
Carbon::now()->addDays(5);
Carbon::now()->subWeek();
Carbon::now()->addMonths(2);
Carbon::now()->subYears(1);

Carbon::now()->addHours(3)->addMinutes(15);
Carbon::now()->startOfDay();   // 00:00:00
Carbon::now()->endOfDay();     // 23:59:59
Carbon::now()->startOfWeek();  // Monday 00:00:00 (depends on locale)
Carbon::now()->startOfMonth();
Carbon::now()->endOfMonth();
```

```php
use Carbon\CarbonImmutable;

$now = CarbonImmutable::now();
$tomorrow = $now->addDay(); // new instance, $now unchanged
$now->isSameDay($tomorrow); // false
```

Convert between the two when needed:

```php
$mutable = CarbonImmutable::now()->toMutable();
$immutable = Carbon::now()->toImmutable();
```

## Comparison

```php
$a = Carbon::parse('2026-08-31 10:00:00');
$b = Carbon::parse('2026-08-31 12:00:00');

$a->eq($b);        // false (equal)
$a->lt($b);        // true  (less than)
$a->gt($b);        // false (greater than)
$a->between($a, $b); // true, inclusive

$a->isPast();
$a->isFuture();
$a->isToday();
$a->isWeekend();
$a->isSameDay($b);
```

Useful guard in controllers and middleware:

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
$to->diffInMonths($from);
$to->diffInYears($from);
$to->diffInDays($from, false); // signed: -30 when $to < $from
```

Human readable diffs:

```php
Carbon::now()->subMinutes(5)->diffForHumans();              // 5 minutes ago
Carbon::now()->addHours(2)->diffForHumans();                // 2 hours from now
Carbon::now()->subDays(3)->diffForHumans(null, true);       // 3 days (short, no ago/from now)
Carbon::now()->subDays(10)->diffForHumans(['parts' => 2]);  // 1 week 3 days ago

CarbonInterval::make('1 month 3 days')->forHumans();        // 1 month 3 days
```

## CarbonInterval and CarbonPeriod

For durations and ranges, use the specialized classes:

```php
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;

$interval = CarbonInterval::days(3);
$interval->forHumans(); // 3 days

// iterate over dates
$period = CarbonPeriod::create('2026-08-01', '2026-08-05');
foreach ($period as $date) {
    echo $date->toDateString() . PHP_EOL;
}
// 2026-08-01 ... 2026-08-05

// every 2 days
$period = CarbonPeriod::create('2026-08-01', '2 days', '2026-08-10');

// weekdays only
$period = CarbonPeriod::create('2026-08-01', '2026-08-10')->filter(fn ($d) => $d->isWeekday());
```

## Localization

```php
Carbon::setLocale('fr'); // globally
Carbon::now()->locale('fr')->diffForHumans();
Carbon::now()->locale('ja')->isoFormat('LLLL');
```

This is separate from the app locale (`APP_LOCALE`). Check [available translations](https://carbon.nesbot.com/docs/#api-localization) for the full list.

## Practical Recipes

### Save to Database

Store as `Y-m-d H:i:s` and parse on read. This matches MySQL `DATETIME` and `getCurrentDateTime()`.

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

Cookie expiries take a `Carbon` instance (see [Storage](/storage)):

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

Freeze time in tests so assertions are deterministic:

```php
use Carbon\Carbon;

Carbon::setTestNow(Carbon::create(2026, 8, 31, 10, 0, 0));
Carbon::now()->toDateTimeString(); // 2026-08-31 10:00:00 always

// after test
Carbon::setTestNow(); // clear
```

For immutable code, use `CarbonImmutable::setTestNow()`.

## Notes

- `Carbon` mutates, `CarbonImmutable` does not. Prefer immutable in domain logic and models.
- Store `UTC` in the database and convert to the display timezone.
- Invalid `parse()` / `createFromFormat()` input throws `InvalidFormatException`; catch it for user-supplied values.
- Freeze time with `Carbon::setTestNow()` in tests rather than mocking `time()`.
- For full method lists, see [Getters](https://carbon.nesbot.com/docs/#api-getters), [Setters](https://carbon.nesbot.com/docs/#api-setters), [Comparison](https://carbon.nesbot.com/docs/#api-comparison), and [Difference](https://carbon.nesbot.com/docs/#api-difference).
