<?php

namespace NHSEngland\PagePermissions;

use \Wa72\HtmlPageDom\HtmlPageCrawler;

class UI implements \Dxw\Iguana\Registerable
{
    private $user;
    private $permissions;
    private $settings;

    public function __construct(\NHSEngland\PagePermissions\User $user, \NHSEngland\PagePermissions\Permissions $permissions, \NHSEngland\PagePermissions\Settings $settings)
    {
        $this->user = $user;
        $this->permissions = $permissions;
        $this->settings = $settings;
    }

    public function register()
    {
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_filter('wp_dropdown_pages', [$this, 'filterPageDropdown'], 10, 3);
    }

    public function addMetaBox()
    {
        add_meta_box($this->settings->pluginID(), $this->settings->pluginName(), [$this, 'metaBoxOutput'], 'page', 'side');
    }

    public function metaBoxOutput()
    {
        global $post;
        $disableControls = false;
        if ($post->post_parent) {
            echo 'This feature is only available on top-level pages.';
            return;
        }
        if (!current_user_can('edit_users')) {
            $disableControls = true;
        }
        $users = $this->user->getAllPotentialPageEditors();
        $permissions = $this->permissions->getByPage($post->ID);
        $html = $this->generateHTML($disableControls, $users, $permissions);
        echo $html;
    }

    public function filterPageDropdown(string $output, array $args, array $pages) : string
    {
        if (current_user_can('edit_users')) {
            return $output;
        }
        $screen = get_current_screen();
        if ($screen->post_type !== 'page' || $screen->parent_base !== 'edit') {
            return $output;
        }
        $output = $this->filterDropdownOutput($output, $args, $pages);
        return $output;
    }

    private function generateHTML(bool $disableControls, array $users, array $permissions) : string
    {
        $html = '<input name="' . $this->settings->pluginID() . '_form_exists" type="hidden" value="true">';
        $html .= '<ol>';
        foreach ($users as $user) {
            $html .= '<li><label>';
            $html .= '<input name="' . $this->settings->pluginID() . '[' . $user->ID . ']" type="checkbox"';
            $html .= isset($permissions[$user->ID]) ? ' checked' : '';
            $html .= $disableControls ? ' disabled' : '';
            $html .= '>' . $user->data->user_login;
            $html .= '</label></li>';
        }
        $html .= '</ol>';
        return $html;
    }

    /* Note: HtmlPageCrawler converts "selected='selected'" to just "selected"
    */
    private function filterDropdownOutput(string $output, array $args, array $pages) : string
    {
        $html = HtmlPageCrawler::create($output);
        $userID = get_current_user_id();
        foreach ($pages as $page) {
            if (!$this->user->canAccessChildrenOf($userID, $page->ID)) {
                $thisOption = $html->filter('option[value="' . $page->ID . '"]');
                $thisOption->remove();
            }
        }
        $noParentOption = $html->filter('option[value=""]');
        $noParentOption->remove();
        return $html->saveHtml();
    }
}
