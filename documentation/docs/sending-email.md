# Sending Email

Email is sent with [PHPMailer](https://github.com/PHPMailer/PHPMailer) over SMTP.
The example below wraps it in a small fluent `Mailer` utility so controllers stay clean.

Trade-off: this page is a recipe, not a shipped mailer. The framework provides no mailer class, no `mail` keys in the default config or `.env`, and no queue, so you own the SMTP setup and mail sends synchronously inside the request.

## Installation

Install PHPMailer with Composer.

```bash
composer require phpmailer/phpmailer
```

## Configuration

Add your SMTP settings to `config/config.php`. Values are read with `Config::get()`, see [Configuration](/configuration). The default `config/config.php` ships with no `mail` key, so add the whole block below before using the recipe.

```php
"mail" => [
    "host" => "smtp.example.com",
    "port" => 587,
    "username" => "user@example.com",
    "password" => "your-password",
    "security" => "tls", // tls or ssl
    "from" => "no-reply@example.com",
],
```

## The Mailer Utility

Recipe only: the framework ships no `App\Utils\Mailer` class, create `app/Utils/Mailer.php` below to get the fluent wrapper used in this guide.

```php
<?php
namespace App\Utils;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Roolith\Configuration\Config;

class Mailer
{
    protected PHPMailer $mail;
    protected array $to = [];
    protected array $cc = [];
    protected array $bcc = [];
    protected array $attachments = [];
    protected string $subject = '';
    protected string $body = '';

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        $this->mail->isSMTP();
        $this->mail->Host = Config::get('mail.host');
        $this->mail->Port = Config::get('mail.port');
        $this->mail->SMTPAuth = true;
        $this->mail->Username = Config::get('mail.username');
        $this->mail->Password = Config::get('mail.password');
        $this->mail->SMTPSecure = Config::get('mail.security') == 'tls'
            ? PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer::ENCRYPTION_SMTPS;

        $this->mail->setFrom(Config::get('mail.from'));
    }

    public static function to(string|array $emails): self
    {
        $instance = new static;
        $instance->addEmails('to', $emails);

        return $instance;
    }

    public function cc(string|array $emails): self
    {
        $this->addEmails('cc', $emails);

        return $this;
    }

    public function bcc(string|array $emails): self
    {
        $this->addEmails('bcc', $emails);

        return $this;
    }

    protected function addEmails(string $type, string|array $emails): void
    {
        foreach ((array) $emails as $email) {
            $this->{$type}[] = $email;
        }
    }

    public function attachment(string|array $files): self
    {
        foreach ((array) $files as $file) {
            $this->attachments[] = $file;
        }

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function send(bool $isDebugMode = false): bool
    {
        try {
            foreach ($this->to as $email) {
                $this->mail->addAddress($email);
            }

            foreach ($this->cc as $email) {
                $this->mail->addCC($email);
            }

            foreach ($this->bcc as $email) {
                $this->mail->addBCC($email);
            }

            foreach ($this->attachments as $file) {
                $this->mail->addAttachment($file);
            }

            if ($isDebugMode) {
                $this->mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL;
            }

            $this->mail->isHTML(true);
            $this->mail->Subject = $this->subject;
            $this->mail->Body = $this->body;

            return $this->mail->send();
        } catch (Exception $e) {
            return false;
        }
    }
}
```

## Sending a Simple Email

```php
use App\Utils\Mailer;

$isSent = Mailer::to('a@b.com')
    ->subject('Email subject')
    ->body('<p>Hello from Roolith!</p>')
    ->send();
```

`send()` returns `true` on success and `false` on failure.

## Multiple Recipients and Copies

Pass arrays to address multiple people at once.

```php
Mailer::to(['a@b.com', 'c@d.com'])
    ->cc('d@x.com')
    ->bcc(['e@y.com', 'f@z.com'])
    ->subject('Email subject')
    ->body('<p>Email body</p>')
    ->send();
```

## Attachments

```php
Mailer::to('a@b.com')
    ->attachment(APP_ROOT . '/uploads/invoice.pdf')
    ->subject('Your invoice')
    ->body('<p>Please find your invoice attached.</p>')
    ->send();
```

Multiple attachments are supported by passing an array.

## Using Mailer in a Controller

```php
<?php
namespace App\Controllers;

use App\Models\User;
use App\Utils\Mailer;

class AuthController extends Controller
{
    public function sendVerificationCode()
    {
        $user = User::current();

        if (!$user) {
            return false;
        }

        return Mailer::to($user->email)
            ->subject('Your verification code')
            ->body('<p>Your code is <strong>' . $user->verification_code . '</strong>.</p>')
            ->send();
    }
}
```

## Debugging

Pass `true` to `send()` to see the full SMTP conversation while developing.

```php
Mailer::to('a@b.com')
    ->subject('Debug')
    ->body('<p>Debug</p>')
    ->send(true);
```

## Notes

- The body is sent as HTML with `isHTML(true)`, so use plain text or strip tags for plain text emails.
- Add a `MAIL_SERVICE` style switch if you want to disable sending in development and let `send()` return `true` without touching the SMTP server.
- Keep credentials in the environment specific config files (`config/development.config.php`) so secrets never ship to production.