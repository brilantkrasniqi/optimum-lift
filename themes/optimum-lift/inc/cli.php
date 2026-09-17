<?php

/**
 * `wp ol-shop seed`: the demo catalogue and the local site configuration.
 *
 * Loaded only under WP-CLI. The copy is transcribed from the design mocks
 * (index.html, produkt.html, produkt-dieta.html) and written in the same voice
 * for the Products the mocks have no page for.
 *
 * Placeholders to replace with real, consented content before launch:
 * testimonials, results and the marquee, the homepage hero's before/after and
 * progress card, the goal tabs' customer averages and typical results, the
 * trainer, "WhatsApp 7/7", value-stack values, the shopping-list cost and the
 * legal pages. Guarantee copy outside {guarantee_days} tokens (headings,
 * intros, reassurance lines, comparison cells, FAQ answers, Product
 * descriptions) says 30 days: keep it equal to the Customizer setting.
 */

declare(strict_types=1);

namespace OptimumLift\Theme\Cli;

use DateInterval;
use DateTimeImmutable;
use RuntimeException;
use WC_Comments;
use WC_Coupon;
use WC_Install;
use WC_Product_Attribute;
use WC_Product_Simple;
use WP_CLI;
use WP_Comment;
use WP_Error;
use WP_Term;

/**
 * Demo storefront data for local development.
 *
 * @phpstan-type Review array{0: string, 1: int, 2: string}
 * @phpstan-type Row array<string, mixed>
 * @phpstan-type ProductData array{
 *     title: string,
 *     excerpt: string,
 *     description: string,
 *     category: string,
 *     goal: string,
 *     regular: string,
 *     sale: string,
 *     featured: bool,
 *     menu_order: int,
 *     age_days: int,
 *     sales: int,
 *     cross_sells: list<string>,
 *     fields: array<string, mixed>,
 *     reviews: list<Review>
 * }
 */
final class ShopCommand
{
    private const ENVIRONMENTS = ['local', 'development'];

    private const PROGRAM = 'programi-i-stervitjes-12-javor';
    private const FORCE   = 'force-mase';
    private const DIET    = 'plani-ushqimor-12-javor';
    private const MED     = 'dieta-mesdhetare';
    private const BUNDLE  = 'transformimi-total';

    private const FRONT_PAGE = 'kreu';
    private const COUPON     = 'OPTIMUM10';
    private const GOAL       = 'objektivi';
    private const PLAN_TYPE  = 'ol_training_plan';

    /**
     * @var array<string, int> Product IDs by slug.
     */
    private array $ids = [];

    /**
     * @var array<string, string> Fields ACF does not know, so their content was not saved.
     */
    private array $unknownFields = [];

    /**
     * Seeds the demo catalogue and configures the local site.
     *
     * Creates or updates, by slug: the Product categories and the goal
     * attribute, five Products with their sales fields, sections, cross-sells,
     * demo sales and demo reviews, the front page, the OPTIMUM10 coupon, and
     * the site settings the storefront expects. Runs only when the environment
     * type is local or development.
     *
     * ## OPTIONS
     *
     * [--reset]
     * : Delete the seeded Products (with their reviews), the front page and the coupon first.
     *
     * ## EXAMPLES
     *
     *     wp ol-shop seed
     *     wp ol-shop seed --reset
     *
     * @param list<string>              $args
     * @param array<string, string|true> $assoc
     */
    public function seed(array $args, array $assoc): void
    {
        try {
            WP_CLI::success($this->run(isset($assoc['reset'])));
        } catch (RuntimeException $e) {
            WP_CLI::error($e->getMessage());
        }
    }

    private function run(bool $reset): string
    {
        $environment = wp_get_environment_type();
        if (!in_array($environment, self::ENVIRONMENTS, true)) {
            throw new RuntimeException(sprintf(
                'Refusing to seed a "%s" environment. Set WP_ENVIRONMENT_TYPE to local or development (docker-compose.yml sets it for the wpcli service).',
                $environment
            ));
        }

        if (!function_exists('WC') || !function_exists('acf_get_field')) {
            throw new RuntimeException('WooCommerce and ACF Pro must be active.');
        }

        $catalogue = $this->catalogue();

        if ($reset) {
            $this->reset(array_keys($catalogue));
        }

        $offerEnd = (new DateTimeImmutable('now', wp_timezone()))->setTime(23, 59)->add(new DateInterval('P3D'));

        $categories = [
            'programe-stervitjeje' => $this->term('product_cat', 'programe-stervitjeje', 'Programe stërvitjeje'),
            'dieta'                => $this->term('product_cat', 'dieta', 'Plane ushqimore'),
            'paketa'               => $this->term('product_cat', 'paketa', 'Paketa'),
        ];
        $goals = $this->goals();

        foreach ($catalogue as $slug => $data) {
            $this->ids[$slug] = $this->saveProduct($slug, $data, $categories, $goals, $offerEnd);
        }

        // Cross-sells, components and section links point at other Products,
        // so they are written once every Product has an ID.
        foreach ($catalogue as $slug => $data) {
            $this->saveCrossSells($slug, $data['cross_sells']);
            $this->saveFields($this->ids[$slug], $data['fields'] + ['field_olt_blocks' => $this->blocks($slug)]);
            $this->saveReviews($this->ids[$slug], $data['age_days'], $data['reviews']);
        }

        $this->saveFields($this->ids[self::BUNDLE], [
            'field_olt_bundle_components' => array_map(
                fn (string $slug): int => $this->ids[$slug],
                [self::PROGRAM, self::FORCE, self::DIET, self::MED]
            ),
        ]);

        $plan = $this->linkPlan();

        $frontPage = $this->frontPage();
        $this->saveFields($frontPage, ['field_olt_blocks' => $this->homeBlocks()]);

        $this->coupon();
        $this->configure($offerEnd);
        $this->legalPages();
        $language = $this->siteLanguage();

        if ($this->unknownFields !== []) {
            throw new RuntimeException(sprintf(
                'Seeded, but ACF does not know these fields, so their content was not saved: %s. Check inc/shop/fields.php.',
                implode(', ', $this->unknownFields)
            ));
        }

        return sprintf(
            'Seeded %d Products, the front page (%d), coupon %s and the site settings; offers end %s. %s %s',
            count($this->ids),
            $frontPage,
            self::COUPON,
            $offerEnd->format('Y-m-d H:i'),
            $plan,
            $language
        );
    }

    /**
     * @param list<string> $slugs The seeded Products.
     */
    private function reset(array $slugs): void
    {
        foreach ($slugs as $slug) {
            $product = wc_get_product($this->postId('product', $slug));
            if ($product) {
                $product->delete(true);
            }
        }

        $page = $this->postId('page', self::FRONT_PAGE);
        if ($page > 0) {
            wp_delete_post($page, true);
        }

        $coupon = new WC_Coupon(self::COUPON);
        if ($coupon->get_id() > 0) {
            $coupon->delete(true);
        }
    }

    private function postId(string $postType, string $slug): int
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

    private function term(string $taxonomy, string $slug, string $name): int
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
     * The global goal attribute and its terms.
     *
     * @return array{attribute: int, taxonomy: string, terms: array<string, int>}
     */
    private function goals(): array
    {
        $attribute = wc_attribute_taxonomy_id_by_name(self::GOAL);
        if ($attribute === 0) {
            $created = wc_create_attribute([
                'name'         => 'Objektivi',
                'slug'         => self::GOAL,
                'type'         => 'select',
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ]);
            if ($created instanceof WP_Error) {
                throw new RuntimeException('Could not create the goal attribute: ' . $created->get_error_message());
            }
            $attribute = $created;
        }

        // WooCommerce registers attribute taxonomies on init, before this
        // request created the attribute.
        $taxonomy = wc_attribute_taxonomy_name(self::GOAL);
        if (!taxonomy_exists($taxonomy)) {
            register_taxonomy($taxonomy, ['product'], ['hierarchical' => false, 'show_ui' => false, 'rewrite' => false]);
        }

        $terms = [];
        foreach (
            [
                'humbje-yndyre'       => 'Humbje yndyre',
                'mase-force'          => 'Masë & forcë',
                'shendet-mbajtje'     => 'Shëndet & mbajtje',
                'transformim-i-plote' => 'Transformim i plotë',
            ] as $slug => $name
        ) {
            $terms[$name] = $this->term($taxonomy, $slug, $name);
        }

        return ['attribute' => $attribute, 'taxonomy' => $taxonomy, 'terms' => $terms];
    }

    /**
     * @param ProductData                                                         $data
     * @param array<string, int>                                                  $categories
     * @param array{attribute: int, taxonomy: string, terms: array<string, int>} $goals
     */
    private function saveProduct(string $slug, array $data, array $categories, array $goals, DateTimeImmutable $offerEnd): int
    {
        $product = new WC_Product_Simple($this->postId('product', $slug));
        $onSale  = $data['sale'] !== '';

        $product->set_name($data['title']);
        $product->set_slug($slug);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_featured($data['featured']);
        $product->set_menu_order($data['menu_order']);
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product->set_reviews_allowed(true);
        $product->set_short_description($data['excerpt']);
        $product->set_description($data['description']);
        $product->set_category_ids([$categories[$data['category']]]);
        $product->set_regular_price($data['regular']);
        $product->set_sale_price($data['sale']);
        $product->set_date_on_sale_from($onSale ? (new DateTimeImmutable('today', wp_timezone()))->getTimestamp() : null);
        $product->set_date_on_sale_to($onSale ? $offerEnd->getTimestamp() : null);
        // Demo proof: a sales count the thresholds can show, and a publish date
        // that makes only the newest Product "new".
        $product->set_total_sales($data['sales']);
        $product->set_date_created(time() - $data['age_days'] * DAY_IN_SECONDS);

        $goal = new WC_Product_Attribute();
        $goal->set_id($goals['attribute']);
        $goal->set_name($goals['taxonomy']);
        $goal->set_options([$goals['terms'][$data['goal']]]);
        $goal->set_visible(true);
        $goal->set_variation(false);
        $product->set_attributes([$goal]);

        return $product->save();
    }

    /**
     * @param list<string> $slugs
     */
    private function saveCrossSells(string $slug, array $slugs): void
    {
        $product = wc_get_product($this->ids[$slug]);
        if (!$product) {
            throw new RuntimeException(sprintf('Product "%s" vanished while seeding.', $slug));
        }

        $product->set_cross_sell_ids(array_map(fn (string $other): int => $this->ids[$other], $slugs));
        $product->save();
    }

    /**
     * Writes top-level fields by key. A field or sub field ACF does not know
     * would be saved under a meaningless meta key or dropped, so it is skipped
     * and reported instead.
     *
     * @param array<string, mixed> $fields
     */
    private function saveFields(int $postId, array $fields): void
    {
        foreach ($fields as $key => $value) {
            $field = acf_get_field($key);
            if (!is_array($field)) {
                $this->unknownFields[$key] = $key;
                continue;
            }

            $unknown = $this->unknownSubFields($field, $value, $key);
            if ($unknown !== []) {
                $this->unknownFields += array_combine($unknown, $unknown);
            }

            update_field($key, $value, $postId);
        }
    }

    /**
     * Paths of sub field names in a repeater or flexible content value that the
     * field does not define.
     *
     * @param array<string, mixed> $field
     * @return list<string>
     */
    private function unknownSubFields(array $field, mixed $value, string $path): array
    {
        if (!is_array($value)) {
            return [];
        }

        if ($field['type'] === 'repeater') {
            $subFields = is_array($field['sub_fields'] ?? null) ? $field['sub_fields'] : [];

            return $this->unknownInRows($subFields, $value, $path);
        }

        if ($field['type'] !== 'flexible_content') {
            return [];
        }

        $layouts = array_column(is_array($field['layouts'] ?? null) ? $field['layouts'] : [], null, 'name');
        $unknown = [];

        foreach ($value as $row) {
            $name   = is_array($row) ? (string) ($row['acf_fc_layout'] ?? '') : '';
            $layout = $layouts[$name] ?? null;

            if (!is_array($layout) || !is_array($layout['sub_fields'] ?? null)) {
                $unknown[] = $path . '/' . $name;
                continue;
            }

            $unknown = array_merge($unknown, $this->unknownInRows($layout['sub_fields'], [$row], $path . '/' . $name));
        }

        return array_values(array_unique($unknown));
    }

    /**
     * @param array<mixed> $subFields
     * @param array<mixed> $rows
     * @return list<string>
     */
    private function unknownInRows(array $subFields, array $rows, string $path): array
    {
        $byName  = array_column($subFields, null, 'name');
        $unknown = [];

        foreach ($rows as $row) {
            foreach (is_array($row) ? $row : [] as $name => $value) {
                if ($name === 'acf_fc_layout') {
                    continue;
                }

                $subField = $byName[$name] ?? null;
                if (!is_array($subField)) {
                    $unknown[] = $path . '/' . $name;
                    continue;
                }

                $unknown = array_merge($unknown, $this->unknownSubFields($subField, $value, $path . '/' . $name));
            }
        }

        return array_values(array_unique($unknown));
    }

