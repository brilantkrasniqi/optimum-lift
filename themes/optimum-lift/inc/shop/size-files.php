<?php

/**
 * The "Size PDFs" box on a variable Product's edit screen (ADR-0013): one
 * upload of the renderer's PDFs (`content/diets/`) puts each file on the
 * variation its name names, and a table shows whether every Size is ready to
 * sell.
 *
 * A file name is `<plan prefix>-<values>kg.pdf`, the values being the
 * variation's attribute values in the parent's order. A men-only plan still
 * writes its gender, so `plan-mashkull-80-90kg.pdf` names the Pesha-only
 * variation `80-90` too, with `plan-mashkull` as its prefix. A diet's
 * variation holds one file; a bundle's holds one per plan prefix.
 *
 * Files live in WooCommerce's protected `woocommerce_uploads/diets/<slug>/`.
 * A re-upload overwrites the same path and keeps the download's ID, so
 * Customers who already bought get the new file through their old link.
 */

declare(strict_types=1);

/**
 * The Product's variations in the Size picker's order (attribute by
 * attribute, each in its term order).
 *
 * @return list<WC_Product_Variation>
 */
function optimum_lift_size_variations(WC_Product_Variable $product): array
{
    $order = [];
    foreach ($product->get_attributes() as $attribute) {
        if (!$attribute instanceof WC_Product_Attribute || !$attribute->get_variation()) {
            continue;
        }

        // The picker's order: wc_get_product_terms() sorts by the terms'
        // own order, where WC_Product_Attribute::get_terms() sorts by name.
        $values = $attribute->is_taxonomy()
            ? array_map(
                static fn (WP_Term $term): string => $term->slug,
                array_filter(wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'all']), static fn (mixed $t): bool => $t instanceof WP_Term)
            )
            : $attribute->get_options();
        $order['attribute_' . sanitize_title($attribute->get_name())] = array_flip(array_map('strval', $values));
    }

    $variations = [];
    foreach ($product->get_children() as $id) {
        $variation = wc_get_product($id);
        if ($variation instanceof WC_Product_Variation && in_array($variation->get_status(), ['publish', 'private'], true)) {
            $variations[] = $variation;
        }
    }

    $rank = static function (WC_Product_Variation $v) use ($order): array {
        $values = $v->get_variation_attributes();
        $keys   = [];
        foreach ($order as $key => $positions) {
            $keys[] = $positions[$values[$key] ?? ''] ?? PHP_INT_MAX;
        }

        return $keys;
    };
    usort($variations, static fn (WC_Product_Variation $a, WC_Product_Variation $b): int => $rank($a) <=> $rank($b));

    return $variations;
}

/**
 * The file-name suffix of a variation ("-mashkull-80-90"), or null when it
 * has an "Any …" attribute.
 */
function optimum_lift_size_file_suffix(WC_Product_Variation $variation): ?string
{
    $values = $variation->get_variation_attributes();
    $parts  = [];
    foreach (array_keys(optimum_lift_size_keys(optimum_lift_base_product($variation))) as $key) {
        $value = (string) ($values[$key] ?? '');
        if ($value === '') {
            return null;
        }
        $parts[] = sanitize_title($value);
    }

    return $parts === [] ? null : '-' . implode('-', $parts);
}

/**
 * A file name without `.pdf` and a trailing `kg`, lower case.
 */
function optimum_lift_size_file_stem(string $name): string
{
    $stem = strtolower(wp_basename($name));
    $stem = (string) preg_replace('/\.pdf$/', '', $stem);

    return (string) preg_replace('/kg$/', '', $stem);
}

/**
 * The plan prefix of a file stored for a variation, or null when its name
 * does not end with the variation's values.
 */
function optimum_lift_size_file_prefix(string $file, WC_Product_Variation $variation): ?string
{
    $suffix = optimum_lift_size_file_suffix($variation);
    $stem   = optimum_lift_size_file_stem((string) wp_parse_url($file, PHP_URL_PATH));
    if ($suffix === null || !str_ends_with($stem, $suffix) || strlen($stem) === strlen($suffix)) {
        return null;
    }

    return substr($stem, 0, -strlen($suffix));
}

/**
 * Where the Product's Size PDFs are stored: WordPress's uploads folder,
 * WooCommerce's protected `woocommerce_uploads` inside it, then
 * `diets/<slug>`.
 *
 * @return array{path: string, url: string, subdir: string}
 */
