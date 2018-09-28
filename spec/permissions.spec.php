<?php

describe(\NHSEngland\PagePermissions\Permissions::class, function () {
    beforeEach(function () {
        \WP_Mock::setUp();
        $this->editorTypeCaps = [
            'edit_pages' => true,
            'edit_page' => true,
            'edit_others_pages' => true,
            'edit_published_pages' => true,
            'edit_private_pages' => true,

            'delete_page' => true,
            'delete_pages' => true,
            'delete_others_pages' => true,
            'delete_published_pages' => true,
            'delete_private_pages' => true,
            'foo' => 'bar'
        ];

        $this->trimmedCaps = [
            'foo' => 'bar'
        ];
        $this->adminTypeCaps = [
            'edit_users' => true,
            'edit_pages' => true,
            'edit_page' => true,
            'edit_others_pages' => true,
            'edit_published_pages' => true,
            'edit_private_pages' => true,

            'delete_page' => true,
            'delete_pages' => true,
            'delete_others_pages' => true,
            'delete_published_pages' => true,
            'delete_private_pages' => true,
            'foo' => 'bar'
        ];
        $this->user = Mockery::mock(\NHSEngland\PagePermissions\User::class);
        $this->settings = Mockery::mock(\NHSEngland\PagePermissions\Settings::class);
        $this->settings->shouldReceive('pluginName')
            ->andReturn('NHS Page Permissions');
        $this->settings->shouldReceive('pluginID')
            ->andReturn('nhs_page_permissions');
        $this->settings->shouldReceive('metakey')
            ->andReturn('_nhs_page_permissions');
        $this->permissions = new NHSEngland\PagePermissions\Permissions($this->user, $this->settings);
    });

    afterEach(function () {
        \WP_Mock::tearDown();
    });

    it('is registerable', function () {
        expect($this->permissions)->to->be->instanceof(\Dxw\Iguana\Registerable::class);
    });

    describe('->register()', function () {
        it('adds the filter and action', function () {
            WP_Mock::expectFilterAdded('user_has_cap', [$this->permissions, 'checkPermissions'], 10, 3);
            WP_Mock::expectActionAdded('edit_post', [$this->permissions, 'updateByPage'], 10, 2);
            $this->permissions->register();
        });
    });

    describe('->checkPermissions()', function () {
        context('page ID not specified', function () {
            it('returns all caps unamended', function () {
                $allCaps = $this->editorTypeCaps;
                $result = $this->permissions->checkPermissions($allCaps, ['requiredCap'], [
                    'edit_post',
                    123,
                    null
                ]);
                expect($result)->to->equal($allCaps);
            });
        });
        context('user ID not specified', function () {
            it('returns all caps unamended', function () {
                $allCaps = $this->editorTypeCaps;
                $result = $this->permissions->checkPermissions($allCaps, ['requiredCap'], [
                    'edit_post',
                    null,
                    456
                ]);
                expect($result)->to->equal($allCaps);
            });
        });
        context('user is admin', function () {
            it('returns all caps unamended', function () {
                $allCaps = $this->adminTypeCaps;
                $this->user->shouldReceive('isAdmin')
                    ->once()
                    ->with($allCaps)
                    ->andReturn(true);
                $result = $this->permissions->checkPermissions($allCaps, ['requiredCap'], [
                    'edit_page',
                    123,
                    456
                ]);
                expect($result)->to->equal($allCaps);
            });
        });
        context('user is not admin', function () {
            beforeEach(function () {
                $this->allCaps = $this->editorTypeCaps;
                $this->user->shouldReceive('isAdmin')
                    ->once()
                    ->with($this->allCaps)
                    ->andReturn(false);
            });
            context('saving a page', function () {
                beforeEach(function () {
                    global $_POST;
                    $_POST = [];
                    $_POST['save'] = true;
                });
                context('no parent set', function () {
                    it('removes all page caps', function () {
                        $_POST['parent_id'] = '';
                        $result = $this->permissions->checkPermissions($this->allCaps, ['requiredCap'], [
                            'edit_page',
                            123,
                            456
                        ]);
                        expect($result)->to->equal($this->trimmedCaps);
                    });
                });
                context('parent set', function () {
                    beforeEach(function () {
                        $_POST['parent_id'] = 789;
                    });
                    context('but cannot access the parent', function () {
                        it('remove all page caps', function () {
                            $this->user->shouldReceive('canAccessChildrenOf')
                                ->once()
                                ->with(123, 789)
                                ->andReturn(false);
                            $result = $this->permissions->checkPermissions($this->allCaps, ['requiredCap'], [
                                'edit_page',
                                123,
                                456
                            ]);
                            expect($result)->to->equal($this->trimmedCaps);
                        });
                    });
                    context('and can access the parent', function () {
                        it('returns all caps unamended', function () {
                            $this->user->shouldReceive('canAccessChildrenOf')
                                ->once()
                                ->with(123, 789)
                                ->andReturn(true);
                            $result = $this->permissions->checkPermissions($this->allCaps, ['requiredCap'], [
                                'edit_page',
                                123,
                                456
                            ]);
                            expect($result)->to->equal($this->editorTypeCaps);
                        });
                    });
                });
            });
            context('publishing a page', function () {
                beforeEach(function () {
                    global $_POST;
                    $_POST = [];
                    $_POST['publish'] = true;
                });
                context('no parent set', function () {
                    it('removes all page caps', function () {
                        $_POST['parent_id'] = '';
                        $result = $this->permissions->checkPermissions($this->allCaps, ['requiredCap'], [
                            'edit_page',
                            123,
                            456
                        ]);
                        expect($result)->to->equal($this->trimmedCaps);
                    });
                });
                context('parent set', function () {
                    beforeEach(function () {
                        $_POST['parent_id'] = 789;
                    });
                    context('but cannot access the parent', function () {
                        it('remove all page caps', function () {
                            $this->user->shouldReceive('canAccessChildrenOf')
                                ->once()
                                ->with(123, 789)
                                ->andReturn(false);
                            $result = $this->permissions->checkPermissions($this->allCaps, ['requiredCap'], [
                                'edit_page',
                                123,
                                456
                            ]);
                            expect($result)->to->equal($this->trimmedCaps);
                        });
                    });
                    context('and can access the parent', function () {
                        it('returns all caps unamended', function () {
                            $this->user->shouldReceive('canAccessChildrenOf')
                                ->once()
                                ->with(123, 789)
                                ->andReturn(true);
                            $result = $this->permissions->checkPermissions($this->allCaps, ['requiredCap'], [
                                'edit_page',
                                123,
                                456
                            ]);
                            expect($result)->to->equal($this->editorTypeCaps);
                        });
                    });
                });
            });
            context('not saving or publishing a page', function () {
                beforeEach(function () {
                    global $_POST;
                    $_POST = [];
                });
                context('user has access to this page ID', function () {
                    it('returns all caps unamended', function () {
                        $this->user->shouldReceive('canAccessPage')
                            ->once()
                            ->with(123, 456)
                            ->andReturn(true);
                        $result = $this->permissions->checkPermissions($this->allCaps, ['requiredCap'], [
                            'edit_page',
                            123,
                            456
                        ]);
                        expect($result)->to->equal($this->editorTypeCaps);
                    });
                });
                context('user does not have access to this page ID', function () {
                    it('remove all page caps', function () {
                        $this->user->shouldReceive('canAccessPage')
                            ->once()
                            ->with(123, 456)
                            ->andReturn(false);
                        $result = $this->permissions->checkPermissions($this->allCaps, ['requiredCap'], [
                            'edit_page',
                            123,
                            456
                        ]);
                        expect($result)->to->equal($this->trimmedCaps);
                    });
                });
            });
        });
    });

    describe('->getByPage()', function () {
        context('post_meta is empty', function () {
            it('returns an empty array', function () {
                WP_Mock::wpFunction('get_post_meta', [
                    'times' => 1,
                    'args' => [
                        123,
                        '_nhs_page_permissions',
                        true
                    ],
                    'return' => ''
                ]);
                $result = $this->permissions->getByPage(123);
                expect($result)->to->equal([]);
            });
        });
        context('post_meta is populated', function () {
            it('returns the value', function () {
                WP_Mock::wpFunction('get_post_meta', [
                    'times' => 1,
                    'args' => [
                        123,
                        '_nhs_page_permissions',
                        true
                    ],
                    'return' => [
                        45 => true,
                        89 => true
                    ]
                ]);
                $result = $this->permissions->getByPage(123);
                expect($result)->to->equal([
                    45 => true,
                    89 => true
                ]);
            });
        });
    });

    describe('->updateByPage()', function () {
        beforeEach(function () {
            $this->postID = 123;
            $this->post = Mockery::mock(WP_Post::class);
        });
        context('is not a page', function () {
            it('does nothing', function () {
                $this->post->post_type = 'post';
                WP_Mock::wpFunction('update_post_meta', [
                    'times' => 0
                ]);
                $this->permissions->updateByPage($this->postID, $this->post);
            });
        });
        context('is a page', function () {
            beforeEach(function () {
                $this->post->post_type = 'page';
            });
            context('user cannot edit_users', function () {
                it('does nothing', function () {
                    WP_Mock::wpFunction('current_user_can', [
                        'times' => 1,
                        'args' => 'edit_users',
                        'return' => false
                    ]);
                    WP_Mock::wpFunction('update_post_meta', [
                        'times' => 0
                    ]);
                    $this->permissions->updateByPage($this->postID, $this->post);
                });
            });
            context('user can edit_users', function () {
                beforeEach(function () {
                    WP_Mock::wpFunction('current_user_can', [
                        'times' => 1,
                        'args' => 'edit_users',
                        'return' => true
                    ]);
                });
                context('nhs_page_permissions_form_exists not set', function () {
                    it('does nothing', function () {
                        global $_POST;
                        $_POST = [];
                        $_POST['nhs_page_permissions'] = [
                            'some' => 'values',
                            'that' => 'should',
                            'not' => 'save'
                        ];
                        WP_Mock::wpFunction('update_post_meta', [
                            'times' => 0
                        ]);
                        $this->permissions->updateByPage($this->postID, $this->post);
                    });
                });
                context('nhs_page_permissions_form_exists is set', function () {
                    context('nhs_page_permissions is not set', function () {
                        it('does nothing', function () {
                            global $_POST;
                            $_POST = [];
                            $_POST['nhs_page_permissions_form_exists'] = true;
                            WP_Mock::wpFunction('update_post_meta', [
                                'times' => 0
                            ]);
                            $this->permissions->updateByPage($this->postID, $this->post);
                        });
                    });
                    context('nhs_page_permissions is set', function () {
                        it('updates the post meta', function () {
                            global $_POST;
                            $_POST = [];
                            $_POST['nhs_page_permissions_form_exists'] = true;
                            $_POST['nhs_page_permissions'] = [
                                45 => 'foo',
                                89 => 'bar'
                            ];
                            WP_Mock::wpFunction('update_post_meta', [
                                'times' => 1,
                                'args' => [
                                    $this->postID,
                                    '_nhs_page_permissions',
                                    [
                                        45 => true,
                                        89 => true
                                    ]
                                ]
                            ]);
                            $this->permissions->updateByPage($this->postID, $this->post);
                        });
                    });
                });
            });
        });
    });
});
