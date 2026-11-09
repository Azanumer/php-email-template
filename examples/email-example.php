<?php
/**
 * Example: build a welcome email and an order confirmation, then print the
 * HTML and plain-text versions. No mail is sent — pipe the output to a file
 * or hand it to your mailer (mail(), PHPMailer, or the php-mail-queue worker).
 *
 * Usage: php examples/email-example.php > /tmp/welcome.html
 */

declare(strict_types=1);

require __DIR__ . '/../src/EmailTemplate.php';

// --- Welcome email ---------------------------------------------------------
$welcome = (new EmailTemplate('CorelHost', '#0d7a5f'))
    ->setTagline('Fast, affordable web hosting')
    ->heading('Welcome to CorelHost, Azan!')
    ->paragraph('Your account is ready. Your first hosting plan is active and your domain is pointed at our servers.')
    ->bulletList([
        'Free SSL certificate, auto-renewed',
        'Daily backups, kept for 30 days',
        'One-click WordPress installer',
    ])
    ->button('Open your dashboard', 'https://corelhost.com/dashboard')
    ->divider()
    ->setFooter('You received this email because you created an account at CorelHost. Reply to this email if you need help.');

echo "=== WELCOME (HTML) ===\n";
echo $welcome->build() . "\n\n";
echo "=== WELCOME (PLAIN TEXT) ===\n";
echo $welcome->plainText() . "\n";

// --- Order confirmation ----------------------------------------------------
$order = (new EmailTemplate('CorelHost', '#0d7a5f'))
    ->heading('Order confirmed — #1042')
    ->paragraph('Thanks for your order! Here is what you bought:')
    ->table(
        ['Item', 'Term', 'Price'],
        [
            ['Shared Hosting — Starter', '12 months', '$35.88'],
            ['Domain — example.com', '1 year', '$9.99'],
        ]
    )
    ->paragraph('Total charged: $45.87. Your invoice is attached to your account.')
    ->button('View invoice', 'https://corelhost.com/invoices/1042', '#b45309')
    ->setFooter('CorelHost — support@corelhost.com');

echo "=== ORDER (HTML) ===\n";
echo $order->build() . "\n\n";
echo "=== ORDER (PLAIN TEXT) ===\n";
echo $order->plainText() . "\n";
