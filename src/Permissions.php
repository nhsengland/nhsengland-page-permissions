<?php

namespace NHSEngland\PagePermissions;

class Permissions implements \Dxw\Iguana\Registerable
{
    private $user;
    private $settings;

    public function __construct(\NHSEngland\PagePermissions\User $user, \NHSEngland\PagePermissions\Settings $settings)
    {
        $this->user = $user;
        $this->settings = $settings;
    }

    public function register()
    {
        add_filter('user_has_cap', [$this, 'checkPermissions'], 10, 3);
        add_action('edit_post', [$this, 'updateByPage'], 10, 2);
    }

    public function checkPermissions(array $allCaps, array $cap, array $args) : array
    {
        $userID = (int)$args[1];
        $pageID = (int)$args[2];

        if ($userID <= 0 || $pageID <= 0) {
            return $allCaps;
        }

        if ($this->user->isAdmin($allCaps)) {
            return $allCaps;
        }

        if (isset($_POST['save']) || isset($_POST['publish'])) {
            if ((int) $_POST['parent_id']) {
                if ($this->user->canAccessChildrenOf($userID, (int) $_POST['parent_id'])) {
                    return $allCaps;
                }
            }
            return $this->removePageCaps($allCaps);
        }

        if ($this->user->canAccessPage($userID, $pageID)) {
            return $allCaps;
        }
        return $this->removePageCaps($allCaps);
    }

    public function getByPage(int $pageID) : array
    {
        $permissions = get_post_meta($pageID, $this->settings->metakey(), true);
        if ($permissions == '') {
            return [];
        }
        return $permissions;
    }

    public function updateByPage(int $postID, \WP_Post $post)
    {
        if ($post->post_type !== 'page') {
            return;
        }
        if (!current_user_can('edit_users')) {
            return;
        }

        if (!isset($_POST[$this->settings->pluginID() . '_form_exists'])) {
            return;
        }
        if (isset($_POST[$this->settings->pluginID()])) {
            $permissions = [];
            foreach ($_POST[$this->settings->pluginID()] as $k=>$v) {
                $permissions[(int)$k] = true;
            }
            update_post_meta($postID, $this->settings->metakey(), $permissions);
        }
    }

    private function removePageCaps(array $allCaps) : array
    {
        unset($allCaps['edit_page']);
        unset($allCaps['edit_pages']);
        unset($allCaps['edit_others_pages']);
        unset($allCaps['edit_published_pages']);
        unset($allCaps['edit_private_pages']);

        unset($allCaps['delete_page']);
        unset($allCaps['delete_pages']);
        unset($allCaps['delete_others_pages']);
        unset($allCaps['delete_published_pages']);
        unset($allCaps['delete_private_pages']);
        return $allCaps;
    }
}
