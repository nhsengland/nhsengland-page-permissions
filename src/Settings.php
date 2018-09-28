<?php

namespace NHSEngland\PagePermissions;

class Settings
{
    const PLUGIN_NAME = 'NHS Page Permissions';
    const PLUGIN_ID = 'nhs_page_permissions';
    const METAKEY = '_nhs_page_permissions';

    public function pluginName() : string
    {
        return self::PLUGIN_NAME;
    }

    public function pluginID() : string
    {
        return self::PLUGIN_ID;
    }

    public function metakey() : string
    {
        return self::METAKEY;
    }
}
