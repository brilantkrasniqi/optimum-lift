<?php

/**
 * The database state the code depends on: shared by `wp ol-shop setup` (a
 * fresh live site, inc/cli-setup.php) and `wp ol-shop seed` (local).
 *
 * The local database is never copied to production (it holds demo Products,
 * fake reviews and test orders). Instead, everything the theme and the Plans
 * plugin expect to find in the database, and that is not content someone
 * writes, is created here: the store settings, the WooCommerce pages with the
 * classic cart and checkout, the Product categories, the goal and Size
 * attributes, the front page and the legal pages. `wp ol-shop seed` builds
 * the local site on the same SiteSetup, so local and live cannot drift apart.
 *
 * Safe to run again: settings are set again, and pages and terms are created
 * only when missing.
 */

declare(strict_types=1);

namespace OptimumLift\Theme\Cli;

use RuntimeException;
use WC_Install;
use WP_Error;
use WP_Term;

/**
 * The site configuration shared by the local seed and the live setup.
 *
 * @phpstan-type SizeAttribute array{attribute: int, taxonomy: string, terms: array<string, int>}
 */
final class SiteSetup
{
    /**
     * The Size attributes (spec .scratch/diet-plans, Decision 9): term slugs
     * equal the diet renderer's codes, in the order every picker shows them.
     */
    public const SIZES = [
        'gjinia' => ['name' => 'Gjinia', 'terms' => ['mashkull' => 'Mashkull', 'femer' => 'Femër']],
        'pesha'  => ['name' => 'Pesha', 'terms' => [
            '50-60'  => '50–60 kg',
            '60-70'  => '60–70 kg',
            '70-80'  => '70–80 kg',
            '80-90'  => '80–90 kg',
            '90plus' => '90+ kg',
        ]],
    ];

    public const FRONT_PAGE = 'kreu';

    private const GOAL = 'objektivi';

    private const CATEGORIES = [
        'programe-stervitjeje' => 'Programe stërvitjeje',
        'dieta'                => 'Plane ushqimore',
        'paketa'               => 'Paketa',
    ];

    private const GOALS = [
        'humbje-yndyre'       => 'Humbje yndyre',
        'mase-force'          => 'Masë & forcë',
        'shendet-mbajtje'     => 'Shëndet & mbajtje',
        'transformim-i-plote' => 'Transformim i plotë',
    ];

    public function postId(string $postType, string $slug): int
    {
        $ids = get_posts([
            'post_type'        => $postType,
            'name'             => $slug,
            'post_status'      => 'any',
            'numberposts'      => 1,
            'fields'           => 'ids',
            'suppress_filters' => true,
        ]);

        return $ids === [] ? 0 : (int) $ids[0];
    }

    public function term(string $taxonomy, string $slug, string $name): int
    {
        $term = get_term_by('slug', $slug, $taxonomy);
        if ($term instanceof WP_Term) {
            if (wp_specialchars_decode($term->name) !== $name) {
                wp_update_term($term->term_id, $taxonomy, ['name' => $name]);
            }

            return $term->term_id;
        }

        $inserted = wp_insert_term($name, $taxonomy, ['slug' => $slug]);
        if ($inserted instanceof WP_Error) {
            throw new RuntimeException(sprintf('Could not create the term "%s": %s', $name, $inserted->get_error_message()));
        }

        return (int) $inserted['term_id'];
    }

    /**
     * The Product categories, by slug.
     *
     * @return array<string, int>
     */
    public function categories(): array
    {
        $ids = [];
        foreach (self::CATEGORIES as $slug => $name) {
            $ids[$slug] = $this->term('product_cat', $slug, $name);
        }

        return $ids;
    }

    /**
     * The global goal attribute and its terms, by name.
     *
     * @return array{attribute: int, taxonomy: string, terms: array<string, int>}
     */
    public function goals(): array
    {
        [$attribute, $taxonomy] = $this->attribute(self::GOAL, 'Objektivi');

        $terms = [];
        foreach (self::GOALS as $slug => $name) {
            $terms[$name] = $this->term($taxonomy, $slug, $name);
        }

        return ['attribute' => $attribute, 'taxonomy' => $taxonomy, 'terms' => $terms];
    }

    /**
     * The Size attributes (Gjinia, Pesha) and their terms, in order, by slug.
     *
     * @return array<string, SizeAttribute>
     */
    public function sizes(): array
    {
        $sizes = [];
        foreach (self::SIZES as $slug => $size) {
            [$attribute, $taxonomy] = $this->attribute($slug, $size['name']);

            $ids   = [];
            $index = 0;
            foreach ($size['terms'] as $termSlug => $termName) {
                $ids[(string) $termSlug] = $this->term($taxonomy, (string) $termSlug, $termName);
                wc_set_term_order($ids[(string) $termSlug], $index++, $taxonomy);
            }

            $sizes[$slug] = ['attribute' => $attribute, 'taxonomy' => $taxonomy, 'terms' => $ids];
        }

        return $sizes;
    }

