# PHP Email Template

A dependency-free HTML email builder. It produces a responsive, table-based email with inline styles — the only styling every email client reliably supports — plus a matching plain-text version. Pair it with `mail()`, PHPMailer, or the `php-mail-queue` worker.

## Usage

```php
require 'src/EmailTemplate.php';

$email = (new EmailTemplate('CorelHost', '#0d7a5f'))
    ->setTagline('Fast, affordable web hosting')
    ->heading('Welcome to CorelHost, Azan!')
    ->paragraph('Your account is ready.')
    ->bulletList(['Free SSL', 'Daily backups', 'One-click WordPress'])
    ->button('Open your dashboard', 'https://corelhost.com/dashboard')
    ->divider()
    ->setFooter('Reply to this email if you need help.');

$html = $email->build();      // Full HTML email
$text = $email->plainText();  // Plain-text alternative

mail($to, 'Welcome to CorelHost', $html, [
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
]);
```

## Parts

| Method | What it adds |
|---|---|
| `heading($text)` | Large section heading |
| `paragraph($text)` | Body paragraph |
| `button($label, $url, $color = null)` | Call-to-action button (http/https URLs only) |
| `bulletList($items)` | Bulleted list |
| `table($headers, $rows)` | Simple data table (order lines, account details) |
| `divider()` | Horizontal rule |
| `setTagline($text)` | Small line under the brand in the header |
| `setFooter($text)` | Small centred footer text |

## Safety

- All text is escaped with `htmlspecialchars()` — user-supplied content cannot break the layout or inject markup.
- Buttons only accept `http`/`https` URLs; `javascript:` and other schemes are silently dropped.
- Brand/button colours must be `#rgb` / `#rrggbb`; anything else falls back to the default dark grey.

## Example

Run `php examples/email-example.php` for a welcome email and an order confirmation, printed as HTML and plain text.

## License

MIT — see [LICENSE](LICENSE).
