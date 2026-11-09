<?php
/**
 * EmailTemplate — dependency-free HTML email builder.
 *
 * Builds a responsive, table-based HTML email with inline styles (the only
 * styling email clients reliably support) plus a matching plain-text version.
 * Everything is escaped on the way in, so user-supplied content cannot break
 * the layout or inject markup.
 *
 * @author Azan Umer
 * @license MIT
 */

declare(strict_types=1);

class EmailTemplate
{
    /** @var string Brand name shown in the header. */
    private string $brand;

    /** @var string Brand colour used for the header bar and buttons. */
    private string $brandColor;

    /** @var string Optional header tagline. */
    private string $tagline = '';

    /** @var array<int, array<string, mixed>> Ordered content parts. */
    private array $parts = [];

    /** @var string Footer text shown at the bottom of the email. */
    private string $footer = '';

    public function __construct(string $brand = '', string $brandColor = '#1d2327')
    {
        $this->brand = $brand;
        $this->brandColor = $this->sanitizeColor($brandColor);
    }

    public function setTagline(string $tagline): self
    {
        $this->tagline = $tagline;
        return $this;
    }

    public function setFooter(string $footer): self
    {
        $this->footer = $footer;
        return $this;
    }

    public function heading(string $text): self
    {
        $this->parts[] = ['type' => 'heading', 'text' => $text];
        return $this;
    }

    public function paragraph(string $text): self
    {
        $this->parts[] = ['type' => 'paragraph', 'text' => $text];
        return $this;
    }