function optimum_lift_size_files_dir(WC_Product $product): array
{
    $uploads = wp_upload_dir(null, false);
    $subdir  = '/woocommerce_uploads/diets/' . sanitize_title($product->get_slug() !== '' ? $product->get_slug() : (string) $product->get_id());

    return [
        'path'   => $uploads['basedir'] . $subdir,
        'url'    => $uploads['baseurl'] . $subdir,
        'subdir' => $subdir,
    ];
}

/**
 * What stops a Size from selling as it should, as plain sentences.
 *
 * @return list<string>
 */
function optimum_lift_size_warnings(WC_Product_Variable $product): array
{
    $warnings   = [];
    $variations = optimum_lift_size_variations($product);
    $prices     = array_unique(array_map(static fn (WC_Product_Variation $v): string => (string) $v->get_price('edit'), $variations));
    $sized      = optimum_lift_is_bundle($product)
        ? count(array_filter(optimum_lift_bundle_components($product), 'optimum_lift_needs_choice'))
        : 1;

    if (count($prices) > 1) {
        $warnings[] = __('The Sizes have different prices. Every Size of a Product must cost the same; the page shows the lowest price.', 'optimum-lift');
    }

    foreach ($variations as $variation) {
        $label = optimum_lift_size_label($variation);
        $label = $label !== '' ? $label : '#' . $variation->get_id();
        $files = count($variation->get_downloads());

        if (optimum_lift_size_file_suffix($variation) === null) {
            /* translators: %s: Size, such as "Mashkull · 80–90 kg", or a variation number. */
            $warnings[] = sprintf(__('%s has an "Any …" attribute. Give it one value per attribute: such Sizes cannot be bought.', 'optimum-lift'), $label);
        }
        if (!$variation->is_virtual()) {
            /* translators: %s: Size. */
            $warnings[] = sprintf(__('%s is not Virtual, so checkout asks for an address and skips the withdrawal waiver.', 'optimum-lift'), $label);
        }
        if (!$variation->is_downloadable() || $files === 0) {
            /* translators: %s: Size. */
            $warnings[] = sprintf(__('%s has no PDF.', 'optimum-lift'), $label);
        } elseif ($files < $sized) {
            /* translators: 1: Size, 2: number of files it has, 3: number of sized diets in the bundle. */
            $warnings[] = sprintf(__('%1$s has %2$d PDFs, but the bundle contains %3$d diets sold in Sizes.', 'optimum-lift'), $label, $files, $sized);
        }
    }

    return $warnings;
}

/*
 * ------------------------------------------------------------------- the box
 */

add_action('add_meta_boxes_product', static function (WP_Post $post): void {
    $product = wc_get_product($post->ID);
    if (!$product instanceof WC_Product_Variable) {
        return;
    }

    add_meta_box('ol-size-files', __('Size PDFs', 'optimum-lift'), 'optimum_lift_size_files_box', 'product', 'normal', 'default');
});

// A file field needs a multipart form.
add_action('post_edit_form_tag', static function (WP_Post $post): void {
    if ($post->post_type === 'product') {
        echo ' enctype="multipart/form-data"';
    }
});