    /**
     * The front page, created when missing.
     */
    public function frontPage(): int
    {
        $id = wp_insert_post([
            'ID'           => $this->postId('page', self::FRONT_PAGE),
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => 'Kreu',
            'post_name'    => self::FRONT_PAGE,
            'post_content' => '',
        ], true);
        if ($id instanceof WP_Error) {
            throw new RuntimeException('Could not save the front page: ' . $id->get_error_message());
        }

        update_option('show_on_front', 'page');
        update_option('page_on_front', $id);

        return $id;
    }

    /**
     * The settings the storefront expects, in WordPress and WooCommerce.
     */
    public function settings(): void
    {
        $options = [
            // The home page's <title> and link previews read the tagline.
            'blogdescription'                  => 'Programe stërvitjeje dhe plane ushqimore në shqip',
            // Kosovo's zone, so offers end and orders are dated in local time.
            'timezone_string'                  => 'Europe/Belgrade',
            'date_format'                      => 'j F Y',
            'woocommerce_coming_soon'          => 'no',
            'woocommerce_currency'             => 'EUR',
            'woocommerce_default_country'      => 'XK',
            'woocommerce_price_thousand_sep'   => '.',
            'woocommerce_price_decimal_sep'    => ',',
            'woocommerce_price_num_decimals'   => '2',
            'woocommerce_currency_pos'         => 'right_space',
            'woocommerce_enable_reviews'       => 'yes',
            'woocommerce_enable_review_rating' => 'yes',
            'woocommerce_email_from_name'      => 'Optimum Lift',
            // The header's account button offers "Create an account". Nobody
            // picks a password: inc/shop/login-link.php generates one and
            // sends a login link instead (ADR-0014).
            'woocommerce_enable_myaccount_registration'  => 'yes',
            'woocommerce_registration_generate_password' => 'no',
            // Settings, so WooCommerce stores them in the language it was
            // installed in (English) unless they are set.
            'woocommerce_registration_privacy_policy_text' => 'Të dhënat e tua përdoren për të menaxhuar llogarinë tënde. Më shumë te faqja [privacy_policy].',
            'woocommerce_checkout_privacy_policy_text'     => 'Të dhënat e tua përdoren për të përpunuar porosinë dhe për llogarinë tënde. Më shumë te faqja [privacy_policy].',
            // A terms page turns on WooCommerce's required terms checkbox. The
            // checkout asks for as little as possible (ADR-0007), so it stays
            // off: the one box a buyer must tick is the withdrawal waiver
            // (inc/shop/withdrawal.php, ADR-0009).
            'woocommerce_checkout_terms_and_conditions_checkbox_text' => '',
            'woocommerce_onboarding_profile'   => ['skipped' => true],
        ];
        foreach ($options as $name => $value) {
            update_option($name, $value);
        }

        // The storefront is Albanian; wp-admin stays English for whoever builds it.
        foreach (get_users(['role' => 'administrator', 'fields' => 'ID']) as $userId) {
            update_user_meta((int) $userId, 'locale', 'en_US');
        }
    }

    /**
     * The shop, cart, checkout and My Account pages, with the classic cart
     * and checkout (ADR-0007) and Albanian titles.
     */
    public function shopPages(): void
    {
        foreach (['shop', 'cart', 'checkout', 'myaccount'] as $page) {
            if (get_post(wc_get_page_id($page)) === null) {
                WC_Install::create_pages();
                break;
            }
        }

        foreach (['cart' => 'woocommerce_cart', 'checkout' => 'woocommerce_checkout'] as $page => $shortcode) {
            wp_update_post([
                'ID'           => wc_get_page_id($page),
                'post_content' => sprintf('<!-- wp:shortcode -->[%s]<!-- /wp:shortcode -->', $shortcode),
            ]);
        }

        // WooCommerce names its pages in English. The titles show in the
        // browser tab, on cart and My Account, and in My Account's eyebrow.
        $titles = ['shop' => 'Dyqani', 'cart' => 'Shporta', 'checkout' => 'Pagesa', 'myaccount' => 'Llogaria ime'];
        foreach ($titles as $page => $title) {
            if (wc_get_page_id($page) > 0) {
                wp_update_post(['ID' => wc_get_page_id($page), 'post_title' => $title]);
            }
        }
    }

