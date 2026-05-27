<?php
/**
 * Customer profile section: show applied customer tags on the WP user edit screen.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Admin;

if (!defined('ABSPATH')) exit;

class CustomerTagsProfile
{
    public function register(): void
    {
        add_action('show_user_profile',  [$this, 'renderSection']);
        add_action('edit_user_profile',  [$this, 'renderSection']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        if (!in_array($hook, ['profile.php', 'user-edit.php'], true)) {
            return;
        }
        wp_enqueue_style(
            'harper-tagger-admin',
            WC_TAGGER_URL . 'admin/css/tagger-admin.css',
            [],
            WC_TAGGER_VERSION
        );
    }

    /**
     * Render the tag section on the user profile/edit page.
     *
     * @param \WP_User $user
     */
    public function renderSection(\WP_User $user): void
    {
        $customerId = $user->ID;
        $tags       = $customerId > 0 ? $this->getCustomerTags($customerId) : [];

        require WC_TAGGER_DIR . 'admin/views/customer-tags-profile.php';
    }

    /**
     * Fetch applied customer tags for a WP user.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCustomerTags(int $customerId): array
    {
        global $wpdb;
        $ctTable   = $wpdb->prefix . 'harper_tagger_customer_tags';
        $tagsTable = $wpdb->prefix . 'harper_tagger_tags';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.id, t.name, t.color, t.image_url
                   FROM {$ctTable} ct
                   JOIN {$tagsTable} t ON t.id = ct.tag_id
                  WHERE ct.customer_id = %d
                  ORDER BY t.name ASC",
                $customerId
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }
}