function optimum_lift_size_files_box(WP_Post $post): void
{
    $product = wc_get_product($post->ID);
    if (!$product instanceof WC_Product_Variable) {
        return;
    }

    $warnings = optimum_lift_size_warnings($product);
    $yes      = __('Yes', 'optimum-lift');
    $no       = __('No', 'optimum-lift');

    wp_nonce_field('ol_size_files', 'ol_size_files_nonce');
    ?>
    <p><?php esc_html_e('Upload the PDFs the diet renderer made for this Product, all at once. Each file goes on the Size its name ends with ("…-mashkull-80-90kg.pdf"). A file with the same plan name replaces the old one and keeps its download link.', 'optimum-lift'); ?></p>

    <?php foreach ($warnings as $warning) : ?>
        <div class="notice notice-warning inline"><p><?php echo esc_html($warning); ?></p></div>
    <?php endforeach; ?>

    <table class="widefat striped" style="margin-top: 12px">
        <thead>
            <tr>
                <th><?php esc_html_e('Size', 'optimum-lift'); ?></th>
                <th><?php esc_html_e('Price', 'optimum-lift'); ?></th>
                <th><?php esc_html_e('Virtual', 'optimum-lift'); ?></th>
                <th><?php esc_html_e('Downloadable', 'optimum-lift'); ?></th>
                <th><?php esc_html_e('Files', 'optimum-lift'); ?></th>
                <th><?php esc_html_e('Status', 'optimum-lift'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (optimum_lift_size_variations($product) as $variation) : ?>
                <?php
                $files = array_map(
                    static fn (WC_Product_Download $d): string => wp_basename((string) wp_parse_url($d->get_file(), PHP_URL_PATH)),
                    array_values($variation->get_downloads())
                );
                $label = optimum_lift_size_label($variation);
                ?>
                <tr>
                    <td><?php echo esc_html($label !== '' ? $label : '#' . $variation->get_id()); ?></td>
                    <td><?php echo $variation->get_price('edit') !== '' ? wp_kses_post(wc_price((float) $variation->get_price('edit'))) : '&mdash;'; ?></td>
                    <td><?php echo esc_html($variation->is_virtual() ? $yes : $no); ?></td>
                    <td><?php echo esc_html($variation->is_downloadable() ? $yes : $no); ?></td>
                    <td><?php echo $files !== [] ? implode('<br>', array_map('esc_html', $files)) : '&mdash;'; ?></td>
                    <td><?php echo esc_html($variation->get_status() === 'publish' ? __('Published', 'optimum-lift') : __('Private', 'optimum-lift')); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p>
        <label for="ol-size-files-input"><strong><?php esc_html_e('Upload PDFs', 'optimum-lift'); ?></strong></label><br>
        <input type="file" id="ol-size-files-input" name="ol_size_files[]" accept=".pdf,application/pdf" multiple>
    </p>
    <p class="description"><?php esc_html_e('The files are attached when you update the Product.', 'optimum-lift'); ?></p>
    <?php
}

/*
 * ---------------------------------------------------------------- the upload
 */

/**
 * The uploaded files, one entry per file.
 *
 * @return list<array{name: string, tmp_name: string, error: int}>
 */
function optimum_lift_size_files_uploaded(): array
{
    $files = $_FILES['ol_size_files'] ?? null;
    if (!is_array($files) || !is_array($files['name'] ?? null)) {
        return [];
    }

    $list = [];
    foreach ($files['name'] as $i => $name) {
        $error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $list[] = [
            'name'     => sanitize_file_name(wp_unslash((string) $name)),
            'tmp_name' => (string) ($files['tmp_name'][$i] ?? ''),
            'error'    => $error,
        ];
    }

    return $list;
}

/**
 * Matches each upload to its variation and plan prefix. Returns the plan, or
 * the problems: an upload is attached only when every file has exactly one
 * place.
 *
 * @param list<array{name: string, tmp_name: string, error: int}> $files
 * @return array{plan: list<array{file: array{name: string, tmp_name: string, error: int}, variation: WC_Product_Variation, prefix: string}>, errors: list<string>}
 */
function optimum_lift_size_files_plan(WC_Product_Variable $product, array $files): array
{
    $variations = optimum_lift_size_variations($product);
    $bundle     = optimum_lift_is_bundle($product);
    $plan       = [];
    $errors     = [];
    $slots      = [];

    foreach ($files as $file) {
        $name = $file['name'];
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            /* translators: %s: file name. */
            $errors[] = sprintf(__('%s did not upload. Try again, or upload fewer files at once.', 'optimum-lift'), $name);
            continue;
        }

        $type = wp_check_filetype_and_ext($file['tmp_name'], $name, ['pdf' => 'application/pdf']);
        if (($type['ext'] ?? '') !== 'pdf') {
            /* translators: %s: file name. */
            $errors[] = sprintf(__('%s is not a PDF.', 'optimum-lift'), $name);
            continue;
        }

        $matches = [];
        foreach ($variations as $variation) {
            $prefix = optimum_lift_size_file_prefix($name, $variation);
            if ($prefix !== null) {
                $matches[] = ['file' => $file, 'variation' => $variation, 'prefix' => $prefix];
            }
        }

        if (count($matches) !== 1) {
            $errors[] = $matches === []
                /* translators: %s: file name. */
                ? sprintf(__('%s matches no Size of this Product. Its name must end with a Size, like "-mashkull-80-90kg.pdf".', 'optimum-lift'), $name)
                /* translators: %s: file name. */
                : sprintf(__('%s matches more than one Size.', 'optimum-lift'), $name);
            continue;
        }

        // A diet's Size takes one file; a bundle's, one per plan.
        $match = $matches[0];
        $slot  = $match['variation']->get_id() . ($bundle ? '|' . $match['prefix'] : '');
        if (isset($slots[$slot])) {
            $errors[] = sprintf(
                /* translators: 1: file name, 2: another file name, 3: Size. */
                __('%1$s and %2$s are both for %3$s.', 'optimum-lift'),
                $slots[$slot],
                $name,
                optimum_lift_size_label($match['variation'])
            );
            continue;
        }

        $slots[$slot] = $name;
        $plan[]       = $match;
    }

    return ['plan' => $errors === [] ? $plan : [], 'errors' => $errors];
}

