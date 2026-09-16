<?php

/**
 * Which Plans a Product includes. A bundle includes several.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Access;

use OptimumLift\Plans\Content\PostTypes;

final class ProductFields
{
    public function register(): void
    {
        add_action('acf/include_fields', [$this, 'registerFields']);
    }

    public function registerFields(): void
    {
        acf_add_local_field_group([
            'key'      => 'group_ol_product_plans',
            'title'    => __('Training Plans', 'optimum-lift-plans'),
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'product']]],
            'position' => 'side',
            'fields'   => [
                [
                    'key'           => 'field_ol_product_plans',
                    'name'          => 'plans',
                    'label'         => __('Plans included', 'optimum-lift-plans'),
                    'instructions'  => __('Buying this Product gives Access to these Plans. Mark the Product Virtual. Changing this later does not affect past buyers.', 'optimum-lift-plans'),
                    'type'          => 'post_object',
                    'post_type'     => [PostTypes::PLAN],
                    'post_status'   => ['publish'],
                    'multiple'      => 1,
                    'ui'            => 1,
                    'allow_null'    => 1,
                    'return_format' => 'id',
                ],
            ],
        ]);
    }

    /**
     * @return list<int>
     */
    public static function planIds(int $productId): array
    {
        $ids = get_field('plans', $productId);

        return is_array($ids) ? array_values(array_unique(array_filter(array_map('intval', $ids)))) : [];
    }
}