    /**
     * The terms, privacy and refund pages the footer and checkout link to.
     *
     * Locally ($overwrite) they are published with placeholder text marked
     * for replacement, every run. Live, a page already published is left
     * alone, and a missing one is created as a draft with the placeholder, so
     * no placeholder legal text goes public: its real text is pasted in and
     * it is published by hand.
     *
     * @return list<string> The titles of the pages still in draft.
     */
    public function legalPages(bool $overwrite): array
    {
        $email = (string) optimum_lift_setting('contact_email');
        $pages = [
            'woocommerce_terms_page_id' => ['kushtet-e-sherbimit', 'Kushtet e shërbimit', [
                'Këto kushte rregullojnë blerjen dhe përdorimin e produkteve digjitale të Optimum Lift: programe stërvitjeje dhe plane ushqimore. Duke blerë, i pranon ato.',
                'Produktet janë për përdorim personal. Nuk lejohet shpërndarja, rishitja ose publikimi i materialeve pa leje me shkrim.',
                'Informacioni në produkte nuk zëvendëson këshillën mjekësore. Konsultohu me mjekun para se të fillosh një program stërvitjeje ose dietë.',
            ]],
            'wp_page_for_privacy_policy' => ['politika-e-privatesise', 'Politika e privatësisë', [
                'Në pagesë mbledhim vetëm email-in, emrin dhe shtetin, që të përpunojmë porosinë dhe të të dërgojmë produktin.',
                'Pagesat me kartë i përpunon ofruesi i pagesave. Ne nuk i shohim dhe nuk i ruajmë të dhënat e kartës.',
                sprintf('Për të parë ose fshirë të dhënat e tua, shkruaj në %s.', $email),
            ]],
            'woocommerce_refund_returns_page_id' => ['politika-e-kthimit', 'Politika e kthimit', [
                'Produktet e Optimum Lift janë digjitale dhe i merr menjëherë pas pagesës. Në pagesë kërkon që aksesi të nisë menjëherë dhe pranon që, sapo nis, humb të drejtën e tërheqjes brenda 14 ditëve. Prandaj, pasi aksesi është dhënë, nuk kthejmë para.',
                sprintf('Nëse produkti nuk hapet, ka gabim ose nuk është siç përshkruhet, shkruaj në %s me numrin e porosisë dhe e rregullojmë ose ta zëvendësojmë. Kjo nuk prek të drejtat që të jep ligji.', $email),
            ]],
        ];

        $drafts = [];
        foreach ($pages as $option => [$slug, $title, $paragraphs]) {
            $id = (int) get_option($option);
            if ($id <= 0 || get_post($id) === null) {
                $id = $this->postId('page', $slug);
            }

            if (!$overwrite && $id > 0 && get_post_status($id) === 'publish') {
                update_option($option, $id);
                continue;
            }

            array_unshift($paragraphs, '<strong>[Tekst shembull: zëvendësoje me tekstin ligjor të rishikuar para lansimit.]</strong>');
            $content = implode("\n\n", array_map(
                static fn (string $paragraph): string => '<!-- wp:paragraph --><p>' . $paragraph . '</p><!-- /wp:paragraph -->',
                $paragraphs
            ));

            // Live, a draft someone already started keeps its text.
            $keep = !$overwrite && $id > 0 && get_post_field('post_name', $id) === $slug;

            $saved = wp_insert_post([
                'ID'           => $id,
                'post_type'    => 'page',
                'post_status'  => $overwrite ? 'publish' : 'draft',
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_content' => $keep ? (string) get_post_field('post_content', $id) : $content,
            ], true);
            if ($saved instanceof WP_Error) {
                throw new RuntimeException(sprintf('Could not save the page "%s": %s', $title, $saved->get_error_message()));
            }

            update_option($option, $saved);
            if (!$overwrite) {
                $drafts[] = $title;
            }
        }

        return $drafts;
    }

    /**
     * WordPress keeps WPLANG only for an installed language; docker/setup.sh
     * and deploy/bootstrap-live.sh install the Albanian packs.
     */
    public function siteLanguage(): string
    {
        if (!in_array('sq', get_available_languages(), true)) {
            return 'The site language stays English until the sq language pack is installed: wp language core install sq';
        }

        update_option('WPLANG', 'sq');

        return 'Site language: sq.';
    }

    /**
     * A global attribute with custom term order, created when missing and
     * left alone when present. Returns its ID and taxonomy.
     *
     * @return array{0: int, 1: string}
     */
    private function attribute(string $slug, string $name): array
    {
        $attribute = wc_attribute_taxonomy_id_by_name($slug);
        if ($attribute === 0) {
            $created = wc_create_attribute([
                'name'         => $name,
                'slug'         => $slug,
                'type'         => 'select',
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ]);
            if ($created instanceof WP_Error) {
                throw new RuntimeException(sprintf('Could not create the attribute "%s": %s', $name, $created->get_error_message()));
            }
            $attribute = $created;
        }

        // WooCommerce registers attribute taxonomies on init, before this
        // request created the attribute.
        $taxonomy = wc_attribute_taxonomy_name($slug);
        if (!taxonomy_exists($taxonomy)) {
            register_taxonomy($taxonomy, ['product'], ['hierarchical' => false, 'show_ui' => false, 'rewrite' => false]);
        }

        return [$attribute, $taxonomy];
    }
}
