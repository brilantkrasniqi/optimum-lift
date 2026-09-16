<?php

/**
 * Renders a Plan to PDF with Dompdf (ADR-0005).
 *
 * The cache key is a hash of the rendered HTML, not a modified date: an edit to
 * the Plan, to any Exercise it uses, or to the template changes the HTML, so a
 * stale PDF is never served. Building the HTML takes milliseconds; Dompdf takes
 * seconds.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Download;

use Dompdf\Dompdf;
use Dompdf\Options;
use OptimumLift\Plans\Plan\Plan;
use OptimumLift\Plans\Portal\Templates;
use RuntimeException;

final class PdfRenderer
{
    public static function available(): bool
    {
        return class_exists(Dompdf::class);
    }

    /**
     * Absolute path to the Plan's PDF, rendering it first if the cached copy
     * is missing or stale.
     */
    public function file(Plan $plan): string
    {
        if (!self::available()) {
            throw new RuntimeException('Dompdf is not installed. Run `npm run composer -- install`.');
        }

        $html = $this->html($plan);
        $dir  = self::cacheDir();
        $hash = substr(wp_hash($plan->id . '|' . $html), 0, 20);
        $path = sprintf('%s/plan-%d-%s.pdf', $dir, $plan->id, $hash);

        if (is_file($path)) {
            return $path;
        }

        $pdf = $this->render($html);

        foreach (glob(sprintf('%s/plan-%d-*.pdf', $dir, $plan->id)) ?: [] as $stale) {
            wp_delete_file($stale);
        }

        // Write then rename, so a concurrent request never serves half a file.
        $tmp = $path . '.' . wp_generate_password(6, false) . '.tmp';
        file_put_contents($tmp, $pdf);
        rename($tmp, $path);

        return $path;
    }

    public function html(Plan $plan): string
    {
        return Templates::capture('pdf/plan', ['plan' => $plan]);
    }

    public static function filename(Plan $plan): string
    {
        return sanitize_file_name(sanitize_title($plan->title) . '.pdf');
    }

    private function render(string $html): string
    {
        $uploads = wp_get_upload_dir();
        $options = new Options();

        $options->setChroot([$uploads['basedir'], \OptimumLift\Plans\DIR]);
        $options->setIsRemoteEnabled(false);
        $options->setFontDir(self::cacheDir() . '/fonts');
        $options->setFontCache(self::cacheDir() . '/fonts');
        $options->setTempDir(get_temp_dir());
        // DejaVu ships with Dompdf and covers Albanian (ë, ç).
        $options->setDefaultFont('DejaVu Sans');
        $options->setDefaultPaperSize('a4');
        $options->setDpi(96);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $dompdf->getCanvas()->page_text(
            520,
            810,
            '{PAGE_NUM} / {PAGE_COUNT}',
            $dompdf->getFontMetrics()->getFont('DejaVu Sans'),
            8,
            [0.36, 0.4, 0.44]
        );

        return (string) $dompdf->output();
    }

    /**
     * uploads/ol-plans/, closed to direct web access. Apache honours the
     * .htaccess; on other servers the unguessable file hash is the safeguard.
     */
    public static function cacheDir(): string
    {
        $dir = wp_get_upload_dir()['basedir'] . '/ol-plans';

        if (!is_dir($dir . '/fonts')) {
            wp_mkdir_p($dir . '/fonts');
        }

        if (!is_file($dir . '/.htaccess')) {
            file_put_contents($dir . '/.htaccess', "Require all denied\n");
            file_put_contents($dir . '/index.php', "<?php\n// Silence.\n");
        }

        return $dir;
    }
}