    /**
     * Demo reviews, matched by author so a rerun updates them in place.
     *
     * @param list<Review> $reviews Newest first.
     */
    private function saveReviews(int $productId, int $ageDays, array $reviews): void
    {
        $existing = [];
        foreach (get_comments(['post_id' => $productId, 'type' => 'review', 'status' => 'all']) as $comment) {
            if ($comment instanceof WP_Comment && str_starts_with($comment->comment_author, 'Demo ')) {
                $existing[$comment->comment_author] = (int) $comment->comment_ID;
            }
        }

        foreach ($reviews as $i => [$author, $rating, $text]) {
            $time = time() - (int) round(($i + 1) * $ageDays / (count($reviews) + 1) * DAY_IN_SECONDS);
            $data = [
                'comment_post_ID'      => $productId,
                'comment_author'       => $author,
                'comment_author_email' => sanitize_title($author) . '@example.com',
                'comment_content'      => $text,
                'comment_type'         => 'review',
                'comment_approved'     => 1,
                'comment_date'         => (string) wp_date('Y-m-d H:i:s', $time),
                'comment_date_gmt'     => gmdate('Y-m-d H:i:s', $time),
            ];

            if (isset($existing[$author])) {
                $id = $existing[$author];
                wp_update_comment(['comment_ID' => $id] + $data);
                unset($existing[$author]);
            } else {
                $id = (int) wp_insert_comment($data);
                if ($id === 0) {
                    throw new RuntimeException(sprintf('Could not save the review by %s.', $author));
                }
            }

            update_comment_meta($id, 'rating', $rating);
            update_comment_meta($id, 'verified', 0);
        }

        foreach ($existing as $id) {
            wp_delete_comment($id, true);
        }

        WC_Comments::clear_transients($productId);
    }

    /**
     * Gives Access to the first Training Plan through the program and the
     * bundle, which contains the program.
     */
    private function linkPlan(): string
    {
        if (!post_type_exists(self::PLAN_TYPE)) {
            return 'No Training Plan linked: the Plans plugin is not active.';
        }

        $plans = get_posts([
            'post_type'   => self::PLAN_TYPE,
            'post_status' => 'publish',
            'numberposts' => 1,
            'orderby'     => 'ID',
            'order'       => 'ASC',
            'fields'      => 'ids',
        ]);
        if ($plans === []) {
            return 'No Training Plan linked: run `wp ol-plans seed` first, then seed again.';
        }

        $plan = (int) $plans[0];
        $this->saveFields($this->ids[self::PROGRAM], ['field_ol_product_plans' => [$plan]]);
        $this->saveFields($this->ids[self::BUNDLE], ['field_ol_product_plans' => [$plan]]);

        $hidden = $this->hidePlansDemoProducts($plan);

        return sprintf(
            'Training Plan %d is included in the program and the bundle.%s',
            $plan,
            $hidden === [] ? '' : ' Hidden from the catalogue: the Plans demo Product ' . implode(', ', $hidden) . '.'
        );
    }

    /**
     * `wp ol-plans seed` publishes its own uncategorised Product for the same
     * Plan. Next to the seeded catalogue it would show in the shop grid, the
     * footer and the catalogue count, so it is hidden from the catalogue. It
     * stays purchasable from its URL.
     *
     * @return list<int> The Products hidden by this run.
     */
    private function hidePlansDemoProducts(int $plan): array
    {
        $products = wc_get_products([
            'status'     => 'publish',
            'visibility' => 'catalog',
            'exclude'    => array_values($this->ids),
            'limit'      => -1,
        ]);

        $hidden = [];
        foreach (is_array($products) ? $products : [] as $product) {
            $plans = get_field('field_ol_product_plans', $product->get_id());

            if (
                !is_array($plans)
                || !in_array($plan, array_map('intval', $plans), true)
                || optimum_lift_product_kind($product) !== null
            ) {
                continue;
            }

            $product->set_catalog_visibility('hidden');
            $product->save();
            $hidden[] = $product->get_id();
        }

        return $hidden;
    }

    private function frontPage(): int
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

    private function coupon(): void
    {
        $coupon = new WC_Coupon(self::COUPON);
        $coupon->set_code(self::COUPON);
        $coupon->set_status('publish');
        $coupon->set_description('Demo: 10% off, offered by the homepage exit-intent modal.');
        $coupon->set_discount_type('percent');
        $coupon->set_amount(10);
        $coupon->set_individual_use(false);
        $coupon->set_usage_limit_per_user(1);
        $coupon->save();
    }

    private function configure(DateTimeImmutable $offerEnd): void
    {
        $options = [
            'woocommerce_coming_soon'          => 'no',
            'woocommerce_default_country'      => 'XK',
            'woocommerce_price_thousand_sep'   => '.',
            'woocommerce_price_decimal_sep'    => ',',
            'woocommerce_price_num_decimals'   => '2',
            'woocommerce_currency_pos'         => 'right_space',
            'woocommerce_enable_reviews'       => 'yes',
            'woocommerce_enable_review_rating' => 'yes',
        ];
        foreach ($options as $name => $value) {
            update_option($name, $value);
        }

        // The storefront is Albanian; wp-admin stays English for whoever builds it.
        foreach (get_users(['role' => 'administrator', 'fields' => 'ID']) as $userId) {
            update_user_meta((int) $userId, 'locale', 'en_US');
        }

        set_theme_mod('offer_ends_at', $offerEnd->format('Y-m-d\TH:i'));
        set_theme_mod('exit_coupon', self::COUPON);

        // ADR-0007: the classic cart and checkout, not the blocks WooCommerce installs.
        foreach (['cart' => 'woocommerce_cart', 'checkout' => 'woocommerce_checkout'] as $page => $shortcode) {
            if (get_post(wc_get_page_id($page)) === null) {
                WC_Install::create_pages();
            }

            wp_update_post([
                'ID'           => wc_get_page_id($page),
                'post_content' => sprintf('<!-- wp:shortcode -->[%s]<!-- /wp:shortcode -->', $shortcode),
            ]);
        }
    }

