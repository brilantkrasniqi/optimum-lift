<?php

/**
 * An Exercise's library key: the slug that names it in the library data and in
 * Plan files, the same on every site. Post IDs differ from site to site, so
 * anything stored as a file must never carry one.
 *
 * A key is set once and never changes. A hand-made Exercise gets one from its
 * title the first time it is saved with a title; the library importer sets its
 * own; a title edited later does not rename it.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Content;

use WP_Post;

final class LibraryKeys
{
    public const FIELD = 'field_ol_exercise_library_key';
    public const META  = 'library_key';

    private const MAX_LENGTH = 100;

    public function register(): void
    {
        add_filter('acf/update_value/key=' . self::FIELD, [$this, 'protect'], 5, 2);
        // WordPress runs save_post_{type} before the generic save_post, where
        // ACF saves the edit form, so the key exists before the form's own
        // (empty, read-only) value reaches protect().
        add_action('save_post_' . PostTypes::EXERCISE, [$this, 'ensure'], 10, 2);
    }

    /**
     * Whatever is being saved, a key that is already set stays. An empty one
     * takes the incoming key when it is valid and unused, else one made from
     * the title.
     */
    public function protect(mixed $value, int|string $postId): string
    {
        $incoming = is_string($value) ? trim($value) : '';
        $id       = is_numeric($postId) ? (int) $postId : 0;

        if ($id <= 0) {
            return $incoming;
        }

        $stored = $this->stored($id);

        if ($stored !== '') {
            return $stored;
        }

        if ($this->isValid($incoming) && $this->find($incoming, $id) === null) {
            return $incoming;
        }

        return $this->unique((string) get_post_field('post_title', $id), $id);
    }

    public function ensure(int $postId, WP_Post $post): void
    {
        if ($post->post_status === 'auto-draft' || $this->stored($postId) !== '') {
            return;
        }

        $key = $this->unique($post->post_title, $postId);

        if ($key !== '') {
            update_field(self::FIELD, $key, $postId);
        }
    }

    /**
     * Lowercase letters and digits in groups joined by single hyphens.
     */
    public function isValid(string $key): bool
    {
        return strlen($key) <= self::MAX_LENGTH && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $key) === 1;
    }

    /**
     * The Exercise holding a key, in any status including the trash, other
     * than `$exceptPostId`.
     */
    public function find(string $key, int $exceptPostId = 0): ?int
    {
        global $wpdb;

        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT m.post_id FROM {$wpdb->postmeta} m
             INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id AND p.post_type = %s
             WHERE m.meta_key = %s AND m.meta_value = %s AND m.post_id <> %d
             ORDER BY m.post_id LIMIT 1",
            PostTypes::EXERCISE,
            self::META,
            $key,
            $exceptPostId
        ));

        return $id === null ? null : (int) $id;
    }

    /**
     * A free key made from a title, or '' when the title has no letters or
     * digits to build one from.
     */
    public function unique(string $title, int $exceptPostId = 0): string
    {
        $base = $this->slug($title);

        if ($base === '') {
            return '';
        }

        $key = $base;

        for ($n = 2; $this->find($key, $exceptPostId) !== null; $n++) {
            $key = $base . '-' . $n;
        }

        return $key;
    }

    /**
     * "Farmer's walk" becomes "farmers-walk". Not sanitize_title(): it keeps
     * non-ASCII characters as percent-encoded octets, which are not valid here.
     * tools/build-exercise-library.mjs follows the same rules.
     */
    public function slug(string $title): string
    {
        $ascii = str_replace(["'", "\u{2019}"], '', remove_accents($title));
        $slug  = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii)), '-');
        $slug  = trim(substr($slug, 0, self::MAX_LENGTH - 4), '-');

        return $slug;
    }

    private function stored(int $postId): string
    {
        $value = get_post_meta($postId, self::META, true);

        return is_string($value) ? $value : '';
    }
}