/**
 * Stores the planned files and puts them on their variations. Returns the
 * problems, if any.
 *
 * @param list<array{file: array{name: string, tmp_name: string, error: int}, variation: WC_Product_Variation, prefix: string}> $plan
 * @return list<string>
 */
function optimum_lift_size_files_attach(WC_Product_Variable $product, array $plan): array
{
    $dir    = optimum_lift_size_files_dir($product);
    $bundle = optimum_lift_is_bundle($product);
    $errors = [];
    if (!wp_mkdir_p($dir['path'])) {
        return [__('The upload folder could not be created. Check that wp-content/uploads is writable.', 'optimum-lift')];
    }

    // Through WordPress's upload handling, into this Product's folder, under
    // the file's own name so a re-upload overwrites it.
    $to_dir = static fn (array $uploads): array => array_merge($uploads, $dir);
    add_filter('upload_dir', $to_dir);

    $stored = [];
    foreach ($plan as $i => $item) {
        $file   = $item['file'];
        $upload = [
            'name'     => $file['name'],
            'tmp_name' => $file['tmp_name'],
            'error'    => $file['error'],
            'size'     => (int) filesize($file['tmp_name']),
            'type'     => 'application/pdf',
        ];
        $result = wp_handle_upload(
            $upload,
            [
                'test_form'                => false,
                'mimes'                    => ['pdf' => 'application/pdf'],
                'unique_filename_callback' => static fn (string $directory, string $name): string => $name,
            ]
        );
        if (isset($result['error']) || !isset($result['url'])) {
            /* translators: 1: file name, 2: the reason. */
            $errors[] = sprintf(__('%1$s could not be stored: %2$s', 'optimum-lift'), $file['name'], (string) ($result['error'] ?? ''));
            continue;
        }
        $stored[$i] = (string) $result['url'];
    }

    remove_filter('upload_dir', $to_dir);

    // All or nothing here too: a file that could not be stored attaches none.
    if ($errors !== []) {
        return $errors;
    }

    // Group by variation, then rebuild each one's downloads.
    $by_variation = [];
    foreach ($plan as $i => $item) {
        if (isset($stored[$i])) {
            $by_variation[$item['variation']->get_id()][] = ['url' => $stored[$i], 'prefix' => $item['prefix'], 'variation' => $item['variation']];
        }
    }

    foreach ($by_variation as $items) {
        $variation = $items[0]['variation'];
        $existing  = $variation->get_downloads();
        $label     = optimum_lift_size_label($variation);
        $downloads = [];
        $spare     = [];

        // A new file takes the ID of the same plan's file this box stored,
        // else of another file, in turn, so buyers' links keep working. A
        // bundle keeps the files no new one replaced; a diet holds one file.
        $prefixes  = array_column($items, 'prefix');
        $by_prefix = [];
        foreach ($existing as $id => $download) {
            $prefix = optimum_lift_size_file_prefix($download->get_file(), $variation);
            $ours   = str_contains($download->get_file(), $dir['subdir'] . '/');
            if ($prefix !== null && $ours) {
                $by_prefix[$prefix] = (string) $id;
            }
            if ($bundle && $ours && $prefix !== null && !in_array($prefix, $prefixes, true)) {
                $downloads[(string) $id] = $download;
            } elseif ($prefix === null || !in_array($prefix, $prefixes, true)) {
                $spare[] = (string) $id;
            }
        }

        foreach ($items as $item) {
            $id = $by_prefix[$item['prefix']] ?? array_shift($spare) ?? wp_generate_uuid4();

            $download = new WC_Product_Download();
            $download->set_id($id);
            $download->set_file($item['url']);
            $download->set_name(trim($product->get_name() . ' — ' . $label . ($bundle ? ' — ' . ucfirst(str_replace('-', ' ', $item['prefix'])) : '')));
            $downloads[$id] = $download;
        }

        if ($bundle) {
            foreach ($spare as $id) {
                $downloads[$id] = $existing[$id];
            }
        }

        try {
            $variation->set_virtual(true);
            $variation->set_downloadable(true);
            $variation->set_downloads($downloads);
            $variation->save();
        } catch (WC_Data_Exception $e) {
            /* translators: 1: Size, 2: the reason. */
            $errors[] = sprintf(__('%1$s: %2$s', 'optimum-lift'), $label, $e->getMessage());
        }
    }

    optimum_lift_size_files_clean($product, $dir);
    wc_delete_product_transients($product->get_id());

    return $errors;
}