    /**
     * The terms, privacy and refund pages the footer and checkout link to,
     * published with placeholder text marked for replacement. The pages
     * WordPress and WooCommerce created as drafts are reused when they exist.
     */
    private function legalPages(): void
    {
        $days  = (int) optimum_lift_setting('guarantee_days');
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
                sprintf('Ke %d ditë nga blerja për të kërkuar kthimin e plotë të parave, pa pyetje.', $days),
                sprintf('Shkruaj në %s me numrin e porosisë. Paratë kthehen në të njëjtën mënyrë pagese.', $email),
            ]],
        ];

        foreach ($pages as $option => [$slug, $title, $paragraphs]) {
            $id = (int) get_option($option);
            if ($id <= 0 || get_post($id) === null) {
                $id = $this->postId('page', $slug);
            }

            array_unshift($paragraphs, '<strong>[Tekst shembull: zëvendësoje me tekstin ligjor të rishikuar para lansimit.]</strong>');
            $content = implode("\n\n", array_map(
                static fn (string $paragraph): string => '<!-- wp:paragraph --><p>' . $paragraph . '</p><!-- /wp:paragraph -->',
                $paragraphs
            ));

            $saved = wp_insert_post([
                'ID'           => $id,
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_content' => $content,
            ], true);
            if ($saved instanceof WP_Error) {
                throw new RuntimeException(sprintf('Could not save the page "%s": %s', $title, $saved->get_error_message()));
            }

            update_option($option, $saved);
        }

        // A terms page turns on WooCommerce's required terms checkbox. The
        // checkout asks for as little as possible (ADR-0007), so it stays off
        // until the launch consent work decides what buyers must accept.
        update_option('woocommerce_checkout_terms_and_conditions_checkbox_text', '');
    }

    /**
     * WordPress keeps WPLANG only for an installed language; docker/setup.sh
     * installs the Albanian packs.
     */
    private function siteLanguage(): string
    {
        if (!in_array('sq', get_available_languages(), true)) {
            return 'The site language stays English until the sq language pack is installed: wp language core install sq';
        }

        update_option('WPLANG', 'sq');

        return 'Site language: sq.';
    }

    /**
     * A section row. The common fields default to empty and the page background.
     *
     * @param array<string, mixed> $fields
     * @return Row
     */
    private function block(string $layout, array $fields): array
    {
        return ['acf_fc_layout' => $layout] + $fields + [
            'anchor'    => '',
            'nav_label' => '',
            'eyebrow'   => '',
            'heading'   => '',
            'intro'     => '',
            'tone'      => 'default',
        ];
    }

    /**
     * Repeater rows with one sub field.
     *
     * @return list<Row>
     */
    private function rows(string $name, string ...$values): array
    {
        return array_map(static fn (string $value): array => [$name => $value], $values);
    }

    /**
     * Repeater rows from positional values.
     *
     * @param list<string>      $names
     * @param list<list<mixed>> $rows
     * @return list<Row>
     */
    private function table(array $names, array $rows): array
    {
        return array_map(static fn (array $row): array => array_combine($names, $row), $rows);
    }

    /**
     * A textarea read as a list, one item per line.
     */
    private function lines(string ...$lines): string
    {
        return implode("\n", $lines);
    }

    private function link(string $slug, string $text): string
    {
        return sprintf(
            '<a href="%s">%s</a>',
            esc_url(wp_make_link_relative((string) get_permalink($this->ids[$slug]))),
            esc_html($text)
        );
    }

    /**
     * @return array<string, ProductData>
     */
    private function catalogue(): array
    {
        return [
            self::PROGRAM => [
                'title'       => 'Programi i Stërvitjes 12-Javor',
                'excerpt'     => '12 javë stërvitje të strukturuara, në shqip, me <strong>version për shtëpi dhe për palestër</strong>. Çdo javë e di saktësisht çfarë të bësh, me sa peshë dhe sa përsëritje — dhe e sheh progresin me shifra, jo me ndjesi.',
                'description' => 'Program stërvitjeje 12-javor në shqip, me version për shtëpi dhe për palestër, video për çdo ushtrim dhe tabelë progresioni. Akses i menjëhershëm, garanci 30 ditë.',
                'category'    => 'programe-stervitjeje',
                'goal'        => 'Humbje yndyre',
                'regular'     => '14.99',
                'sale'        => '7.99',
                'featured'    => true,
                'menu_order'  => 1,
                'age_days'    => 120,
                'sales'       => 97,
                'cross_sells' => [self::DIET, self::MED, self::BUNDLE],
                'fields'      => [
                    'field_olt_title_accent'   => '12-Javor',
                    'field_olt_card_blurb'     => '12 javë të strukturuara, me version për shtëpi dhe për palestër.',
                    'field_olt_points'         => $this->rows(
                        'text',
                        '36 stërvitje të gatshme, javë për javë',
                        'Video për çdo ushtrim, në shqip',
                        '45 minuta maksimum për seancë',
                        'Tabelë progresioni që e mban vetë'
                    ),
                    'field_olt_duration_weeks' => 12,
                    'field_olt_media_label'    => 'Produkt digjital · PDF + video',
                    'field_olt_level_label'    => 'Fillestar → i avancuar',
                    'field_olt_versions'       => $this->table(['label', 'options'], [
                        ['Nivelet', $this->lines('Fillestar', 'Mesatar', 'I avancuar')],
                        ['Ku stërvitesh', $this->lines('Shtëpi', 'Palestër')],
                    ]),
                    'field_olt_versions_note'  => 'Të tre nivelet dhe të dy versionet përfshihen në çmim — zgjedh cilin të ndjekësh.',
                    'field_olt_stats'          => $this->table(['value', 'label'], [
                        ['12', 'Javë program'],
                        ['36', 'Stërvitje'],
                        ['60+', 'Video ushtrimesh'],
                        ['2', 'Versione: shtëpi/palestër'],
                    ]),
                    'field_olt_bundle_hint'    => 'E do edhe ushqimin e zgjidhur?',
                ],
                'reviews'     => [
                    ['Demo Dritan K.', 5, 'Kisha frikë se nuk do ta ndiqja dot, por javët janë të qarta dhe seancat nuk zgjasin shumë. Jam në javën 7 dhe nuk kam humbur asnjë stërvitje.'],
                    ['Demo Besa M.', 5, 'Versioni i shtëpisë me dumbbell është i mjaftueshëm. Në javën e katërt e ndjeva qartë që isha më e fortë.'],
                    ['Demo Arben L.', 5, 'Më në fund e di sa peshë të vë çdo javë. Tabela e progresionit e bën të thjeshtë.'],
                    ['Demo Lirie G.', 4, 'Programi është i mirë dhe videot ndihmojnë shumë. Do të doja pak më shumë ushtrime alternative për gjunjët.'],
                    ['Demo Gentian H.', 5, "E fillova si fillestar i plotë. Java 1 është vërtet e lehtë për t'u ndjekur dhe teknika shpjegohet mirë."],
                    ['Demo Valbona S.', 5, 'Me punë dhe fëmijë, 45 minuta tri herë në javë është e mundshme. Kjo ishte arsyeja pse e bleva.'],
                    ['Demo Ilir B.', 5, 'Java e lehtësimit më pëlqeu shumë. Nuk ndihesha i rraskapitur si me programet e tjera.'],
                    ['Demo Mimoza R.', 5, 'Kalova nga versioni i palestrës te ai i shtëpisë gjatë pushimeve pa asnjë problem.'],
                    ['Demo Kushtrim A.', 4, "Rezultate të mira në forcë. PDF-ja do të ishte më e lehtë për t'u lexuar në telefon me shkronja pak më të mëdha."],
                    ['Demo Erjona P.', 5, 'Pagova një herë dhe e kam përgjithmonë. Po e nis nga e para për herë të dytë, me pesha më të rënda.'],
                    ['Demo Fisnik T.', 5, 'Squat-i nga 70 në 95 kg në 12 javë. Nuk e besoja që do ta arrija kaq shpejt.'],
                    ['Demo Rina V.', 5, 'E qartë, në shqip dhe pa teori të tepërt. E hap ditën dhe e bëj.'],
                ],
            ],
            self::FORCE => [
                'title'       => 'Forcë & Masë',
                'excerpt'     => 'Program 16-javor për muskul dhe forcë reale, jo vetëm humbje peshe. Split 4–5 ditë, <strong>mbingarkesë progresive e shkruar javë pas jave</strong> dhe video për çdo ushtrim, në shqip.',
                'description' => 'Program stërvitjeje 16-javor për masë muskulore dhe forcë, në shqip: split 4–5 ditë (push/pull/këmbë), tabelë e mbingarkesës progresive dhe video për çdo ushtrim. Akses i menjëhershëm, garanci 30 ditë.',
                'category'    => 'programe-stervitjeje',
                'goal'        => 'Masë & forcë',
                'regular'     => '15.99',
                'sale'        => '8.99',
                'featured'    => true,
                'menu_order'  => 2,
                'age_days'    => 100,
                'sales'       => 37,
                'cross_sells' => [self::DIET, self::MED, self::BUNDLE],
                'fields'      => [
                    'field_olt_title_accent'   => 'Masë',
                    'field_olt_card_blurb'     => 'Për ata që duan muskul e forcë reale, jo vetëm humbje peshe.',
                    'field_olt_points'         => $this->rows(
                        'text',
                        'Split 4–5 ditë (push/pull/këmbë)',
                        'Tabela e mbingarkesës progresive',
                        'Video demonstrim për çdo ushtrim',
                        "Udhëzues suplementesh (vetëm ç'duhet)"
                    ),
                    'field_olt_duration_weeks' => 16,
                    'field_olt_media_label'    => 'Produkt digjital · PDF + video',
                    'field_olt_level_label'    => 'Mesatar → i avancuar',
                    'field_olt_versions'       => $this->table(['label', 'options'], [
                        ['Nivelet', $this->lines('Mesatar', 'I avancuar')],
                        ['Ku stërvitesh', $this->lines('Palestër', 'Shtëpi me shtangë dhe stol')],
                    ]),
                    'field_olt_versions_note'  => 'Të gjitha versionet përfshihen në çmim.',
                    'field_olt_stats'          => $this->table(['value', 'label'], [
                        ['16', 'Javë program'],
                        ['4–5', 'Stërvitje në javë'],
                        ['60+', 'Video ushtrimesh'],
                        ['3', 'Faza progresioni'],
                    ]),
                    'field_olt_bundle_hint'    => 'Masa ndërtohet edhe në kuzhinë.',
                ],
                'reviews'     => [
                    ['Demo Edon Z.', 5, 'Isha ngecur prej një viti me të njëjtat pesha. Me këtë ndarje dhe tabelën e progresionit, shtyj 10 kg më shumë.'],
                    ['Demo Klodian M.', 5, 'Stërvitje serioze, pa gjëra të panevojshme. Ditët e këmbëve janë të vështira, siç duhet të jenë.'],
                    ['Demo Albulena K.', 4, 'Programi është shumë i mirë, por kërkon palestër të pajisur mirë. Në shtëpi me pak pesha nuk e ndiqja dot.'],
                    ['Demo Faton D.', 5, 'Fitova 4 kg në 16 javë dhe forca u rrit në çdo ushtrim. Videot e teknikës janë të shkurtra dhe të qarta.'],
                    ['Demo Dardan S.', 5, 'Më pëlqen që e di paraprakisht çfarë vjen javën tjetër. Asnjë seancë e improvizuar.'],
                    ['Demo Shqipe L.', 4, 'Rezultate të mira. Seancat e ditëve me volum zgjasin pak më shumë se 60 minuta.'],
                    ['Demo Blend R.', 5, 'Udhëzuesi i suplementeve më kurseu para: tani blej vetëm kreatinë dhe proteinë.'],
                    ['Demo Agon N.', 5, 'Kalova nga 4 në 5 ditë në javë pa problem. Progresioni është i menduar mirë.'],
                ],
            ],
            self::DIET => [
                'title'       => 'Plani Ushqimor 12-Javor',
                'excerpt'     => "Kalori dhe makro të llogaritura për ty — me gjoks pule, jogurt, peshk, oriz dhe perime që i gjen në çdo treg te ne. Pa ushqime ekzotike që s'i gjen, pa uri, pa filluar nga e para çdo të hënë.",
                'description' => 'Plan ushqimor 12-javor në shqip, me kalori e makro të llogaritura dhe ushqime që gjenden në çdo treg te ne. Lista e pazarit, 30 receta, akses i menjëhershëm, garanci 30 ditë.',
                'category'    => 'dieta',
                'goal'        => 'Humbje yndyre',
                'regular'     => '12.99',
                'sale'        => '6.99',
                'featured'    => true,
                'menu_order'  => 3,
                'age_days'    => 110,
                'sales'       => 74,
                'cross_sells' => [self::PROGRAM, self::FORCE, self::BUNDLE],
                'fields'      => [
                    'field_olt_title_accent'   => '12-Javor',
                    'field_olt_card_blurb'     => 'Kalori e makro të llogaritura, me ushqime që gjenden tek ne.',
                    'field_olt_points'         => $this->rows(
                        'text',
                        'Plan 12-javor',
                        'Lista e pazarit javë për javë',
                        '30 receta shqiptare',
                        'Tabela e zëvendësimeve për çdo ushqim'
                    ),
                    'field_olt_duration_weeks' => 12,
                    'field_olt_media_label'    => 'Produkt digjital · PDF + receta',
                    'field_olt_level_label'    => '',
                    'field_olt_versions'       => $this->table(['label', 'options'], [
                        ['Objektivat', $this->lines('Humbje yndyre', 'Mbajtje', 'Shtim mase')],
                        ['Preferencat', $this->lines('Pa kufizim', 'Vegjetariane', 'Pa laktozë')],
                    ]),
                    'field_olt_versions_note'  => 'I merr të tria versionet e objektivit dhe të tria preferencat — kalon nga njëri te tjetri kur ndryshon qëllimi.',
                    'field_olt_stats'          => $this->table(['value', 'label'], [
                        ['{customers}', 'Klientë'],
                        ['30', 'Receta shqiptare'],
                        ['12', 'Javë të planifikuara'],
                        ['{guarantee_days} ditë', 'Garanci kthimi'],
                    ]),
                    'field_olt_bundle_hint'    => 'Ushqimi pa stërvitje ecën përgjysmë.',
                ],
                'reviews'     => [
                    ['Demo Teuta B.', 5, 'Nuk kam ndier uri as në javët e deficitit. Pesë vakte bëjnë diferencën.'],
                    ['Demo Besnik H.', 5, 'Humba 7 kg në 12 javë duke ngrënë bukë dhe mish. Nuk e besoja.'],
                    ['Demo Arta Q.', 5, 'Lista e pazarit më kursen kohë çdo javë. Blej një herë dhe gatuaj të dielën.'],
                    ['Demo Leonora F.', 5, 'Ndoqa versionin pa laktozë dhe recetat funksionojnë njësoj mirë.'],
                    ['Demo Genc M.', 4, 'Plan shumë i mirë. Dy javët e para me peshore janë pak të lodhshme, por pastaj bëhet e lehtë.'],
                    ['Demo Vlora D.', 5, "Javët e mbajtjes janë pjesa më e vlefshme. Pesha nuk m'u kthye pas dietës."],
                    ['Demo Ardian K.', 5, 'Ushqime që i gjej në çdo market në Prishtinë. Asgjë ekzotike.'],
                    ['Demo Elira S.', 5, 'Recetat janë të shpejta dhe familja ha të njëjtin ushqim.'],
                    ['Demo Driton P.', 5, 'Më në fund e kuptoj sa proteinë duhet të ha dhe pse.'],
                    ['Demo Hana C.', 5, 'Tabela e zëvendësimeve është shumë praktike kur nuk gjej peshk të freskët.'],
                ],
            ],
            self::MED => [
                'title'       => 'Dieta Mesdhetare',
                'excerpt'     => '8 javë ushqim mesdhetar me vaj ulliri, peshk, perime dhe drithëra integrale — <strong>pa numëruar kalori</strong> dhe pa hequr grupe të tëra ushqimesh. Për energji, shëndet dhe një peshë që mbahet.',
                'description' => 'Plan ushqimor mesdhetar 8-javor në shqip, me 25 receta, listë pazari javë pas jave dhe ushqime që gjenden te ne. Pa numërim kalorish. Akses i menjëhershëm, garanci 30 ditë.',
                'category'    => 'dieta',
                'goal'        => 'Shëndet & mbajtje',
                'regular'     => '9.99',
                'sale'        => '5.99',
                'featured'    => false,
                'menu_order'  => 4,
                'age_days'    => 6,
                'sales'       => 13,
                'cross_sells' => [self::PROGRAM, self::FORCE, self::BUNDLE],
                'fields'      => [
                    'field_olt_title_accent'   => 'Mesdhetare',
                    'field_olt_card_blurb'     => '8 javë me vaj ulliri, peshk dhe perime — pa restriksion të skajshëm.',
                    'field_olt_points'         => $this->rows(
                        'text',
                        '25 receta mesdhetare',
                        'Lista e pazarit javë për javë',
                        'Pa numërim kalorish',
                        'Menu 8-javore, ditë për ditë'
                    ),
                    'field_olt_duration_weeks' => 8,
                    'field_olt_media_label'    => 'Produkt digjital · PDF + receta',
                    'field_olt_level_label'    => '',
                    'field_olt_versions'       => $this->table(['label', 'options'], [
                        ['Preferencat', $this->lines('Pa kufizim', 'Vegjetariane')],
                    ]),
                    'field_olt_versions_note'  => 'Të dy versionet përfshihen në çmim.',
                    'field_olt_stats'          => $this->table(['value', 'label'], [
                        ['8', 'Javë të planifikuara'],
                        ['25', 'Receta mesdhetare'],
                        ['0', 'Kalori për të numëruar'],
                        ['{guarantee_days} ditë', 'Garanci kthimi'],
                    ]),
                    'field_olt_bundle_hint'    => 'Ushqimi i mirë jep më shumë kur e shoqëron stërvitja.',
                ],
                'reviews'     => [
                    ['Demo Jehona R.', 5, 'Më në fund një dietë pa numëruar asgjë. Ha peshk dy herë në javë dhe ndihem me më shumë energji.'],
                    ['Demo Petrit V.', 5, 'Recetat janë të thjeshta dhe me gjëra që i kemi në shtëpi. Vaji i ullirit dhe perimet nuk mungojnë kurrë.'],
                    ['Demo Nora E.', 4, 'E pëlqej shumë. Do të doja edhe disa receta të tjera me mish të bardhë.'],
                ],
            ],
            self::BUNDLE => [
                'title'       => 'Transformimi Total',
                'excerpt'     => 'Akses te të gjitha programet e stërvitjes dhe të gjitha planet ushqimore, plus mbështetje. Sistemi i plotë, pa hapësirë për hamendësime.',
                'description' => 'Paketa e plotë e Optimum Lift: çdo program stërvitjeje dhe çdo plan ushqimor në një blerje, më lirë se të blera veç e veç. Pagesë e njëhershme, akses i menjëhershëm, garanci 30 ditë.',
                'category'    => 'paketa',
                'goal'        => 'Transformim i plotë',
                'regular'     => '14.99',
                'sale'        => '',
                'featured'    => false,
                'menu_order'  => 0,
                'age_days'    => 90,
                'sales'       => 156,
                'cross_sells' => [],
                'fields'      => [
                    'field_olt_title_accent'   => 'Total',
                    'field_olt_card_blurb'     => 'Çdo program dhe çdo dietë — më lirë se dy produkte veç e veç.',
                    'field_olt_points'         => $this->rows(
                        'text',
                        'Çdo program stërvitjeje aktual',
                        'Çdo plan ushqimor aktual',
                        '60 receta shqiptare (fit)',
                        'Grup privat + mbështetje WhatsApp',
                        'BONUS: Programet e reja falas',
                        'BONUS: Përditësime falas përgjithmonë'
                    ),
                    'field_olt_duration_weeks' => 12,
                    'field_olt_media_label'    => 'Produkt digjital · Paketa e plotë',
                    'field_olt_level_label'    => 'Çdo program + çdo dietë',
                    'field_olt_versions'       => [],
                    'field_olt_versions_note'  => '',
                    'field_olt_stats'          => $this->table(['value', 'label'], [
                        ['{saving}', 'Kursen me paketën'],
                        ['60', 'Receta shqiptare'],
                        ['{customers}', 'Klientë'],
                        ['{guarantee_days} ditë', 'Garanci kthimi'],
                    ]),
                    'field_olt_bundle_hint'    => '',
                ],
                'reviews'     => [
                    ['Demo Arlind G.', 5, 'Mora paketën për stërvitjen dhe ushqimin bashkë. Në 12 javë humba 9 kg dhe ruajta forcën.'],
                    ['Demo Donika H.', 5, "Del shumë më lirë se t'i blesh veç e veç, dhe kam gjithçka në një vend."],
                    ['Demo Qendrim B.', 5, 'Programi dhe plani ushqimor shkojnë bashkë: ditët e rënda kanë më shumë karbohidrate. E menduar mirë.'],
                    ['Demo Merita L.', 4, 'Shumë material, në fillim të duhet pak kohë ta organizosh. Pas javës së parë shkon vetë.'],
                    ['Demo Endrit S.', 5, 'Nisa me Forcë & Masë dhe tani po provoj edhe Dietën Mesdhetare. Ia vlen çdo cent.'],
                    ['Demo Adelina M.', 5, "Në WhatsApp m'u përgjigjën brenda ditës kur kisha pyetje për zëvendësimet."],
                    ['Demo Luan K.', 5, 'Isha skeptik për një produkt digjital, por është më i strukturuar se trajneri që kisha.'],
                    ['Demo Fjolla D.', 5, 'Recetat shqiptare të rillogaritura janë pjesa ime e preferuar. Byrek dhe prapë në deficit.'],
                    ['Demo Ermal T.', 4, 'Paketë e plotë dhe e dobishme. Do të ishte mirë një aplikacion, por PDF-ja funksionon.'],
                    ['Demo Blerta N.', 5, 'Burri ndjek versionin e palestrës, unë atë të shtëpisë, dhe hamë nga i njëjti plan.'],
                    ['Demo Visar J.', 5, 'Pagesë një herë dhe asnjë abonim. Kjo më bindi.'],
                    ['Demo Aurora I.', 5, 'Rezultatet erdhën ngadalë, por qëndrojnë. Pas pesë muajsh pesha është aty ku e lashë.'],
                ],
            ],
        ];
    }

    /**
     * @return list<Row>
     */
    private function blocks(string $slug): array
    {
        return match ($slug) {
            self::PROGRAM => $this->programBlocks(),
            self::FORCE   => $this->forceBlocks(),
            self::DIET    => $this->dietBlocks(),
            self::MED     => $this->mediterraneanBlocks(),
            self::BUNDLE  => $this->bundleBlocks(),
            default       => [],
        };
    }

    /**
     * produkt.html, sections 5–14.
     *
     * @return list<Row>
     */
    private function programBlocks(): array
    {
        return [
            $this->block('qualification', [
                'eyebrow'   => 'Kualifikimi',
                'heading'   => 'A është ky program për ty?',
                'yes_title' => 'Po, është për ty nëse',
                'yes_items' => $this->rows(
                    'text',
                    "Do të stërvitesh seriozisht por nuk di nga t'ia fillosh.",
                    'Ke provuar programe nga interneti dhe i ke lënë në javën e dytë.',
                    'Ke 3–4 orë në javë, jo më shumë.',
                    'Ushqimin e ke pak a shumë në rregull dhe të mungon vetëm stërvitja.',
                    'Do të dish çfarë të bësh sot, jo të lexosh teori.'
                ),
                'no_title'  => 'Jo, mos e blej nëse',
                'no_items'  => $this->rows(
                    'text',
                    'Kërkon rezultate pa ndryshuar asgjë në rutinën tënde.',
                    'Të duhet edhe plani ushqimor — atëherë merr Transformimin Total.',
                    'Ke lëndim aktiv ose kusht mjekësor pa miratim të mjekut.',
                    'Pret që programi të bëhet vetë — plani punon vetëm nëse punon ti.'
                ),
            ]),
            $this->block('phases', [
                'anchor'    => 'permban',
                'nav_label' => 'Çfarë përmban',
                'eyebrow'   => 'Përmbajtja',
                'heading'   => '12 javë, 3 faza, zero hamendësime',
                'intro'     => 'Çdo fazë ka një punë të vetën. Nuk kalon në tjetrën derisa trupi të jetë gati.',
                'tone'      => 'alt',
                'style'     => 'numbers',
                'cards'     => $this->table(['label', 'title', 'text', 'bullets', 'icon', 'featured'], [
                    [
                        'Java 1–4',
                        'Themeli & teknika',
                        'Lëvizjet bazë, teknika e saktë, mësimi i ritmit. Dalje nga seanca pa u shkatërruar.',
                        $this->lines('3 stërvitje në javë', 'Video teknike për çdo lëvizje', 'Testi fillestar i forcës'),
                        '',
                        false,
                    ],
                    [
                        'Java 5–8',
                        'Mbingarkesa progresive',
                        'Ku ndodh ndryshimi i vërtetë: çdo javë shton peshë ose përsëritje, me shifra.',
                        $this->lines('4 stërvitje në javë', 'Tabela e progresionit javë pas jave', 'Java e lehtësimit (deload)'),
                        '',
                        true,
                    ],
                    [
                        'Java 9–12',
                        'Intensiteti & forma',
                        'Volum më i lartë, pushime më të shkurtra, përfundimi me matje dhe plan vazhdimi.',
                        $this->lines('4–5 stërvitje në javë', 'Teste krahasuese me javën 1', 'Udhëzuesi "çfarë pas javës 12"'),
                        '',
                        false,
                    ],
                ]),
                'checklist' => '',
            ]),
            $this->block('preview', [
                'eyebrow'         => 'Shiko brenda',
                'heading'         => 'Kështu duket një ditë',
                'intro'           => 'Pa teori, pa kapituj të gjatë. E hap, e lexon ditën, e bën.',
                'workout_label'   => 'Java 5 · Dita 2',
                'workout_tag'     => 'Pull',
                'workout_rows'    => $this->table(['name', 'scheme'], [
                    ['Tërheqje në fole', '4 × 6'],
                    ['Kanotazh me shtangë', '4 × 8'],
                    ['Tërheqje kabllo', '3 × 12'],
                    ['Bicepsi me dumbbell', '3 × 12'],
                ]),
                'workout_note'    => 'Shto 2,5 kg krahasuar me javën e kaluar',
                'media_video_url' => '',
                'media_caption'   => 'Video · teknika e ushtrimit',
                'progress_title'  => 'Tabela e progresit',
                'progress_rows'   => $this->table(['label', 'value', 'percent'], [
                    ['Shtytje me shtangë', '60 → 75 kg', 72],
                    ['Squat', '80 → 102 kg', 84],
                    ['Tërheqje', '3 → 9 përsëritje', 66],
                ]),
                'progress_note'   => 'Shifrat i mbush vetë çdo javë. Në javën 12 e sheh me sy se sa ke ecur — jo me ndjesi.',
            ]),
            $this->block('value_stack', [
                'eyebrow'     => 'Çfarë merr',
                'heading'     => 'Gjithçka që vjen me programin',
                'tone'        => 'alt',
                'layout'      => 'centered',
                'product'     => null,
                'items'       => $this->table(['text', 'value', 'bonus'], [
                    ['Programi 12-javor (PDF, version shtëpi + palestër)', 7.99, false],
                    ['60+ video demonstrimi në shqip', 4.99, false],
                    ['Tabela e progresionit (Excel + e printueshme)', 2.49, false],
                    ['Udhëzuesi i ngrohjes dhe parandalimit të lëndimeve', 1.49, false],
                    ['Plani "orar i zënë" — seanca 25-minutëshe', 1.99, true],
                ]),
                'total_label' => 'Vlera totale',
                'pay_label'   => 'Ti paguan sot',
                'cta_label'   => 'Bli tani — {price}',
            ]),
            $this->block('results', [
                'anchor'     => 'rezultate',
                'nav_label'  => 'Rezultate',
                'eyebrow'    => 'Rezultate',
                'heading'    => 'Me këtë program',
                'items'      => $this->table(['name', 'meta', 'result', 'quote'], [
                    ['Ardit, 29', 'Tiranë · 12 javë · Palestër', '+18 kg', 'Shtytja nga 60 në 78 kg. Për herë të parë e dija saktë sa peshë të vija çdo javë.'],
                    ['Elona, 34', 'Prishtinë · 12 javë · Shtëpi', '−6 kg', 'Dy fëmijë dhe zero kohë. Versioni i shtëpisë me 30 minuta ishte i vetmi që zbatova dot.'],
                    ['Blerim, 24', 'Shkup · 12 javë · Palestër', '+5 kg', 'Isha vetëm 3 muaj në palestër dhe pa program. Tani e di pse dhe çfarë po bëj.'],
                ]),
                'disclaimer' => 'Rezultatet ndryshojnë nga personi në person dhe varen nga zbatimi i planit.',
                'cta_label'  => '',
                'cta_anchor' => '',
            ]),
            $this->block('reviews', [
                'anchor'       => 'deshmi',
                'eyebrow'      => 'Dëshmi',
                'heading'      => 'Çfarë thonë blerësit',
                'tone'         => 'alt',
                'source'       => 'both',
                'limit'        => 3,
                'items'        => $this->table(['quote', 'name', 'meta', 'rating'], [
                    ['Versioni i shtëpisë më shpëtoi dimrin. Zero pajisje, zero justifikime.', 'Fatjon D.', 'Elbasan', 5],
                    ['Videot në shqip bëjnë diferencën. E kuptoj teknikën pa u lodhur me anglisht.', 'Arta H.', 'Prishtinë', 5],
                    ['Tabela e progresionit është ajo që më mbajti. E shoh se po ecën, prandaj vazhdoj.', 'Marin K.', 'Durrës', 5],
                ]),
                'show_summary' => true,
            ]),
            $this->block('comparison', [
                'anchor'    => 'krahaso',
                'nav_label' => 'Krahaso',
                'eyebrow'   => 'Krahasimi',
                'heading'   => 'Cili produkt është për ty?',
                'intro'     => 'Krahasimi i shpejtë mes produkteve tona, që të mos blesh gabim.',
                'columns'   => $this->table(['label', 'product', 'highlight'], [
                    ['Ky program', $this->ids[self::PROGRAM], true],
                    ['Forcë & Masë', $this->ids[self::FORCE], false],
                    ['Transformimi Total', $this->ids[self::BUNDLE], false],
                ]),
                'rows'      => $this->table(['label', 'type', 'cells'], [
                    ['Çmimi', 'price', $this->rows('value', '', '', '')],
                    ['Më i mirë për', 'text', $this->rows('value', 'Formë e përgjithshme', 'Muskul & forcë', 'Gjithçka')],
                    ['Stërvitje në javë', 'text', $this->rows('value', '3–5', '4–5', '3–5')],
                    ['Plan ushqimor', 'text', $this->rows('value', '✕', '✕', '✓ I plotë')],
                    ['Receta shqiptare', 'text', $this->rows('value', '✕', '✕', '✓ 60')],
                    ['', 'cta', $this->rows('value', '', '', '')],
                ]),
                'note'      => '',
            ]),
            $this->block('credibility', [
                'eyebrow' => 'Kush e ndërtoi',
                'heading' => 'Nuk është program i shkarkuar nga interneti',
                'tone'    => 'alt',
                'name'    => '[Emri i trajnerit]',
                'role'    => 'Themelues · Optimum Lift',
                'body'    => '<p>Ky program është ndërtuar nga trajner i certifikuar me [X] vjet eksperiencë dhe mbi [X] klientë të ndjekur individualisht — dhe testuar në palestra dhe shtëpi këtu, jo në një studio jashtë.</p>',
                'badges'  => $this->table(['title', 'text', 'icon'], [
                    ['Certifikim ndërkombëtar', '[Emri i certifikimit]', ''],
                    ['Metodë e bazuar në shkencë', 'Pa çajra, pa detox, pa mrekulli', ''],
                ]),
                'chips'   => '',
            ]),
            $this->block('faq', [
                'anchor'    => 'faq',
                'nav_label' => 'Pyetje',
                'eyebrow'   => 'Pyetje të shpeshta',
                'heading'   => 'Ke ndonjë dyshim? Mirë.',
                'items'     => $this->table(['question', 'answer'], [
                    [
                        'A më duhet palestër?',
                        '<p>Jo. Programi vjen në dy versione: <strong>shtëpi</strong> (pa pajisje ose me një palë dumbbell) dhe <strong>palestër</strong>. I merr të dy me të njëjtin çmim dhe kalon nga njëri te tjetri kur të duash.</p>',
                    ],
                    [
                        'A përfshihet plani ushqimor?',
                        sprintf("<p>Jo — ky produkt është vetëm stërvitje. Nëse do edhe ushqimin, merr %s, që i ka të dyja dhe del më lirë sesa t'i blesh veç e veç.</p>", $this->link(self::BUNDLE, 'Transformimin Total')),
                    ],
                    [
                        'Jam fillestar plotësisht. Është shumë për mua?',
                        '<p>Jo. Java 1 fillon me lëvizje bazë dhe video të detajuara për teknikën. Ndiq nivelin <strong>Fillestar</strong> — të tre nivelet përfshihen në çmim — dhe programi përshtatet me ty, jo anasjelltas.</p>',
                    ],
                    ['Si e marr pasi paguaj?', $this->deliveryAnswer('PDF + videot')],
                    ['Po nëse nuk funksionon për mua?', $this->guaranteeAnswer()],
                ]),
                'note'      => '',
                'help_box'  => false,
            ]),
            $this->block('final_cta', [
                'heading'               => "12 javë kalojnë\nsido që të jetë.",
                'body'                  => 'Pyetja e vetme është nëse në fund të tyre do të kesh një trup më të fortë — apo edhe një herë tjetër "nga e hëna filloj".',
                'show_countdown'        => false,
                'product'               => null,
                'primary_label'         => 'Bli tani — {price}',
                'primary_action'        => 'buy_now',
                'primary_anchor'        => '',
                'secondary_add_to_cart' => false,
                'note'                  => 'Në vend të {regular_price} · Garanci {guarantee_days} ditë · Akses i menjëhershëm',
            ]),
        ];
    }

    /**
     * produkt-dieta.html, sections 5–15.
     *
     * @return list<Row>
     */
    private function dietBlocks(): array
    {
        return [
            $this->block('qualification', [
                'eyebrow'   => 'Kualifikimi',
                'heading'   => 'A është ky plan për ty?',
                'intro'     => 'Të themi troç, që të mos humbasësh para kot.',
                'yes_title' => 'Po, është për ty nëse…',
                'yes_items' => $this->rows(
                    'text',
                    'Ke provuar dieta që të lanë me uri dhe i ke lënë brenda dy javësh.',
                    'Nuk di sa duhet të hash — dhe je lodhur duke gjetur numra kontradiktorë në internet.',
                    "Do ushqime normale: mish, bukë, oriz, djathë — jo pluhura dhe ushqime që s'i gjen te ne.",
                    'Gatuan vetë ose ke dikë që gatuan në shtëpi.',
                    'Do një plan të shkruar, jo "hani shëndetshëm".'
                ),
                'no_title'  => 'Jo, mos e blej nëse…',
                'no_items'  => $this->rows(
                    'text',
                    'Kërkon të humbasësh 10 kg në dy javë. Ky plan punon me 0,5–1 kg në javë — sepse kaq është e qëndrueshme.',
                    'Ke një gjendje shëndetësore që kërkon dietë të përshkruar nga mjeku. Fol së pari me të.',
                    'Nuk je i gatshëm të gatuash ose të përgatitesh fare — plani kërkon 20–30 minuta në ditë.',
                    'Pret që plani të bëjë punën vetë. Ai të tregon çfarë të hash; ngrënia mbetet e jotja.'
                ),
            ]),
            $this->block('phases', [
                'anchor'    => 'permban',
                'nav_label' => 'Çfarë përmban',
                'eyebrow'   => 'Çfarë përmban',
                'heading'   => '12 javë, të shkruara ditë për ditë',
                'intro'     => 'Jo një listë ushqimesh "të lejuara". Një plan me sasi, orare dhe zëvendësime.',
                'tone'      => 'alt',
                'style'     => 'icons',
                'cards'     => $this->table(['label', 'title', 'text', 'bullets', 'icon', 'featured'], [
                    [
                        'Javët 1–4',
                        'Rregullimi',
                        "Vendos oraret, sasitë dhe proteinën në çdo vakt. Pa prerje të egra — trupi mëson ritmin e ri para se t'i ulim kaloritë.",
                        '',
                        'calendar',
                        false,
                    ],
                    [
                        'Javët 5–9',
                        'Deficiti',
                        'Kaloritë ulen me hapa të vegjël, proteina mbetet lart që të mos humbasësh muskul. Këtu bie pesha — pa u ndier i uritur gjithë ditën.',
                        '',
                        'trend-up',
                        true,
                    ],
                    [
                        'Javët 10–12',
                        'Mbajtja',
                        "Pjesa që mungon te çdo dietë tjetër: si t'i kthesh kaloritë lart pa i marrë kilogramët prapa. Kjo javë vendos nëse rezultati mbetet.",
                        '',
                        'shield',
                        false,
                    ],
                ]),
                'checklist' => $this->lines(
                    'Kalori dhe makro të llogaritura sipas peshës, gjatësisë dhe aktivitetit',
                    '30 receta shqiptare me foto dhe kohë gatimi',
                    'Lista e pazarit e gatshme, javë për javë',
                    "Tabela e zëvendësimeve: s'ke peshk? ja çfarë hahet në vend",
                    'Udhëzues për dasma, ditëlindje dhe darka jashtë',
                    'Fletë ndjekjeje për peshën dhe matjet javore'
                ),
            ]),
            $this->block('sample_day', [
                'anchor'    => 'brenda',
                'nav_label' => 'Një ditë ushqimi',
                'eyebrow'   => 'Shiko brenda',
                'heading'   => 'Kështu duket një ditë e vërtetë',
                'intro'     => 'Ditë e marrë nga java 6, objektivi "humbje yndyre", burrë 82 kg. Jo shembull i zbukuruar — një ditë e plotë, si e ke në PDF.',
                'day_label' => 'Java 6 · E martë',
                'kcal'      => '2.200 kcal',
                'protein'   => '143 g',
                'carbs'     => '181 g',
                'fat'       => '94 g',
                'meals'     => $this->table(['time', 'name', 'description', 'kcal', 'macros'], [
                    ['07:30', 'Mëngjes', 'Omletë me 3 vezë · 2 feta bukë integrale · domate dhe trangull', '480', '28 / 38 / 22'],
                    ['10:30', 'Ndërmjet', 'Jogurt grek 200 g · një lugë mjaltë · 15 g arra', '290', '20 / 24 / 12'],
                    ['13:30', 'Dreka', 'Gjoks pule 150 g · oriz 60 g (i pazier) · sallatë sezonale me vaj ulliri', '620', '48 / 55 / 20'],
                    ['17:00', 'Ndërmjet', 'Një mollë · 30 g bajame', '250', '7 / 22 / 16'],
                    ['20:00', 'Darka', 'Skumbri ose levrek 180 g · patate të pjekura 200 g · perime të ziera', '560', '40 / 42 / 24'],
                ]),
                'note'      => '<strong>Pesë vakte, jo dy.</strong> Nuk rri i uritur — rri në deficit. Është ndryshim i madh, dhe është arsyeja pse njerëzit e mbajnë këtë plan më gjatë se dietat që kanë provuar më parë.',
                'facts'     => $this->table(['value', 'text'], [
                    ['5', 'vakte në ditë — që të mos vijë uria e madhe në darkë'],
                    ['20–30 min', 'gatim në ditë, me gjërat që ke tashmë në kuzhinë'],
                    ['0', 'suplemente të detyrueshme — plani punon me ushqim'],
                ]),
            ]),
            $this->block('shopping_list', [
                'anchor'      => 'pazari',
                'eyebrow'     => 'Lista e pazarit',
                'heading'     => '"Po a nuk kushton shtrenjtë të hash kështu?"',
                'tone'        => 'alt',
                'body'        => '<p>Jo. Këto janë ushqimet e një jave të plotë për një person — gjithçka gjendet në treg ose supermarket te ne. Pa avokado të importuar, pa proteina pluhur, pa "superfood" me çmim dyfish.</p><p>Shumica e njerëzve shpenzojnë <strong>afërsisht njësoj</strong> sa shpenzonin më parë. Ndryshon çfarë blejnë, jo sa.</p>',
                'cost_label'  => 'Kostoja mesatare',
                'cost_value'  => '≈ 40 €',
                'cost_suffix' => 'në javë · një person',
                'cost_note'   => 'Çmime orientuese sipas tregut — ndryshojnë sipas qytetit dhe sezonit.',
                'list_label'  => 'Java 6 · një person',
                'items'       => $this->lines(
                    'Gjoks pule 2 kg',
                    'Vezë 30 kokë',
                    'Jogurt grek 2 kg',
                    'Peshk (skumbri/levrek) 1,5 kg',
                    'Oriz 1 kg',
                    'Patate 2 kg',
                    'Bukë integrale 2 copë',
                    'Perime sezonale 3 kg',
                    'Fruta sezonale 2 kg',
                    'Vaj ulliri 1 l',
                    'Bajame / arra 500 g',
                    'Djathë i bardhë 500 g'
                ),
                'list_note'   => 'Çdo javë e planit ka listën e vet, të ndarë sipas radhëve të supermarketit — që ta bësh pazarin një herë dhe të mos mendosh më.',
            ]),
            $this->block('results', [
                'anchor'     => 'rezultate',
                'eyebrow'    => 'Rezultate',
                'heading'    => '12 javë, ushqim normal',
                'intro'      => 'Këto janë reale, jo mesatare të garantuara.',
                'items'      => $this->table(['name', 'meta', 'result', 'quote'], [
                    ['Arta, 34', '', '−9 kg', "E para dietë që s'më la me uri. Fëmijët hanë të njëjtin ushqim, thjesht unë e mas."],
                    ['Endrit, 28', '', '−12 kg', "Pazari një herë në javë, gatim të dielën. Pjesën tjetër s'e mendoj fare."],
                    ['Blerina, 41', '', '−7 kg', "Javët 10–12 më mësuan si t'i mbaj. Kjo më mungonte te çdo dietë tjetër."],
                ]),
                'disclaimer' => 'Rezultatet ndryshojnë nga personi në person.',
                'cta_label'  => '',
                'cta_anchor' => '',
            ]),
            $this->block('reviews', [
                'anchor'       => 'deshmi',
                'eyebrow'      => 'Dëshmi',
                'heading'      => 'Çfarë thonë ata që e ndoqën',
                'tone'         => 'alt',
                'source'       => 'both',
                'limit'        => 3,
                'items'        => $this->table(['quote', 'name', 'meta', 'rating'], [
                    ['Kisha provuar keto-n, agjërimin, gjithçka. Kjo ishte e para ku e kuptova pse po haja atë që haja.', 'Xhulia', 'Tiranë', 5],
                    ['Lista e pazarit ma ndryshoi punën. Nuk rri më para frigoriferit duke menduar çfarë të gatuaj.', 'Granit', 'Prishtinë', 5],
                    ['Recetat janë me gjëra që gjen te ne. Kjo duket gjë e vogël derisa provon një dietë amerikane.', 'Alma', 'Durrës', 5],
                ]),
                'show_summary' => false,
            ]),
            $this->block('comparison', [
                'anchor'    => 'krahaso',
                'nav_label' => 'Krahaso',
                'eyebrow'   => 'Krahaso',
                'heading'   => 'Tri rrugë për të njëjtin qëllim',
                'columns'   => $this->table(['label', 'product', 'highlight'], [
                    ['Dieta falas nga interneti', null, false],
                    ['Plani Ushqimor', $this->ids[self::DIET], true],
                    ['Nutricionist privat', null, false],
                ]),
                'rows'      => $this->table(['label', 'type', 'cells'], [
                    ['Kosto', 'price', $this->rows('value', '0 €', '', '50–80 € për vizitë')],
                    ['Llogaritur për ty', 'text', $this->rows('value', '✕ Jo', '✓ Po', '✓ Po')],
                    ['Ushqime që gjenden te ne', 'text', $this->rows('value', '✕ Rrallë', '✓ Gjithmonë', '✓ Po')],
                    ['Lista e pazarit', 'text', $this->rows('value', '✕ Jo', '✓ Për 12 javë', 'Ndonjëherë')],
                    ['Faza e mbajtjes', 'text', $this->rows('value', '✕ Jo', '✓ Javët 10–12', 'Me pagesë shtesë')],
                    ['E ke përgjithmonë', 'text', $this->rows('value', '—', '✓ Po', '✕ Jo')],
                ]),
                'note'      => 'Një nutricionist i mirë është investim i shkëlqyer — sidomos nëse ke një gjendje shëndetësore. Ky plan është për atë që do një rrugë të shkruar e të përballueshme, jo për të zëvendësuar mjekun.',
            ]),
            $this->block('credibility', [
                'eyebrow' => 'Kush e shkroi',
                'heading' => 'Plani nuk doli nga një gjenerator',
                'tone'    => 'alt',
                'name'    => '',
                'role'    => '',
                'body'    => '<p>U shkrua nga ekipi i Optimum Lift bashkë me një nutricionist të licencuar, mbi bazën e planeve që kemi ndjekur me klientë realë këtu — jo të përkthyera nga faqe të huaja.</p><p>Ushqimet, sasitë dhe recetat janë zgjedhur për tregun tonë: çfarë gjendet, çfarë kushton sa duhet dhe çfarë hahet vërtet në shtëpitë tona.</p>',
                'badges'  => [],
                'chips'   => $this->lines('Nutricionist i licencuar', 'Ushqime lokale', 'Shkruar në shqip'),
            ]),
            $this->block('guarantee', [
                'heading'    => '30 ditë. Pa pyetje.',
                'style'      => 'card',
                'body'       => "Ndiqe planin një muaj. Nëse nuk të përshtatet — nëse s'të pëlqejnë recetat, nëse e sheh që nuk është për ty — na shkruaj dhe të kthejmë çdo lek. Planin e mban. Rreziku është i yni, jo i yti.",
                'chips'      => '',
                'cta_label'  => 'Provoje pa rrezik',
                'cta_anchor' => 'blej',
            ]),
            $this->block('faq', [
                'anchor'    => 'faq',
                'nav_label' => 'Pyetje',
                'eyebrow'   => 'Pyetje të shpeshta',
                'heading'   => 'Ke ndonjë dyshim? Mirë.',
                'tone'      => 'alt',
                'items'     => $this->table(['question', 'answer'], [
                    [
                        'A do të kem uri gjatë ditës?',
                        '<p>Jo si te dietat me dy vakte. Plani ka <strong>pesë vakte</strong>, me proteinë dhe fibra në secilin — pikërisht ushqimet që të mbajnë të ngopur. Uri e lehtë para vaktit është normale në deficit; uri e vazhdueshme nuk është, dhe nëse e ndien, plani ka udhëzim si ta rregullosh.</p>',
                    ],
                    [
                        'A gjenden këto ushqime te ne?',
                        '<p>Po — ky ishte kushti kryesor kur e shkruam. Pulë, vezë, jogurt, peshk, oriz, patate, perime dhe fruta sezonale, djathë i bardhë, vaj ulliri. Asnjë përbërës që duhet porositur nga jashtë. Ka edhe një <strong>tabelë zëvendësimesh</strong> nëse diçka nuk gjendet në qytetin tënd.</p>',
                    ],
                    [
                        'A duhet të peshoj çdo gjë me peshore?',
                        '<p>Dy javët e para po, sepse të mësojnë sa është vërtet një porcion. Pastaj plani të kalon te matja <strong>me dorë dhe me sy</strong> — një pëllëmbë proteinë, një grusht karbohidrate. Askush nuk peshon ushqim gjithë jetën, dhe plani nuk e kërkon këtë.</p>',
                    ],
                    [
                        'Jam vegjetarian/e ose nuk pi qumësht. Ka version?',
                        '<p>Po. Plani përfshin versionin <strong>Vegjetariane</strong> dhe atë <strong>Pa laktozë</strong>, me burime të tjera proteine dhe receta të përshtatura. I merr të gjitha me të njëjtin çmim.</p>',
                    ],
                    [
                        'Sa kg humb në javë?',
                        '<p>Plani është ndërtuar për <strong>0,5–1 kg në javë</strong>. Më shumë se kaq zakonisht do të thotë humbje uji dhe muskuli, dhe kthehet po aq shpejt. Kush të premton 5 kg në javë, po të shet diçka që nuk qëndron. Shifra e saktë varet nga pesha, mosha dhe sa lëviz.</p>',
                    ],
                    ['A është abonim?', $this->subscriptionAnswer()],
                    ['Si e marr pasi paguaj?', $this->dietDeliveryAnswer()],
                ]),
                'note'      => $this->medicalNote(),
                'help_box'  => false,
            ]),
            $this->block('final_cta', [
                'heading'               => "E hëna tjetër\n*ose sot*",
                'body'                  => 'Dallimi mes atyre që humbin peshë dhe atyre që nisin përsëri çdo javë nuk është vullneti. Është të pasurit e një plani të shkruar.',
                'show_countdown'        => false,
                'product'               => null,
                'primary_label'         => 'Merr planin — {price}',
                'primary_action'        => 'buy_now',
                'primary_anchor'        => '',
                'secondary_add_to_cart' => true,
                'note'                  => $this->oneTimeNote(),
            ]),
        ];
    }

    /**
     * @return list<Row>
     */
    private function forceBlocks(): array
    {
        return [
            $this->block('qualification', [
                'eyebrow'   => 'Kualifikimi',
                'heading'   => 'A është ky program për ty?',
                'yes_title' => 'Po, është për ty nëse',
                'yes_items' => $this->rows(
                    'text',
                    'Stërvitesh prej disa muajsh dhe progresi të ka ngecur.',
                    'Do muskul të vërtetë dhe forcë që matet në kilogramë, jo vetëm pompim.',
                    'Ke akses në palestër, ose në shtangë, disqe dhe stol në shtëpi.',
                    'Ke 4–5 ditë në javë për stërvitje, rreth 60 minuta secila.',
                    'Do të dish saktë sa peshë të vendosësh në çdo seri.'
                ),
                'no_title'  => 'Jo, mos e blej nëse',
                'no_items'  => $this->rows(
                    'text',
                    'Je fillestar plotësisht — fillo me Programin e Stërvitjes 12-Javor.',
                    'Qëllimi yt kryesor është humbja e peshës, jo masa.',
                    'Ke lëndim aktiv ose kusht mjekësor pa miratim të mjekut.',
                    'Nuk je gati të hash mjaftueshëm — muskuli nuk ndërtohet në deficit.'
                ),
            ]),
            $this->block('phases', [
                'anchor'    => 'permban',
                'nav_label' => 'Çfarë përmban',
                'eyebrow'   => 'Përmbajtja',
                'heading'   => "16 javë, 3 faza,\n*një qëllim: më i fortë*",
                'intro'     => 'Çdo fazë ndërtohet mbi të mëparshmen. Peshat rriten vetëm kur teknika e lejon.',
                'tone'      => 'alt',
                'style'     => 'numbers',
                'cards'     => $this->table(['label', 'title', 'text', 'bullets', 'icon', 'featured'], [
                    [
                        'Java 1–5',
                        'Baza e forcës',
                        'Lëvizjet kryesore me volum të kontrolluar dhe testi i forcës që përcakton peshat e tua.',
                        $this->lines('4 stërvitje në javë', 'Testi fillestar i forcës', 'Video teknike për çdo lëvizje'),
                        '',
                        false,
                    ],
                    [
                        'Java 6–11',
                        'Hipertrofia',
                        'Volumi rritet javë pas jave: më shumë seri, më shumë muskul, me pushime të matura.',
                        $this->lines('5 stërvitje në javë', 'Tabela e mbingarkesës progresive', 'Java e lehtësimit (deload)'),
                        '',
                        true,
                    ],
                    [
                        'Java 12–16',
                        'Forca maksimale',
                        'Intensitet i lartë, më pak përsëritje dhe testi përfundimtar krahasuar me javën 1.',
                        $this->lines('4 stërvitje në javë', 'Teste krahasuese me javën 1', 'Udhëzuesi "çfarë pas javës 16"'),
                        '',
                        false,
                    ],
                ]),
                'checklist' => '',
            ]),
            $this->block('reviews', [
                'anchor'       => 'deshmi',
                'eyebrow'      => 'Dëshmi',
                'heading'      => 'Çfarë thonë blerësit',
                'source'       => 'native',
                'limit'        => 3,
                'items'        => [],
                'show_summary' => true,
            ]),
            $this->block('faq', [
                'anchor'    => 'faq',
                'nav_label' => 'Pyetje',
                'eyebrow'   => 'Pyetje të shpeshta',
                'heading'   => 'Ke ndonjë dyshim? Mirë.',
                'tone'      => 'alt',
                'items'     => $this->table(['question', 'answer'], [
                    [
                        'A mund ta ndjek në shtëpi?',
                        sprintf('<p>Po, nëse ke <strong>shtangë, disqe dhe një stol</strong>. Programi ndërtohet rreth ushtrimeve të mëdha me peshë të lirë, dhe me vetëm një palë dumbbell progresi ngec shpejt. Pa pajisje, fillo me %s.</p>', $this->link(self::PROGRAM, 'Programin e Stërvitjes 12-Javor')),
                    ],
                    [
                        'A përfshihet plani ushqimor?',
                        sprintf("<p>Jo — ky produkt është stërvitje, me një udhëzues të shkurtër suplementesh. Masa ndërtohet edhe në kuzhinë: nëse do edhe planin ushqimor, merr %s, që i ka të gjitha dhe del më lirë sesa t'i blesh veç e veç.</p>", $this->link(self::BUNDLE, 'Transformimin Total')),
                    ],
                    [
                        'Sa zgjat një seancë?',
                        '<p>Rreth <strong>60 minuta</strong> bashkë me ngrohjen. Ditët me volum më të lartë, në javët 6–11, mund të shkojnë deri në 70 minuta.</p>',
                    ],
                    ['Si e marr pasi paguaj?', $this->deliveryAnswer('PDF + videot')],
                    ['Po nëse nuk funksionon për mua?', $this->guaranteeAnswer()],
                ]),
                'note'      => '',
                'help_box'  => false,
            ]),
            $this->block('final_cta', [
                'heading'               => "Muskuli nuk ndërtohet\n*me hamendësime.*",
                'body'                  => '16 javë me peshat e shkruara për çdo seancë — ose edhe një vit tjetër me të njëjtat seri dhe të njëjtat pesha.',
                'show_countdown'        => false,
                'product'               => null,
                'primary_label'         => 'Bli tani — {price}',
                'primary_action'        => 'buy_now',
                'primary_anchor'        => '',
                'secondary_add_to_cart' => true,
                'note'                  => $this->oneTimeNote(),
            ]),
        ];
    }

    /**
     * @return list<Row>
     */
    private function mediterraneanBlocks(): array
    {
        return [
            $this->block('qualification', [
                'eyebrow'   => 'Kualifikimi',
                'heading'   => 'A është kjo dietë për ty?',
                'intro'     => 'Të themi troç, që të mos humbasësh para kot.',
                'yes_title' => 'Po, është për ty nëse…',
                'yes_items' => $this->rows(
                    'text',
                    'Do të hash më shëndetshëm pa numëruar kalori dhe pa peshuar çdo gjë.',
                    'Të pëlqen peshku, perimet, vaji i ullirit dhe gatimi i thjeshtë.',
                    'Do më shumë energji gjatë ditës dhe më pak ushqime të përpunuara.',
                    'Kërkon një mënyrë të ushqyeri që e mban për vite, jo një dietë dy-javore.'
                ),
                'no_title'  => 'Jo, mos e blej nëse…',
                'no_items'  => $this->rows(
                    'text',
                    'Kërkon të humbasësh peshë shpejt. Për këtë është Plani Ushqimor 12-Javor, me deficit të llogaritur.',
                    'Ke një gjendje shëndetësore që kërkon dietë të përshkruar nga mjeku. Fol së pari me të.',
                    'Nuk ha peshk dhe nuk do ta provosh — shumë receta e kanë në qendër.'
                ),
            ]),
            $this->block('phases', [
                'anchor'    => 'permban',
                'nav_label' => 'Çfarë përmban',
                'eyebrow'   => 'Çfarë përmban',
                'heading'   => '8 javë, një zakon që mbetet',
                'intro'     => 'Pa ndalime të forta dhe pa numra. Çdo fazë shton një zakon të ri në kuzhinën tënde.',
                'tone'      => 'alt',
                'style'     => 'icons',
                'cards'     => $this->table(['label', 'title', 'text', 'bullets', 'icon', 'featured'], [
                    [
                        'Javët 1–2',
                        'Kalimi',
                        'Vaji i ullirit zë vendin e yndyrave të tjera, perimet hyjnë në çdo vakt dhe buka e bardhë zëvendësohet me integrale.',
                        '',
                        'calendar',
                        false,
                    ],
                    [
                        'Javët 3–6',
                        'Ritmi',
                        'Peshk dy herë në javë, legume dhe drithëra integrale. Menuja ndjek sezonin dhe tregun, jo një listë ushqimesh të huaja.',
                        '',
                        'trend-up',
                        true,
                    ],
                    [
                        'Javët 7–8',
                        'Zakoni',
                        'Ndërton menunë tënde me parimet e planit, që ta vazhdosh edhe pasi mbarojnë tetë javët.',
                        '',
                        'shield',
                        false,
                    ],
                ]),
                'checklist' => $this->lines(
                    '25 receta mesdhetare me ushqime që gjenden te ne',
                    'Menu ditë për ditë për 8 javë',
                    'Lista e pazarit e gatshme, javë për javë',
                    'Version vegjetarian për çdo recetë me mish ose peshk',
                    'Udhëzues për darka jashtë dhe festa',
                    'Pa numërim kalorish dhe pa peshore'
                ),
            ]),
            $this->block('rich_text', [
                'eyebrow' => 'Pse mesdhetare',
                'heading' => 'Jo dietë e modës, *por mënyrë jetese*',
                'body'    => '<p>Dieta mesdhetare është mënyra si ushqehen vendet rreth Mesdheut: shumë perime, fruta, legume dhe drithëra integrale, vaj ulliri si yndyra kryesore, peshk disa herë në javë dhe më pak mish të kuq e ushqime të përpunuara.</p><p>Nuk ka ushqime të ndaluara dhe nuk ka numra për të ndjekur. Plani të tregon çfarë të blesh, çfarë të gatuash dhe si ta ndash pjatën — me ushqimet që gjenden në tregjet tona.</p>',
            ]),
            $this->block('reviews', [
                'anchor'       => 'deshmi',
                'eyebrow'      => 'Dëshmi',
                'heading'      => 'Çfarë thonë ata që e ndoqën',
                'tone'         => 'alt',
                'source'       => 'native',
                'limit'        => 3,
                'items'        => [],
                'show_summary' => true,
            ]),
            $this->block('faq', [
                'anchor'    => 'faq',
                'nav_label' => 'Pyetje',
                'eyebrow'   => 'Pyetje të shpeshta',
                'heading'   => 'Ke ndonjë dyshim? Mirë.',
                'items'     => $this->table(['question', 'answer'], [
                    [
                        'A duhet të numëroj kalori?',
                        sprintf('<p>Jo. Plani ndërtohet mbi <strong>përbërjen e pjatës</strong>: gjysma perime, një e katërta proteinë, një e katërta drithëra integrale, me vaj ulliri. Nëse qëllimi yt kryesor është të humbasësh peshë shpejt, %s ka kalori dhe makro të llogaritura.</p>', $this->link(self::DIET, 'Plani Ushqimor 12-Javor')),
                    ],
                    [
                        'A gjenden ushqimet te ne? A nuk është peshku i shtrenjtë?',
                        '<p>Gjenden. Recetat përdorin peshk të zakonshëm si skumbri, sardele dhe levrek, legume, perime sezonale dhe vaj ulliri. Peshku i konservuar në ujë ose në vaj ulliri funksionon njësoj mirë dhe kushton më pak.</p>',
                    ],
                    [
                        'A ka version vegjetarian?',
                        '<p>Po. Versioni <strong>Vegjetariane</strong> përfshihet në çmim: çdo recetë me mish ose peshk ka alternativën e saj me legume, vezë ose djathë.</p>',
                    ],
                    ['A është abonim?', $this->subscriptionAnswer()],
                    ['Si e marr pasi paguaj?', $this->dietDeliveryAnswer()],
                ]),
                'note'      => $this->medicalNote(),
                'help_box'  => false,
            ]),
            $this->block('final_cta', [
                'heading'               => "Ushqim i mirë,\n*pa numëruar asgjë.*",
                'body'                  => 'Tetë javë mjaftojnë që ushqimi mesdhetar të mos jetë më dietë, por mënyra si ha çdo ditë.',
                'show_countdown'        => false,
                'product'               => null,
                'primary_label'         => 'Merr dietën — {price}',
                'primary_action'        => 'buy_now',
                'primary_anchor'        => '',
                'secondary_add_to_cart' => true,
                'note'                  => $this->oneTimeNote(),
            ]),
        ];
    }

    /**
     * @return list<Row>
     */
    private function bundleBlocks(): array
    {
        return [
            $this->block('qualification', [
                'eyebrow'   => 'Kualifikimi',
                'heading'   => 'A është paketa për ty?',
                'yes_title' => 'Po, është për ty nëse',
                'yes_items' => $this->rows(
                    'text',
                    'Do të ndryshosh edhe stërvitjen, edhe ushqimin — jo vetëm njërën.',
                    'Ke provuar veç e veç dhe rezultati ose nuk erdhi, ose nuk mbeti.',
                    'Do një program dhe një plan ushqimor që punojnë bashkë, javë pas jave.',
                    'Do të paguash një herë dhe të kesh edhe programet që vijnë më vonë.'
                ),
                'no_title'  => 'Jo, mos e blej nëse',
                'no_items'  => $this->rows(
                    'text',
                    'Të mungon vetëm njëra pjesë — atëherë merr vetëm programin ose vetëm planin ushqimor.',
                    'Ke lëndim aktiv ose një gjendje shëndetësore pa miratim të mjekut.',
                    'Nuk ke 3–4 orë në javë për stërvitje dhe 20–30 minuta në ditë për gatim.'
                ),
            ]),
            $this->block('phases', [
                'anchor'    => 'permban',
                'nav_label' => 'Çfarë përmban',
                'eyebrow'   => 'Çfarë përmban',
                'heading'   => "Stërvitje, ushqim dhe mbështetje —\n*në një vend*",
                'intro'     => 'Çdo program dhe çdo plan ushqimor që kemi sot, plus çdo program që shtojmë më vonë.',
                'tone'      => 'alt',
                'style'     => 'icons',
                'cards'     => $this->table(['label', 'title', 'text', 'bullets', 'icon', 'featured'], [
                    [
                        'Stërvitja',
                        'Çdo program',
                        'Programe për formë të përgjithshme dhe për muskul e forcë, me version për shtëpi dhe për palestër.',
                        $this->lines('60+ video në shqip', 'Tabela e progresionit', 'Programet e reja falas'),
                        'barbell',
                        false,
                    ],
                    [
                        'Ushqimi',
                        'Çdo plan ushqimor',
                        'Plane me kalori të llogaritura dhe plane pa numërim, me receta shqiptare dhe listë pazari.',
                        $this->lines('60 receta shqiptare (fit)', 'Lista e pazarit javë për javë', 'Tabela e zëvendësimeve'),
                        'bowl',
                        true,
                    ],
                    [
                        'Mbështetja',
                        'Nuk je vetëm',
                        'Grup privat dhe përgjigje në WhatsApp kur ngec, nga ekipi që i shkroi planet.',
                        $this->lines('Përgjigje brenda 24 orësh', 'Përditësime falas përgjithmonë'),
                        'chat',
                        false,
                    ],
                ]),
                'checklist' => '',
            ]),
            $this->block('reviews', [
                'anchor'       => 'deshmi',
                'eyebrow'      => 'Dëshmi',
                'heading'      => 'Çfarë thonë ata që morën paketën',
                'source'       => 'native',
                'limit'        => 3,
                'items'        => [],
                'show_summary' => true,
            ]),
            $this->block('faq', [
                'anchor'    => 'faq',
                'nav_label' => 'Pyetje',
                'eyebrow'   => 'Pyetje të shpeshta',
                'heading'   => 'Ke ndonjë dyshim? Mirë.',
                'tone'      => 'alt',
                'items'     => $this->table(['question', 'answer'], [
                    [
                        'Çfarë saktësisht përfshihet?',
                        '<p>Të gjitha programet e stërvitjes dhe të gjitha planet ushqimore që janë sot në dyqan, plus çdo program i ri që shtojmë — <strong>pa pagesë shtesë</strong>. I merr të gjitha menjëherë pas pagesës.</p>',
                    ],
                    [
                        'A del vërtet më lirë?',
                        '<p>Po. Paketa kushton më pak se produktet e saj të blera veç e veç, dhe kursimi që sheh te çmimi llogaritet nga çmimet e tyre të sotme.</p>',
                    ],
                    [
                        "Nga t'ia filloj me kaq shumë materiale?",
                        sprintf('<p>Me një program dhe një plan ushqimor. Për shumicën e njerëzve kjo do të thotë %s bashkë me %s; pjesët e tjera i përdor kur ndryshon qëllimi.</p>', $this->link(self::PROGRAM, 'Programi i Stërvitjes 12-Javor'), $this->link(self::DIET, 'Plani Ushqimor 12-Javor')),
                    ],
                    ['A është abonim?', $this->subscriptionAnswer()],
                    ['Po nëse nuk funksionon për mua?', $this->guaranteeAnswer()],
                ]),
                'note'      => $this->medicalNote(),
                'help_box'  => false,
            ]),
            $this->block('final_cta', [
                'heading'               => "12 javë kalojnë\nsido që të jetë.",
                'body'                  => 'Me stërvitjen dhe ushqimin në një plan, pyetja e vetme është nëse fillon sot apo përsëri "të hënën".',
                // The bundle is not on sale itself, so it has no offer to count down to.
                'show_countdown'        => false,
                'product'               => null,
                'primary_label'         => 'Merr paketën e plotë — {price}',
                'primary_action'        => 'buy_now',
                'primary_anchor'        => '',
                'secondary_add_to_cart' => true,
                'note'                  => 'Në vend të {regular_price} · Garanci {guarantee_days} ditë · Akses i menjëhershëm',
            ]),
        ];
    }

    /**
     * index.html, sections 3–16.
     *
     * @return list<Row>
     */
    private function homeBlocks(): array
    {
        return [
            $this->block('home_hero', [
                'heading'          => "Transformo trupin tënd\nnë *12 javë* —\npa hamendësime.",
                'show_proof'       => true,
                'body'             => '<p>Programe stërvitjeje dhe plane ushqimore <strong>në shqip</strong>, të ndërtuara për objektivin, orarin dhe ushqimet që gjenden në <strong>Shqipëri dhe Kosovë</strong>. E marrësh menjëherë në telefon — dhe e ndjek hap pas hapi.</p>',
                'primary_label'    => 'Merr planin tim',
                'primary_anchor'   => 'cmimet',
                'secondary_label'  => 'Si funksionon',
                'secondary_anchor' => 'si-funksionon',
                'reassurance'      => $this->lines('Akses i menjëhershëm', 'Pagesë e njëhershme, pa abonim', 'Garanci 30 ditë'),
                'before_label'     => 'Para',
                'after_label'      => 'Pas 12 javësh',
                'progress_label'   => 'Progresi i javës 8/12',
                'progress_percent' => 68,
                'progress_stats'   => $this->table(['value', 'label'], [
                    ['−9,2', 'kg dhjam'],
                    ['+3,1', 'kg muskul'],
                    ['4×', 'në javë'],
                ]),
                'day_plan_label'   => 'Plani i ditës',
                'day_plan'         => $this->table(['meal', 'kcal'], [
                    ['Mëngjes · Byrek proteinik', '480 kcal'],
                    ['Drekë · Mish pule + oriz', '620 kcal'],
                    ['Darkë · Peshk + sallatë', '510 kcal'],
                ]),
                'stats'            => $this->table(['value', 'label'], [
                    ['{customers}', 'Klientë'],
                    ['{store_rating}★', '{store_reviews} vlerësime'],
                    ['{guarantee_days} ditë', 'Garanci kthimi'],
                    ['7/7', 'Mbështetje në shqip'],
                ]),
            ]),
            $this->block('marquee', [
                'items' => $this->lines(
                    'Tiranë · −11 kg në 14 javë',
                    'Prishtinë · +6 kg masë muskulore',
                    'Durrës · humbi 8 kg pa palestër',
                    'Shkup · barku i sheshtë në 10 javë',
                    'Vlorë · −14 kg, 2 madhësi më poshtë',
                    'Gjakovë · forcë e dyfishuar'
                ),
            ]),
            $this->block('problem', [
                'eyebrow' => 'Problemi',
                'heading' => "Nuk është fajtor vullneti yt.\n*Fajtor është plani.*",
                'intro'   => 'Shumica e njerëzve dorëzohen jo sepse nuk mundohen — por sepse ndjekin plane të kopjuara nga internet, të shkruara për një trup dhe një kuzhinë që nuk është e tyre.',
                'cards'   => $this->table(['icon', 'title', 'text'], [
                    ['list-x', 'Dieta që nuk zbatohet', 'Kinoa, tofu, avokado… ushqime që ose nuk i gjen, ose kushtojnë sa një xhiro pazari.'],
                    ['phone', 'Stërvitje nga TikTok', 'Ushtrime pa rend, pa përsëritje, pa progresion. Lodhesh shumë, ndryshon pak.'],
                    ['clock', 'Javë të humbura', 'Pa e ditur sa kalori e proteina të hash, çdo javë është hamendësim i radhës.'],
                    ['bar-chart', 'Trajner = 150 €/muaj', 'Një trajner personal kushton më shumë në një muaj sa i gjithë programi i Optimum Lift.'],
                ]),
                'footer'  => 'Rezultati? Cikli: filloj → dorëzohem → e marr edhe më keq. *Ne e prishim këtë cikël.*',
            ]),
            $this->block('steps', [
                'anchor'      => 'si-funksionon',
                'nav_label'   => 'Si funksionon',
                'eyebrow'     => 'Zgjidhja',
                'heading'     => '3 hapa. 2 minuta. Pa stres.',
                'intro'       => 'Nuk ka formularë të gjatë, nuk ka pritje, nuk ka abonim që harron ta anulosh.',
                'tone'        => 'alt',
                'steps'       => $this->table(['icon', 'title', 'text'], [
                    ['check-square', 'Zgjidh objektivin', 'Rënie në peshë, masë muskulore apo tonifikim. Zgjidh nivelin: shtëpi ose palestër.'],
                    ['bolt', 'Merr planin menjëherë', 'Pas pagesës, brenda 60 sekondave e ke në email dhe në telefon. PDF + video për çdo ushtrim.'],
                    ['trend-up', 'Ndiq dhe mat progresin', 'Javë pas jave, me tabelën e progresit dhe mbështetje në WhatsApp kur ngec.'],
                ]),
                'cta_label'   => 'Filloj hapin 1 tani',
                'cta_anchor'  => 'cmimet',
                'show_recent' => true,
            ]),
            $this->block('goal_tabs', [
                'anchor'    => 'objektivi',
                'nav_label' => 'Objektivi',
                'eyebrow'   => 'Personalizimi',
                'heading'   => 'Çfarë do të arrish?',
                'intro'     => 'Zgjidh objektivin dhe shiko saktësisht çfarë përmban plani yt.',
                'tabs'      => $this->table(
                    ['label', 'heading', 'text', 'bullets', 'cta_label', 'product', 'cta_anchor', 'stat', 'image_label', 'image_value'],
                    [
                        [
                            'Rënie në peshë',
                            'Humb dhjamin, ruaj muskulin',
                            'Deficit kalorik i llogaritur për peshën tënde — jo vuajtje. Ha bukë, mish, djathë; thjesht në sasinë e duhur.',
                            $this->lines('Kalori & makro të llogaritura për ty', '4 stërvitje/javë, 45 min maksimum', 'Menu javore me ushqime shqiptare', 'Strategji për dasma & festa'),
                            'Shiko planin për rënie në peshë',
                            null,
                            'cmimet',
                            'Mesatarja e klientëve: −0,7 kg/javë',
                            'Rezultat tipik',
                            '−9 kg në 12 javë',
                        ],
                        [
                            'Masë muskulore',
                            'Ndërto masë, pa dhjam të tepërt',
                            'Progresion i mbikëqyrur në peshë dhe përsëritje, me surplus të kontrolluar. Muskul i vërtetë, jo vetëm peshë në peshore.',
                            $this->lines('Split 4–5 ditë (push/pull/këmbë)', 'Tabela e mbingarkesës progresive', '2.800+ kcal me ushqim të vërtetë', "Udhëzues suplementesh (vetëm ç'duhet)"),
                            'Shiko planin për masë muskulore',
                            $this->ids[self::FORCE],
                            '',
                            'Mesatarja e klientëve: +4 kg në 16 javë',
                            'Rezultat tipik',
                            '+4 kg masë e thatë',
                        ],
                        [
                            'Formë & tonifikim',
                            'Formë, forcë dhe vijë e pastër',
                            "Program për ijë, bark dhe të pasme, që mund t'i bësh në shtëpi me minimum pajisje. Pa turp në palestër, pa justifikime.",
                            $this->lines('Versioni shtëpi + versioni palestër', 'Stërvitje 25–35 min për orar të zënë', 'Fokus: bark, ije, të pasme', 'Ushqim i thjeshtë, pa peshore obsesive'),
                            'Shiko planin për tonifikim',
                            $this->ids[self::PROGRAM],
                            '',
                            'Pa pajisje të detyrueshme',
                            'Rezultat tipik',
                            '2 madhësi më poshtë',
                        ],
                    ]
                ),
            ]),
            $this->block('value_stack', [
                'eyebrow'     => 'Çfarë marrësh',
                'heading'     => 'Vlerë e plotë *për një çmim qesharak*',
                'intro'       => 'Në paketën Transformimi Total përfshihet gjithçka që ke nevojë — pa shtesa, pa pagesa të fshehura, pa abonim mujor.',
                'tone'        => 'alt',
                'layout'      => 'split',
                'product'     => $this->ids[self::BUNDLE],
                'items'       => $this->table(['text', 'value', 'bonus'], [
                    ['Programi i stërvitjes 12-javor (PDF + video për çdo ushtrim)', 14.99, false],
                    ['Plani ushqimor javor + lista e pazarit', 12.99, false],
                    ['60 receta shqiptare të rillogaritura (fit)', 5.99, false],
                    ['Kalkulatori i kalorive & makrove (Excel/telefon)', 3.99, false],
                    ['Grupi privat + mbështetje në WhatsApp', 7.99, false],
                    ['Gjurmuesi i progresit + udhëzuesi i matjeve', 2.99, false],
                ]),
                'total_label' => 'Vlera totale',
                'pay_label'   => 'Ti paguan vetëm',
                'cta_label'   => '',
            ]),
            $this->block('pricing', [
                'anchor'         => 'cmimet',
                'heading'        => 'Zgjidh planin tënd',
                'intro'          => 'Programe stërvitjeje, dieta, ose paketa e plotë me çdo gjë. Pagesë e njëhershme, akses i përhershëm, garanci 30 ditë.',
                'show_countdown' => true,
                'bundle'         => null,
                'featured_limit' => 3,
                'rest_heading'   => 'Këto janë më të kërkuarat',
                'rest_text'      => 'Kemi edhe {rest} produkte të tjera — dieta dhe programe për objektiva më specifike.',
                'show_trust'     => true,
            ]),
            $this->block('results', [
                'anchor'     => 'rezultate',
                'nav_label'  => 'Rezultate',
                'eyebrow'    => 'Rezultate',
                'heading'    => 'Njerëz si ty. Rezultate reale.',
                'intro'      => 'Pa filtra, pa Photoshop, pa premtime absurde. Vetëm plan i ndjekur me disiplinë.',
                'tone'       => 'alt',
                'items'      => $this->table(['name', 'meta', 'result', 'quote'], [
                    ['Ardit, 29', 'Tiranë · 14 javë', '−11 kg', "Kisha provuar 3 dieta. Këtu për herë të parë e dija saktë çdo ditë ç'duhej të bëja."],
                    ['Elona, 34', 'Prishtinë · 12 javë', '−8 kg', 'Dy fëmijë, punë 9–17. Stërvitjet 30-minutëshe në shtëpi ishin shpëtimi im.'],
                    ['Blerim, 24', 'Shkup · 16 javë', '+6 kg', 'Hëngra më shumë se kurrë dhe më në fund fillova të rrit peshat çdo javë.'],
                ]),
                'disclaimer' => 'Rezultatet ndryshojnë nga personi në person dhe varen nga zbatimi i planit.',
                'cta_label'  => 'Dua rezultat si ky',
                'cta_anchor' => 'cmimet',
            ]),
            $this->block('reviews', [
                'eyebrow'      => 'Dëshmi',
                'heading'      => 'Çfarë thonë klientët',
                'source'       => 'manual',
                'limit'        => 3,
                'items'        => $this->table(['quote', 'name', 'meta', 'rating'], [
                    ['Më pëlqeu që plani ishte në shqip dhe me ushqime që i gjej në market. Pa gjëra ekzotike që nuk i blen kush.', 'Marin K.', 'Durrës · Paketa e plotë', 5],
                    ['Trajneri në palestër më kërkonte 18.000 lekë në muaj. Këtu pagova një herë dhe kam gjithçka, përgjithmonë.', 'Endrit G.', 'Tiranë · Paketa e plotë', 5],
                    ['Kisha frikë se do ishte e komplikuar. Në të vërtetë e hap PDF-in, lexoj ditën dhe e bëj. Kaq.', 'Arta H.', 'Prishtinë · Plani ushqimor', 5],
                    ['Mbështetja në WhatsApp bëri diferencën. Kur ngeca në javën e 5-të, më ndryshuan planin brenda ditës.', 'Kreshnik B.', 'Gjakovë · Paketa e plotë', 5],
                    ['Recetat shqiptare të rillogaritura janë gjenialitet. Ha byrek dhe humb peshë — kush e kish menduar.', 'Sara L.', 'Vlorë · Paketa e plotë', 5],
                    ['Stërvitjet për shtëpi ishin perfekte për dimrin. Nuk humba as një javë pa u stërvitur.', 'Fatjon D.', 'Elbasan · Programi i stërvitjes', 5],
                ]),
                'show_summary' => true,
            ]),
            $this->block('comparison', [
                'eyebrow' => 'Krahasimi',
                'heading' => 'Pse Optimum Lift?',
                'tone'    => 'alt',
                'columns' => $this->table(['label', 'product', 'highlight'], [
                    ['Optimum Lift', $this->ids[self::BUNDLE], true],
                    ['Trajner personal', null, false],
                    ['Aplikacione të huaja', null, false],
                ]),
                'rows'    => $this->table(['label', 'type', 'cells'], [
                    ['Kostoja', 'price', $this->rows('value', '', '150–200 €/muaj', 'Abonim 10–20 €/muaj')],
                    ['Në gjuhën shqipe', 'text', $this->rows('value', '✓ Plotësisht', '✓', '✕ Vetëm anglisht')],
                    ['Ushqime që gjenden tek ne', 'text', $this->rows('value', '✓ 100%', 'Varet', '✕')],
                    ['Mbështetje njerëzore', 'text', $this->rows('value', '✓ WhatsApp 7/7', '✓', '✕ Chatbot')],
                    ['Fillon menjëherë', 'text', $this->rows('value', '✓ 60 sekonda', 'Duhet takim', '✓')],
                    ['Garanci kthimi parash', 'text', $this->rows('value', '✓ 30 ditë', '✕', 'Vetëm 7 ditë')],
                ]),
                'note'    => '',
            ]),
            $this->block('credibility', [
                'eyebrow' => 'Kush qëndron pas planit',
                'heading' => 'Nuk është plan i shkarkuar nga internet.',
                'name'    => '[Emri i trajnerit]',
                'role'    => 'Themelues · Optimum Lift',
                'body'    => '<p>Programet e Optimum Lift ndërtohen nga trajner i certifikuar me [X] vjet eksperiencë dhe mbi [X] klientë të ndjekur individualisht — dhe përshtaten posaçërisht për mënyrën si hamë, punojmë dhe jetojmë ne shqiptarët.</p>',
                'badges'  => $this->table(['title', 'text', 'icon'], [
                    ['Certifikim ndërkombëtar', '[Emri i certifikimit]', 'medal'],
                    ['[X]+ klientë të ndjekur', 'Nga 2019 e këtej', 'user'],
                    ['Metodë e bazuar në shkencë', 'Pa çajra, pa detox, pa mrekulli', 'bar-chart'],
                    ['Përgjigje brenda 24 orësh', 'Nga ekipi, në WhatsApp', 'chat'],
                ]),
                'chips'   => '',
            ]),
            $this->block('guarantee', [
                'heading'    => 'Garanci 30 ditë — pa pyetje',
                'style'      => 'band',
                'body'       => 'Ndiqe planin për {guarantee_days} ditë. Nëse nuk shikon ndryshim dhe nuk ndihesh më mirë, shkruaj një email dhe të kthejmë 100% të parave — pa formularë, pa justifikime, pa bisedë të pakëndshme. Risku është i jonë, nuk është i yti.',
                'chips'      => $this->lines('Pa abonim i fshehur', 'Pa anulim i komplikuar', 'Materialet i mban'),
                'cta_label'  => '',
                'cta_anchor' => '',
            ]),
            $this->block('faq', [
                'anchor'    => 'faq',
                'nav_label' => 'Pyetje',
                'eyebrow'   => 'Pyetje të shpeshta',
                'heading'   => 'Ke ndonjë dyshim? Mirë.',
                'intro'     => 'Këtu janë përgjigjet e 8 pyetjeve që bëhen më shpesh.',
                'tone'      => 'alt',
                'items'     => $this->table(['question', 'answer'], [
                    [
                        'A më duhet palestër për ta ndjekur?',
                        '<p>Jo. Çdo program vjen në dy versione: <strong>shtëpi</strong> (pa pajisje ose me një palë dumbbell) dhe <strong>palestër</strong>. Mund të kalosh nga një version në tjetrin kur të duash, pa pagesë shtesë.</p>',
                    ],
                    [
                        'Sa kohë më duhet në ditë?',
                        '<p>Nga <strong>25 deri 50 minuta, 3–5 herë në javë</strong>. Programi ka edhe variantin "orar i zënë" me stërvitje 25-minutëshe, për javët kur nuk ke kohë.</p>',
                    ],
                    [
                        'Ushqimet gjenden në Shqipëri dhe Kosovë? Sa kushtojnë?',
                        '<p>Të gjitha ushqimet blihen në çdo market ose pazar tek ne: vezë, jogurt, gjizë, pulë, peshk, oriz, bukë, fasule, perime të stinës. Pa suplemente të detyrueshme dhe pa produkte "ekzotike". Lista e pazarit është e ndarë javë pas jave, që të mos harxhosh më shumë sesa duhet.</p>',
                    ],
                    ['Si e marr programin pasi paguaj?', $this->deliveryAnswer('PDF + videot')],
                    [
                        'Jam fillestar / mbi 40 vjeç. Është për mua?',
                        '<p>Po. Programi ka tre nivele — <strong>fillestar, mesatar, i avancuar</strong> — dhe fillon nga java 1 me lëvizje bazë dhe video të detajuara për teknikën. Mosha nuk është pengesë; plani përshtatet me nivelin tënd, jo anasjelltas.</p>',
                    ],
                    [
                        'Si mund të paguaj? A është e sigurt?',
                        '<p>Me kartë (Visa ose Mastercard). Pagesa procesohet nga një platformë pagesash e certifikuar, me enkriptim SSL — <strong>ne nuk i shohim dhe nuk i ruajmë të dhënat e kartës tënde</strong>.</p>',
                    ],
                    [
                        'Ka abonim mujor që duhet anuluar?',
                        '<p>Nuk ka. Paguan <strong>një herë</strong> dhe materialet janë të tua përgjithmonë, përfshirë përditësimet e ardhshme falas (në paketën e plotë). Asnjë tarifë automatike, asnjë surprizë në ekstraktin bankar.</p>',
                    ],
                    ['Po nëse nuk funksionon për mua?', $this->guaranteeAnswer()],
                ]),
                'note'      => '',
                'help_box'  => true,
            ]),
            $this->block('final_cta', [
                'heading'               => "12 javë kalojnë\nsido që të jetë.",
                'body'                  => 'Pyetja e vetme është: në javën e 12-të do të shikosh një trup të ndryshuar — apo do të thuash përsëri "nga e hëna filloj"?',
                // Its Product is the bundle, which has no offer of its own.
                'show_countdown'        => false,
                'product'               => null,
                'primary_label'         => 'Merr paketën e plotë — {price}',
                'primary_action'        => 'anchor',
                'primary_anchor'        => 'cmimet',
                'secondary_add_to_cart' => false,
                'note'                  => 'Në vend të {regular_price} · Garanci {guarantee_days} ditë · Akses i menjëhershëm',
            ]),
        ];
    }

    private function deliveryAnswer(string $files): string
    {
        return sprintf('<p>Menjëherë. Brenda <strong>60 sekondave</strong> të vjen emaili me linkun e shkarkimit (%s). E hap në telefon, tablet ose kompjuter dhe e ke përgjithmonë — pa aplikacion për të instaluar.</p>', $files);
    }

    private function dietDeliveryAnswer(): string
    {
        return '<p>Menjëherë. Brenda <strong>60 sekondave</strong> të vjen emaili me linkun e shkarkimit (PDF-të e planit, recetat dhe listat e pazarit). E hap në telefon, tablet ose kompjuter — pa aplikacion për të instaluar.</p>';
    }

    private function guaranteeAnswer(): string
    {
        return '<p>Ke <strong>30 ditë garanci të plotë</strong>. Provoje, ndiqe, mate. Nëse nuk të bind, shkruaj një email dhe të kthejmë të gjithë shumën — dhe materialet i mban për vete.</p>';
    }

    private function subscriptionAnswer(): string
    {
        return '<p>Jo. Paguan <strong>një herë</strong> dhe e ke përgjithmonë, bashkë me përditësimet e ardhshme. Nuk ka çfarë të anulosh.</p>';
    }

    /**
     * The disclaimer every diet page keeps (brief §4.3).
     */
    private function medicalNote(): string
    {
        return '<p><strong>Shënim:</strong> Ky plan është material informativ dhe edukativ për njerëz të shëndetshëm — nuk është këshillë mjekësore dhe nuk zëvendëson mjekun. Nëse je shtatzënë ose me gji, nën 18 vjeç, merr mjekim, ose ke ndonjë gjendje shëndetësore, konsultohu me mjekun ose me një nutricionist të licencuar para se ta fillosh.</p>';
    }

    private function oneTimeNote(): string
    {
        return 'Pagesë e njëhershme · Akses i menjëhershëm · Garanci {guarantee_days} ditë';
    }
}

WP_CLI::add_command('ol-shop', ShopCommand::class);
