<?php

function sanitizeSpreadsheetCell($value): string
{
    $text = trim((string) ($value ?? ''));
    if ($text === '') {
        return '';
    }

    $first = substr($text, 0, 1);
    if (in_array($first, ['=', '+', '-', '@'], true)) {
        return "'" . $text;
    }

    return $text;
}
