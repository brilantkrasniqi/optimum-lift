<?php
/**
 * Inline SVG icons from assets/icons/<name>.svg.
 *
 * Each file is a 24×24 stroke (or fill) icon drawn with currentColor, so the
 * text colour class sets its colour. Add an icon by adding a file; the ACF
 * `icon` selects list the same directory.
 */

declare(strict_types=1);

/**
 * An icon's markup, decorative (aria-hidden) unless $attrs says otherwise.
 *
 * $attrs override the file's attributes, e.g. ['stroke-width' => '1.5'] for a
 * large placeholder, or ['aria-hidden' => null, 'role' => 'img',
 * 'aria-label' => '…'] for a meaningful icon. A null or false value removes
 * the attribute. Returns '' for an unknown icon.
 *
 * @param array<string, string|int|float|bool|null> $attrs
 */
function optimum_lift_icon(string $name, string $class = 'w-4 h-4', array $attrs = []): string
{
    static $cache = [];

    if (!array_key_exists($name, $cache)) {
        $cache[$name] = optimum_lift_read_icon($name);
    }

    if ($cache[$name] === null) {
        _doing_it_wrong(__FUNCTION__, esc_html(sprintf('Icon "%s" does not exist in assets/icons/.', $name)), '0.1.0');

        return '';
    }

    $attributes = array_merge(
        $cache[$name]['attributes'],
        ['class' => $class, 'aria-hidden' => 'true', 'focusable' => 'false'],
        $attrs
    );

    $html = '<svg';
    foreach ($attributes as $attribute => $value) {
        if ($value === null || $value === false || ($attribute === 'class' && $value === '')) {
            continue;
        }

        $html .= sprintf(' %s="%s"', esc_attr($attribute), esc_attr($value === true ? $attribute : (string) $value));
    }

    return $html . '>' . $cache[$name]['inner'] . '</svg>';
}

/**
 * Parses an icon file into its root attributes and inner markup. The files
 * ship with the theme, so their inner markup is trusted as-is.
 *
 * @return array{attributes: array<string, string>, inner: string}|null
 */
function optimum_lift_read_icon(string $name): ?array
{
    if (preg_match('/^[a-z0-9-]+$/', $name) !== 1) {
        return null;
    }

    $path = OPTIMUM_LIFT_DIR . '/assets/icons/' . $name . '.svg';
    $svg  = is_readable($path) ? file_get_contents($path) : false;

    if ($svg === false || preg_match('/<svg\b([^>]*)>(.*)<\/svg>/s', $svg, $parts) !== 1) {
        return null;
    }

    preg_match_all('/([a-zA-Z_:][\w:.-]*)="([^"]*)"/', $parts[1], $pairs, PREG_SET_ORDER);

    $attributes = [];
    foreach ($pairs as $pair) {
        if ($pair[1] !== 'xmlns') {
            $attributes[$pair[1]] = $pair[2];
        }
    }

    return ['attributes' => $attributes, 'inner' => trim($parts[2])];
}