/**
 * Deletes the PDFs in the Product's folder that no variation uses any more.
 *
 * @param array{path: string, url: string, subdir: string} $dir
 */
function optimum_lift_size_files_clean(WC_Product_Variable $product, array $dir): void
{
    $used = [];
    foreach (optimum_lift_size_variations($product) as $variation) {
        foreach ($variation->get_downloads() as $download) {
            $used[] = wp_basename((string) wp_parse_url($download->get_file(), PHP_URL_PATH));
        }
    }

    foreach (glob(trailingslashit($dir['path']) . '*.pdf') ?: [] as $path) {
        if (!in_array(wp_basename($path), $used, true)) {
            wp_delete_file($path);
        }
    }
}

// After WooCommerce has saved the Product (priority 10) and its images (20).
add_action('woocommerce_process_product_meta', static function (mixed $post_id): void {
    $nonce = $_POST['ol_size_files_nonce'] ?? '';
    if (
        !is_numeric($post_id)
        || !is_string($nonce)
        || !wp_verify_nonce(wp_unslash($nonce), 'ol_size_files')
        || !current_user_can('edit_product', (int) $post_id)
    ) {
        return;
    }

    $product = wc_get_product((int) $post_id);
    if (!$product instanceof WC_Product_Variable) {
        return;
    }

    $notice = ['errors' => [], 'attached' => 0];
    $files  = optimum_lift_size_files_uploaded();
    if ($files !== []) {
        $result = optimum_lift_size_files_plan($product, $files);
        $notice['errors'] = $result['errors'];
        if ($result['errors'] === []) {
            $notice['errors']   = optimum_lift_size_files_attach($product, $result['plan']);
            $notice['attached'] = count($result['plan']) - count($notice['errors']);
            $product            = wc_get_product($product->get_id());
        }
    }

    $notice['warnings'] = $product instanceof WC_Product_Variable ? optimum_lift_size_warnings($product) : [];
    if ($notice['errors'] !== [] || $notice['warnings'] !== [] || $notice['attached'] > 0) {
        set_transient('optimum_lift_size_files_' . get_current_user_id(), $notice, HOUR_IN_SECONDS);
    }
}, 50);

add_action('admin_notices', static function (): void {
    $key    = 'optimum_lift_size_files_' . get_current_user_id();
    $notice = get_transient($key);
    if (!is_array($notice)) {
        return;
    }

    delete_transient($key);

    $errors   = array_filter((array) ($notice['errors'] ?? []), 'is_string');
    $warnings = array_filter((array) ($notice['warnings'] ?? []), 'is_string');
    $attached = (int) ($notice['attached'] ?? 0);

    if ($errors !== []) {
        echo '<div class="notice notice-error"><p><strong>' . esc_html__('The Size PDFs were not attached:', 'optimum-lift') . '</strong></p><ul>';
        foreach ($errors as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul></div>';
    } elseif ($attached > 0) {
        /* translators: %d: number of PDFs. */
        echo '<div class="notice notice-success"><p>' . esc_html(sprintf(_n('%d PDF attached.', '%d PDFs attached.', $attached, 'optimum-lift'), $attached)) . '</p></div>';
    }

    if ($warnings !== []) {
        echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Size PDFs:', 'optimum-lift') . '</strong></p><ul>';
        foreach ($warnings as $warning) {
            echo '<li>' . esc_html($warning) . '</li>';
        }
        echo '</ul></div>';
    }
});
