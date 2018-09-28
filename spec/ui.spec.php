<?php

describe(\NHSEngland\PagePermissions\UI::class, function () {
    beforeEach(function () {
        \WP_Mock::setUp();
        $this->user = Mockery::mock(\NHSEngland\PagePermissions\User::class);
        $this->permissions = Mockery::mock(\NHSEngland\PagePermissions\Permissions::class);
        $this->settings = Mockery::mock(\NHSEngland\PagePermissions\Settings::class);
        $this->settings->shouldReceive('pluginName')
            ->andReturn('NHS Page Permissions');
        $this->settings->shouldReceive('pluginID')
            ->andReturn('nhs_page_permissions');
        $this->settings->shouldReceive('metakey')
            ->andReturn('_nhs_page_permissions');
        $this->ui = new NHSEngland\PagePermissions\UI($this->user, $this->permissions, $this->settings);
    });

    afterEach(function () {
        \WP_Mock::tearDown();
    });

    it('is registerable', function () {
        expect($this->ui)->to->be->instanceof(\Dxw\Iguana\Registerable::class);
    });

    describe('->register()', function () {
        it('adds the action and filter', function () {
            WP_Mock::expectActionAdded('add_meta_boxes', [$this->ui, 'addMetaBox']);
            WP_Mock::expectFilterAdded('wp_dropdown_pages', [$this->ui, 'filterPageDropdown'], 10, 3);
            $this->ui->register();
        });
    });

    describe('->addMetaBox()', function () {
        it('adds the meta box', function () {
            WP_Mock::wpFunction('add_meta_box', [
                'times' => 1,
                'args' => [
                    'nhs_page_permissions',
                    'NHS Page Permissions',
                    [$this->ui, 'metaBoxOutput'],
                    'page',
                    'side'
                ]
            ]);
            $this->ui->addMetaBox();
        });
    });

    describe('->metaBoxOutput()', function () {
        context('page has parent', function () {
            it('outputs top-level only message', function () {
                global $post;
                $post = (object) [
                    'post_parent' => 23
                ];
                ob_start();
                $this->ui->metaBoxOutput();
                $result = ob_get_clean();
                expect($result)->to->equal('This feature is only available on top-level pages.');
            });
        });
        context('page has no parent', function () {
            beforeEach(function () {
                global $post;
                $post = (object) [
                    'ID' => 123,
                    'post_parent' => 0
                ];
                $user1 = (object) [
                    'ID' => 45,
                    'data' => (object) [
                        'user_login' => 'foo'
                    ]
                ];
                $user2 = (object) [
                    'ID' => 67,
                    'data' => (object) [
                        'user_login' => 'bar'
                    ]
                ];
                $user3 = (object) [
                    'ID' => 89,
                    'data' => (object) [
                        'user_login' => 'gem'
                    ]
                ];
                $this->user->shouldReceive('getAllPotentialPageEditors')
                    ->once()
                    ->andReturn(
                        [
                            $user1,
                            $user2,
                            $user3
                        ]
                    );
                $this->permissions->shouldReceive('getByPage')
                    ->once()
                    ->with(123)
                    ->andReturn(
                        [
                            45 => true,
                            89 => true
                        ]
                    );
            });
            context('current user cannot edit_users', function () {
                it('outputs box with disabled controls', function () {
                    WP_Mock::wpFunction('current_user_can', [
                        'times' => 1,
                        'args' => 'edit_users',
                        'return' => false
                    ]);
                    ob_start();
                    $this->ui->metaBoxOutput();
                    $result = ob_get_clean();
                    expect($result)->to->equal('<input name="nhs_page_permissions_form_exists" type="hidden" value="true"><ol><li><label><input name="nhs_page_permissions[45]" type="checkbox" checked disabled>foo</label></li><li><label><input name="nhs_page_permissions[67]" type="checkbox" disabled>bar</label></li><li><label><input name="nhs_page_permissions[89]" type="checkbox" checked disabled>gem</label></li></ol>');
                });
            });
            context('current user can edit_users', function () {
                it('outputs box with enabled controls', function () {
                    WP_Mock::wpFunction('current_user_can', [
                        'times' => 1,
                        'args' => 'edit_users',
                        'return' => true
                    ]);
                    ob_start();
                    $this->ui->metaBoxOutput();
                    $result = ob_get_clean();
                    expect($result)->to->equal('<input name="nhs_page_permissions_form_exists" type="hidden" value="true"><ol><li><label><input name="nhs_page_permissions[45]" type="checkbox" checked>foo</label></li><li><label><input name="nhs_page_permissions[67]" type="checkbox">bar</label></li><li><label><input name="nhs_page_permissions[89]" type="checkbox" checked>gem</label></li></ol>');
                });
            });
        });
    });

    describe('filterPageDropdown()', function () {
        context('user is admin', function () {
            it('returns the output unamended', function () {
                WP_Mock::wpFunction('current_user_can', [
                    'times' => 1,
                    'args' => 'edit_users',
                    'return' => true
                ]);
                $this->output = '<select name="parent_id" id="parent_id"><option value="">(no parent)</option><option class="level-0" value="102035" selected="selected">About NHS Englandx</option><option class="level-1" value="103760">&nbsp;&nbsp;&nbsp;test perm</option><option class="level-2" value="103762">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;test perm child</option><option class="level-3" value="103764">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;child child</option><option class="level-4" value="103802">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;sds</option></select>';
                $result = $this->ui->filterPageDropdown($this->output, [], []);
                expect($result)->to->equal($this->output);
            });
        });
        context('user is not admin', function () {
            beforeEach(function () {
                WP_Mock::wpFunction('current_user_can', [
                    'times' => 1,
                    'args' => 'edit_users',
                    'return' => false
                ]);
                $this->output = '<select name="parent_id" id="parent_id"><option value="">(no parent)</option><option class="level-0" value="123" selected="selected">About NHS Englandx</option><option class="level-1" value="456">&nbsp;&nbsp;&nbsp;test perm</option><option class="level-2" value="789">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;test perm child</option></select>';
            });
            context('editing a non-page post type', function () {
                it('returns output unamended', function () {
                    $screen = (object) [
                        'parent_base' => 'edit',
                        'post_type' => 'post'
                    ];
                    WP_Mock::wpFunction('get_current_screen', [
                        'times' => 1,
                        'args' => null,
                        'return' => $screen
                    ]);
                    $result = $this->ui->filterPageDropdown($this->output, [], []);
                    expect($result)->to->equal($this->output);
                });
            });
            context('not edit screen', function () {
                it('returns output unamended', function () {
                    $screen = (object) [
                        'parent_base' => 'notEdit',
                        'post_type' => 'page'
                    ];
                    WP_Mock::wpFunction('get_current_screen', [
                        'times' => 1,
                        'args' => null,
                        'return' => $screen
                    ]);
                    $result = $this->ui->filterPageDropdown($this->output, [], []);
                    expect($result)->to->equal($this->output);
                });
            });
            context('editing a page', function () {
                beforeEach(function () {
                    WP_Mock::wpFunction('get_current_screen', [
                        'times' => 1,
                        'args' => null,
                        'return' => (object) [
                            'parent_base' => 'edit',
                            'post_type' => 'page'
                        ]
                    ]);
                });
                context('user has access to all pages in dropdown', function () {
                    it('just removes no parent option', function () {
                        $this->pages = [
                            (object) [
                                'ID' => 123
                            ],
                            (object) [
                                'ID' => 456
                            ],
                            (object) [
                                'ID' => 789
                            ]
                        ];
                        WP_Mock::wpFunction('get_current_user_id', [
                            'times' => 1,
                            'args' => null,
                            'return' => 999
                        ]);
                        $this->user->shouldReceive('canAccessChildrenOf')
                            ->once()
                            ->with(999, 123)
                            ->andReturn(true);
                        $this->user->shouldReceive('canAccessChildrenOf')
                            ->once()
                            ->with(999, 456)
                            ->andReturn(true);
                        $this->user->shouldReceive('canAccessChildrenOf')
                            ->once()
                            ->with(999, 789)
                            ->andReturn(true);
                        $result = $this->ui->filterPageDropdown($this->output, [], $this->pages);
                        expect($result)->to->equal('<select name="parent_id" id="parent_id"><option class="level-0" value="123" selected>About NHS Englandx</option><option class="level-1" value="456">&nbsp;&nbsp;&nbsp;test perm</option><option class="level-2" value="789">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;test perm child</option></select>');
                    });
                });
                context('user has access to only some pages in dropdown', function () {
                    it('removes them and no parent option', function () {
                        $this->pages = [
                            (object) [
                                'ID' => 123
                            ],
                            (object) [
                                'ID' => 456
                            ],
                            (object) [
                                'ID' => 789
                            ]
                        ];
                        WP_Mock::wpFunction('get_current_user_id', [
                            'times' => 1,
                            'args' => null,
                            'return' => 999
                        ]);
                        $this->user->shouldReceive('canAccessChildrenOf')
                            ->once()
                            ->with(999, 123)
                            ->andReturn(false);
                        $this->user->shouldReceive('canAccessChildrenOf')
                            ->once()
                            ->with(999, 456)
                            ->andReturn(false);
                        $this->user->shouldReceive('canAccessChildrenOf')
                            ->once()
                            ->with(999, 789)
                            ->andReturn(true);
                        $result = $this->ui->filterPageDropdown($this->output, [], $this->pages);
                        expect($result)->to->equal('<select name="parent_id" id="parent_id"><option class="level-2" value="789">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;test perm child</option></select>');
                    });
                });
            });
        });
    });
});
