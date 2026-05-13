<?php
/**
 * Field formatting for product opening / Add Product modal (same as product-opening.php top helpers).
 */
if (!function_exists('format_decimal_display')) {
    function format_decimal_display($val) {
        if ($val === '' || $val === null) {
            return '';
        }
        $v = trim((string) $val);
        if ($v === '') {
            return '';
        }
        if (!is_numeric($v)) {
            return htmlspecialchars($v);
        }
        $f = (float) $v;
        if ($f == (int) $f && (string) (int) $f === (string) $f) {
            return (string) (int) $f;
        }

        return rtrim(rtrim(number_format($f, 4, '.', ''), '0'), '.');
    }
}

if (!function_exists('opening_purity_field_value')) {
    function opening_purity_field_value($metal_display_name, $char_data) {
        if ($char_data && array_key_exists('opening_purity', $char_data) && $char_data['opening_purity'] !== null && $char_data['opening_purity'] !== '') {
            return format_decimal_display($char_data['opening_purity']);
        }
        $n = trim((string) $metal_display_name);
        if (in_array($n, ['Gold', 'Silver', 'Platinum'], true)) {
            return '1';
        }

        return '';
    }
}

if (!function_exists('opening_barcode_prefix_default')) {
    function opening_barcode_prefix_default($metal_display_name) {
        $n = trim((string) $metal_display_name);
        $defaults = [
            'Gold' => 'GD',
            'Silver' => 'SV',
            'Diamond & Stones' => 'DM',
        ];

        return $defaults[$n] ?? 'RN';
    }
}

if (!function_exists('opening_barcode_prefix_value')) {
    function opening_barcode_prefix_value($metal_display_name, $char_data) {
        if ($char_data && array_key_exists('barcode_prefix', $char_data)) {
            $saved = trim((string) $char_data['barcode_prefix']);
            if ($saved !== '') {
                return $saved;
            }
        }

        return opening_barcode_prefix_default($metal_display_name);
    }
}
