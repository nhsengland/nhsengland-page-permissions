# NHS England page permissions

A WordPress plugin used on the NHS England website to manage edit access to child pages for non-admins.

When installed and activated, the plugin defaults to preventing all non-admins from editing pages. Admins can select users to grant permission to via an interface on top-level pages.

Once a user has been granted permission, they are able to edit the children of that top level page, and add pages as children of that top-level page. They are not able to edit the top-level page itself.

## Installing the plugin 

Clone this repo into your WordPress `plugins/` directory, and activate the plugin.

## Development

Install the dependencies: `composer install`

Run the unit tests: `vendor/bin/peridot spec`

Run the linter: `vendor/bin/php-cs-fixer fix`