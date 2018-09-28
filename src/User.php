<?php

namespace NHSEngland\PagePermissions;

class User
{
    private $settings;

    public function __construct(\NHSEngland\PagePermissions\Settings $settings)
    {
        $this->settings = $settings;
    }

    /*
    * We can't use current_user_can because this is getting called from a hook on
    * user_has_cap, so that would cause an infinite loop
    */
    public function isAdmin(array $allCaps) : bool
    {
        if (isset($allCaps['edit_users']) && $allCaps['edit_users'] == true) {
            return true;
        }
        return false;
    }

    public function canAccessPage(int $userID, int $pageID) : bool
    {
        if ($this->isTopLevelPage($pageID)) {
            return false;
        }

        $ancestorPermissions = $this->getPageMeta($this->topLevelAncestor($pageID));

        if (isset($ancestorPermissions[$userID]) && $ancestorPermissions[$userID] === true) {
            return true;
        }

        return false;
    }

    public function canAccessChildrenOf(int $userID, int $pageID) : bool
    {
        if ($this->canAccessPage($userID, $pageID)) {
            return true;
        }

        if ($this->isTopLevelPage($pageID)) {
            $pagePermissions = $this->getPageMeta($pageID);
            if (isset($pagePermissions[$userID]) && $pagePermissions[$userID] === true) {
                return true;
            }
        }

        return false;
    }

    /* Return array of WP_Users who have edit_pages cap but not edit_users */
    public function getAllPotentialPageEditors() : array
    {
        $users = get_users();
        $potentials = [];
        foreach ($users as $user) {
            if (!$this->isAdmin($user->allcaps) && $user->allcaps['edit_pages']) {
                $potentials[] = $user;
            }
        }
        return $potentials;
    }

    private function isTopLevelPage(int $pageID) : bool
    {
        return (get_post_ancestors($pageID) == []);
    }

    private function topLevelAncestor(int $pageID) : int
    {
        $ancestors = get_post_ancestors($pageID);
        $topAncestor = $ancestors[count($ancestors)-1];
        return $topAncestor;
    }

    private function getPageMeta(int $pageID)
    {
        return get_post_meta(
            $pageID,
            $this->settings->metakey(),
            true
        );
    }
}
