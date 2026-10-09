<?php

/**
 * `wp ol-shop setup`: the database state the code depends on, for a fresh
 * live site. What it creates is SiteSetup (inc/site-setup.php).
 */

declare(strict_types=1);

namespace OptimumLift\Theme\Cli;

use RuntimeException;
use WP_CLI;

/**
 * Sets up a fresh live site's database.
 */
final class SetupCommand
{
    /**
     * WordPress's and WooCommerce's sample content, by post type and slug.
     */
    private const SAMPLES = [
        'post' => ['hello-world'],
        'page' => ['sample-page', 'refund_returns'],
    ];

    /**
     * Creates the database state the theme and the Plans plugin depend on.
     *
     * For a fresh live site; run by deploy/bootstrap-live.sh. Sets the store
     * settings (currency, price format, timezone, registration, the checkout
     * texts), turns off the offline payment methods (cash on delivery would
     * grant Plan Access without payment), creates the shop pages with the
     * classic cart and checkout, the Product categories, the goal and Size
     * attributes (Gjinia, Pesha), the front page and the legal pages (as
     * drafts), sets permalinks and the site language, closes comments and
     * deletes WordPress's sample post and page.
     *
     * Safe to run again. The settings above are set again (a tagline changed
     * in wp-admin goes back); pages, terms and attributes are only created
     * when missing, and published legal pages are left alone. Creates no
     * Products, reviews, sales or coupons: those are demo data
     * (`wp ol-shop seed`) or real content you enter.
     *
     * ## OPTIONS
     *
     * [--email-from=<address>]
     * : The address WooCommerce sends from, on the shop's domain.
     *
     * ## EXAMPLES
     *
     *     wp ol-shop setup --email-from=info@optimumlift.com
     *
     * @param list<string>              $args
     * @param array<string, string|true> $assoc
     */
    public function __invoke(array $args, array $assoc): void
    {
        try {
            foreach ($this->run($assoc) as $line) {
                WP_CLI::log($line);
            }
            WP_CLI::success('The site is set up.');
        } catch (RuntimeException $e) {
            WP_CLI::error($e->getMessage());
        }
    }

    /**
     * @param array<string, string|true> $assoc
     * @return list<string>
     */
    private function run(array $assoc): array
    {
        if (!function_exists('WC')) {
            throw new RuntimeException('WooCommerce must be active.');
        }

        $site = new SiteSetup();
        $log  = [];

        $site->settings();
        $email = isset($assoc['email-from']) && is_string($assoc['email-from']) ? sanitize_email($assoc['email-from']) : '';
        if ($email !== '') {
            update_option('woocommerce_email_from_address', $email);
            $log[] = sprintf('Emails are sent from %s.', $email);
        } else {
            $log[] = sprintf('Emails are sent from %s; pass --email-from to change it.', (string) get_option('woocommerce_email_from_address'));
        }

        // Comments: the store has no blog, and Product reviews are WooCommerce's own.
        update_option('default_comment_status', 'closed');
        update_option('default_ping_status', 'closed');

        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure('/%postname%/');

        $log[] = $this->offlinePayments();
        $this->deleteSamples();

        $site->shopPages();
        $site->categories();
        $site->goals();
        $site->sizes();
        $site->frontPage();

        $drafts = $site->legalPages(false);
        if ($drafts !== []) {
            $log[] = sprintf('Legal pages waiting for their text, then Publish: %s.', implode(', ', $drafts));
        }

        $log[] = $site->siteLanguage();

        flush_rewrite_rules();

        if (!function_exists('acf_get_field')) {
            $log[] = 'ACF Pro is not active: upload it and activate it, or the Plans and the Sections fields will not load.';
        }

        return $log;
    }

    /**
     * Cash on delivery, bank transfer and cheque move an order on without a
     * card payment, and a processing order grants Plan Access.
     */
    private function offlinePayments(): string
    {
        foreach (['cod', 'bacs', 'cheque'] as $gateway) {
            $option   = sprintf('woocommerce_%s_settings', $gateway);
            $settings = get_option($option, []);
            $settings = is_array($settings) ? $settings : [];
            $settings['enabled'] = 'no';
            update_option($option, $settings);
        }

        return 'Cash on delivery, bank transfer and cheque are off.';
    }

    private function deleteSamples(): void
    {
        $site = new SiteSetup();
        foreach (self::SAMPLES as $postType => $slugs) {
            foreach ($slugs as $slug) {
                $id = $site->postId($postType, $slug);
                if ($id > 0) {
                    wp_delete_post($id, true);
                }
            }
        }
    }
}

WP_CLI::add_command('ol-shop setup', SetupCommand::class);