    /**
     * A call-to-action button. The URL must be http(s); anything else is
     * ignored and the button is not added.
     */
    public function button(string $label, string $url, ?string $color = null): self
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return $this;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return $this;
        }
        $this->parts[] = [
            'type'  => 'button',
            'label' => $label,
            'url'   => $url,
            'color' => $color !== null ? $this->sanitizeColor($color) : $this->brandColor,
        ];
        return $this;
    }

    public function divider(): self
    {
        $this->parts[] = ['type' => 'divider'];
        return $this;
    }

    /**
     * @param string[] $items
     */
    public function bulletList(array $items): self
    {
        $this->parts[] = ['type' => 'list', 'items' => array_values($items)];
        return $this;
    }

    /**
     * A simple data table, e.g. order lines or account details.
     *
     * @param string[]   $headers Column headings.
     * @param string[][] $rows    One array of cell strings per row.
     */
    public function table(array $headers, array $rows): self
    {
        $this->parts[] = [
            'type'    => 'table',
            'headers' => array_values($headers),
            'rows'    => array_values($rows),
        ];
        return $this;
    }

    /**
     * Render the full HTML email.
     */
    public function build(): string
    {
        $body = '';
        foreach ($this->parts as $part) {
            $body .= $this->renderPart($part);
        }

        $footerHtml = '';
        if ($this->footer !== '') {
            $footerHtml = '<tr><td style="padding:24px 32px 8px 32px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#8a8f98;text-align:center;">'
                . $this->esc($this->footer) . '</td></tr>';
        }

        $headerHtml = '';
        if ($this->brand !== '') {
            $headerHtml = '<tr><td style="background-color:' . $this->brandColor . ';padding:24px 32px;font-family:Arial,Helvetica,sans-serif;">'
                . '<span style="font-size:20px;font-weight:bold;color:#ffffff;">' . $this->esc($this->brand) . '</span>'
                . ($this->tagline !== ''
                    ? '<span style="display:block;font-size:13px;color:#c8ccd4;margin-top:4px;">' . $this->esc($this->tagline) . '</span>'
                    : '')
                . '</td></tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $this->esc($this->brand) . '</title></head>'
            . '<body style="margin:0;padding:0;background-color:#f2f4f7;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f7;">'
            . '<tr><td align="center" style="padding:24px 12px;">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:8px;overflow:hidden;">'
            . $headerHtml
            . '<tr><td style="padding:32px;">' . $body . '</td></tr>'
            . $footerHtml
            . '</table></td></tr></table></body></html>';
    }

    /**
     * Render a plain-text version for clients (or inboxes) that ignore HTML.
     */
    public function plainText(): string
    {
        $lines = [];
        if ($this->brand !== '') {
            $lines[] = $this->brand;
            if ($this->tagline !== '') {
                $lines[] = $this->tagline;
            }
            $lines[] = str_repeat('=', mb_strlen($this->brand));
            $lines[] = '';
        }

        foreach ($this->parts as $part) {
            switch ($part['type']) {
                case 'heading':
                    $lines[] = $part['text'];
                    $lines[] = str_repeat('-', mb_strlen($part['text']));
                    $lines[] = '';
                    break;
                case 'paragraph':
                    $lines[] = $part['text'];
                    $lines[] = '';
                    break;
                case 'button':
                    $lines[] = $part['label'] . ': ' . $part['url'];
                    $lines[] = '';
                    break;
                case 'divider':
                    $lines[] = str_repeat('-', 40);
                    $lines[] = '';
                    break;
                case 'list':
                    foreach ($part['items'] as $item) {
                        $lines[] = '- ' . $item;
                    }
                    $lines[] = '';
                    break;
                case 'table':
                    $lines[] = implode(' | ', $part['headers']);
                    $lines[] = str_repeat('-', 40);
                    foreach ($part['rows'] as $row) {
                        $lines[] = implode(' | ', $row);
                    }
                    $lines[] = '';
                    break;
            }
        }

        if ($this->footer !== '') {
            $lines[] = $this->footer;
        }

        return rtrim(implode("\n", $lines)) . "\n";
    }

    private function renderPart(array $part): string
    {
        switch ($part['type']) {
            case 'heading':
                return '<h1 style="margin:0 0 16px 0;font-family:Arial,Helvetica,sans-serif;font-size:24px;line-height:1.3;color:#1d2327;">'
                    . $this->esc($part['text']) . '</h1>';
            case 'paragraph':
                return '<p style="margin:0 0 16px 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#3c434a;">'
                    . $this->esc($part['text']) . '</p>';
            case 'button':
                return '<p style="margin:8px 0 16px 0;">'
                    . '<a href="' . $this->esc($part['url']) . '" style="display:inline-block;background-color:' . $part['color']
                    . ';color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;text-decoration:none;padding:12px 24px;border-radius:6px;">'
                    . $this->esc($part['label']) . '</a></p>';
            case 'divider':
                return '<hr style="border:0;border-top:1px solid #e5e7eb;margin:24px 0;">';
            case 'list':
                $items = '';
                foreach ($part['items'] as $item) {
                    $items .= '<li style="margin-bottom:6px;">' . $this->esc($item) . '</li>';
                }
                return '<ul style="margin:0 0 16px 0;padding-left:20px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#3c434a;">' . $items . '</ul>';
            case 'table':
                $head = '';
                foreach ($part['headers'] as $header) {
                    $head .= '<th style="text-align:left;padding:10px 12px;background-color:#f2f4f7;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#1d2327;border-bottom:1px solid #e5e7eb;">' . $this->esc($header) . '</th>';
                }
                $rows = '';
                foreach ($part['rows'] as $row) {
                    $cells = '';
                    foreach ($row as $cell) {
                        $cells .= '<td style="padding:10px 12px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#3c434a;border-bottom:1px solid #e5e7eb;">' . $this->esc($cell) . '</td>';
                    }
                    $rows .= '<tr>' . $cells . '</tr>';
                }
                return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px 0;border-collapse:collapse;"><thead><tr>' . $head . '</tr></thead><tbody>' . $rows . '</tbody></table>';
        }
        return '';
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Accept #rgb / #rrggbb only; anything else falls back to the default.
     */
    private function sanitizeColor(string $color): string
    {
        return preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color) === 1
            ? $color
            : '#1d2327';
    }
}
