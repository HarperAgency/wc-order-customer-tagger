<?php
/**
 * Seeds default tags and rules on first install.
 * Only runs once — guarded by a wp_option flag.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Db;

if (!defined('ABSPATH')) exit;

class Seeder
{
    private const SEEDED_OPTION = 'harper_tagger_seeded';

    public static function maybeRun(): void
    {
        if (get_option(self::SEEDED_OPTION)) {
            return;
        }
        self::run();
        update_option(self::SEEDED_OPTION, '1');
    }

    public static function run(): void
    {
        global $wpdb;
        $tags  = $wpdb->prefix . 'harper_tagger_tags';
        $rules = $wpdb->prefix . 'harper_tagger_rules';

        foreach (self::defaultTags() as $tag) {
            $wpdb->insert($tags, [
                'name'      => $tag['name'],
                'slug'      => $tag['slug'],
                'color'     => $tag['color'],
                'type'      => $tag['type'],
                'image_url' => null,
            ]);
            $tagId = (int) $wpdb->insert_id;

            foreach ($tag['rules'] as $rule) {
                $wpdb->insert($rules, [
                    'tag_id'     => $tagId,
                    'label'      => $rule['label'],
                    'rule_trigger' => $rule['trigger'],
                    'operator'   => $rule['operator'],
                    'conditions' => json_encode($rule['conditions']),
                    'priority'   => $rule['priority'],
                    'is_active'  => 1,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** @return array<int, array<string, mixed>> */
    private static function defaultTags(): array
    {
        return [

            // ── ORDER TAGS ────────────────────────────────────────────────────

            [
                'name'  => 'High Value',
                'slug'  => 'high-value',
                'color' => '#e67e22',
                'type'  => 'order',
                'rules' => [[
                    'label'      => 'Order total over $500',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'order_total', 'operator' => 'gt', 'value' => 500],
                    ],
                ]],
            ],

            [
                'name'  => 'Unconfirmed Payment',
                'slug'  => 'unconfirmed-payment',
                'color' => '#e74c3c',
                'type'  => 'order',
                'rules' => [[
                    'label'      => 'Payment pending or on hold',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'payment_status', 'operator' => 'in',
                         'value' => ['pending', 'on-hold']],
                    ],
                ]],
            ],

            [
                'name'  => 'Address Mismatch',
                'slug'  => 'address-mismatch',
                'color' => '#c0392b',
                'type'  => 'order',
                'rules' => [[
                    'label'      => 'Billing address differs from shipping address',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'address_mismatch', 'operator' => 'is_true', 'value' => true],
                    ],
                ]],
            ],

            [
                'name'  => 'Guest Checkout',
                'slug'  => 'guest-checkout',
                'color' => '#7f8c8d',
                'type'  => 'order',
                'rules' => [[
                    'label'      => 'Order placed without an account',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'is_guest', 'operator' => 'is_true', 'value' => true],
                    ],
                ]],
            ],

            [
                'name'  => 'First Order',
                'slug'  => 'first-order',
                'color' => '#3498db',
                'type'  => 'order',
                'rules' => [[
                    'label'      => 'Customer\'s first completed order',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'is_first_order', 'operator' => 'is_true', 'value' => true],
                    ],
                ]],
            ],

            [
                'name'  => 'Has Coupon',
                'slug'  => 'has-coupon',
                'color' => '#1abc9c',
                'type'  => 'order',
                'rules' => [[
                    'label'      => 'Discount coupon applied',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'has_coupon', 'operator' => 'is_true', 'value' => true],
                    ],
                ]],
            ],

            [
                'name'  => 'Bulk Order',
                'slug'  => 'bulk',
                'color' => '#2980b9',
                'type'  => 'order',
                'rules' => [[
                    'label'      => '10 or more items',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'item_count', 'operator' => 'gte', 'value' => 10],
                    ],
                ]],
            ],

            [
                'name'  => 'Local Pickup',
                'slug'  => 'local-pickup',
                'color' => '#27ae60',
                'type'  => 'order',
                'rules' => [[
                    'label'      => 'Shipping method is local pickup',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'shipping_method', 'operator' => 'contains', 'value' => 'local_pickup'],
                    ],
                ]],
            ],

            [
                'name'  => 'Free Shipping',
                'slug'  => 'free-shipping',
                'color' => '#16a085',
                'type'  => 'order',
                'rules' => [[
                    'label'      => 'Free shipping applied',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'shipping_method', 'operator' => 'contains', 'value' => 'free_shipping'],
                    ],
                ]],
            ],

            [
                'name'  => 'International',
                'slug'  => 'international',
                'color' => '#8e44ad',
                'type'  => 'order',
                'rules' => [[
                    'label'      => 'Shipping outside US and Canada',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'shipping_country', 'operator' => 'not_in', 'value' => ['US', 'CA']],
                    ],
                ]],
            ],

            // ── CUSTOMER TAGS ─────────────────────────────────────────────────

            [
                'name'  => 'VIP',
                'slug'  => 'vip',
                'color' => '#f1c40f',
                'type'  => 'customer',
                'rules' => [[
                    'label'      => 'High LTV and frequent buyer',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'customer_ltv',          'operator' => 'gt',  'value' => 1000],
                        ['field' => 'customer_order_count',  'operator' => 'gte', 'value' => 5],
                    ],
                ]],
            ],

            [
                'name'  => 'Repeat Buyer',
                'slug'  => 'repeat-buyer',
                'color' => '#2ecc71',
                'type'  => 'customer',
                'rules' => [[
                    'label'      => '3 or more completed orders',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'customer_order_count', 'operator' => 'gte', 'value' => 3],
                    ],
                ]],
            ],

            [
                'name'  => 'At Risk',
                'slug'  => 'at-risk',
                'color' => '#e67e22',
                'type'  => 'customer',
                'rules' => [[
                    'label'      => 'No order in 90+ days',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'days_since_last_order',  'operator' => 'gt',  'value' => 90],
                        ['field' => 'customer_order_count',   'operator' => 'gte', 'value' => 2],
                    ],
                ]],
            ],

            [
                'name'  => 'New Customer',
                'slug'  => 'new-customer',
                'color' => '#3498db',
                'type'  => 'customer',
                'rules' => [[
                    'label'      => 'Exactly 1 completed order',
                    'trigger'    => 'order_placed',
                    'operator'   => 'AND',
                    'priority'   => 10,
                    'conditions' => [
                        ['field' => 'customer_order_count', 'operator' => 'eq', 'value' => 1],
                    ],
                ]],
            ],

        ];
    }
}
