<?php

describe(\NHSEngland\PagePermissions\User::class, function () {
    beforeEach(function () {
        \WP_Mock::setUp();
        $this->settings = Mockery::mock(\NHSEngland\PagePermissions\Settings::class);
        $this->settings->shouldReceive('pluginName')
            ->andReturn('NHS Page Permissions');
        $this->settings->shouldReceive('pluginID')
            ->andReturn('nhs_page_permissions');
        $this->settings->shouldReceive('metakey')
            ->andReturn('_nhs_page_permissions');
        $this->user = new NHSEngland\PagePermissions\User($this->settings);
    });

    afterEach(function () {
        \WP_Mock::tearDown();
    });

    describe('->isAdmin()', function () {
        context('user has edit_users capability', function () {
            it('returns true', function () {
                $allCaps = [
                    'edit_users' => true,
                    'foo' => 'bar'
                ];
                $result = $this->user->isAdmin($allCaps);
                expect($result)->to->equal(true);
            });
        });
        context('user does not have edit_users capability', function () {
            it('returns false', function () {
                $allCaps = [
                    'foo' => 'bar'
                ];
                $result = $this->user->isAdmin($allCaps);
                expect($result)->to->equal(false);
            });
        });
    });

    describe('->canAccessPage()', function () {
        context('page is at top level', function () {
            it('returns false', function () {
                WP_Mock::wpFunction('get_post_ancestors', [
                    'times' => 1,
                    'args' => 123,
                    'return' => []
                ]);
                $userID = 99;
                $pageID = 123;
                $result = $this->user->canAccessPage($userID, $pageID);
                expect($result)->to->equal(false);
            });
        });
        context('page has ancestors', function () {
            context('but user does not have access to top ancestor', function () {
                it('returns false', function () {
                    WP_Mock::wpFunction('get_post_ancestors', [
                        'times' => 2,
                        'args' => 123,
                        'return' => [
                            456,
                            789
                        ]
                    ]);
                    WP_Mock::wpFunction('get_post_meta', [
                        'times' => 1,
                        'args' => [
                            789,
                            '_nhs_page_permissions',
                            true
                        ],
                        'return' => [
                            75 => true,
                            98 => true
                        ]
                    ]);
                    $userID = 99;
                    $pageID = 123;
                    $result = $this->user->canAccessPage($userID, $pageID);
                    expect($result)->to->equal(false);
                });
            });
            context('and user does have access to top ancestor', function () {
                it('returns true', function () {
                    WP_Mock::wpFunction('get_post_ancestors', [
                        'times' => 2,
                        'args' => 123,
                        'return' => [
                            456,
                            789
                        ]
                    ]);
                    WP_Mock::wpFunction('get_post_meta', [
                        'times' => 1,
                        'args' => [
                            789,
                            '_nhs_page_permissions',
                            true
                        ],
                        'return' => [
                            75 => true,
                            99 => true
                        ]
                    ]);
                    $userID = 99;
                    $pageID = 123;
                    $result = $this->user->canAccessPage($userID, $pageID);
                    expect($result)->to->equal(true);
                });
            });
        });
    });

    describe('->canAccessChildrenOf()', function () {
        context('user has access to an ancestor of this page ID', function () {
            it('returns true', function () {
                WP_Mock::wpFunction('get_post_ancestors', [
                    'times' => 2,
                    'args' => 123,
                    'return' => [
                        456,
                        789
                    ]
                ]);
                WP_Mock::wpFunction('get_post_meta', [
                    'times' => 1,
                    'args' => [
                        789,
                        '_nhs_page_permissions',
                        true
                    ],
                    'return' => [
                        67 => true
                    ]
                ]);
                $result = $this->user->canAccessChildrenOf(67, 123);
                expect($result)->to->equal(true);
            });
        });
        context('user does not have access to an ancestor of this page ID', function () {
            it('returns false', function () {
                WP_Mock::wpFunction('get_post_ancestors', [
                    'times' => 3,
                    'args' => 123,
                    'return' => [
                        456,
                        789
                    ]
                ]);
                WP_Mock::wpFunction('get_post_meta', [
                    'times' => 1,
                    'args' => [
                        789,
                        '_nhs_page_permissions',
                        true
                    ],
                    'return' => ''
                ]);
                $result = $this->user->canAccessChildrenOf(67, 123);
                expect($result)->to->equal(false);
            });
        });
        context('top level page with user granted permission to children', function () {
            it('returns true', function () {
                WP_Mock::wpFunction('get_post_ancestors', [
                    'times' => 2,
                    'args' => 123,
                    'return' => []
                ]);
                WP_Mock::wpFunction('get_post_meta', [
                    'times' => 1,
                    'args' => [
                        123,
                        '_nhs_page_permissions',
                        true
                    ],
                    'return' => [
                        67 => true
                    ]
                ]);
                $result = $this->user->canAccessChildrenOf(67, 123);
                expect($result)->to->equal(true);
            });
        });
        context('top level page with user not granted permission to children', function () {
            it('returns false', function () {
                WP_Mock::wpFunction('get_post_ancestors', [
                    'times' => 2,
                    'args' => 123,
                    'return' => []
                ]);
                WP_Mock::wpFunction('get_post_meta', [
                    'times' => 1,
                    'args' => [
                        123,
                        '_nhs_page_permissions',
                        true
                    ],
                    'return' => ''
                ]);
                $result = $this->user->canAccessChildrenOf(67, 123);
                expect($result)->to->equal(false);
            });
        });
    });

    describe('->getAllPotentialPageEditors()', function () {
        it('returns all users with edit_pages cap but not edit_users', function () {
            $user1 = Mockery::mock(WP_User::class);
            $user1->id = 12;
            $user1->allcaps = [
                'edit_pages' => true
            ];
            $user2 = Mockery::mock(WP_User::class);
            $user2->id = 34;
            $user2->allcaps = [
                'edit_pages' => true,
                'edit_users' => true
            ];
            $user3 = Mockery::mock(WP_User::class);
            $user3->id = 56;
            $user3->allcaps = [
                'edit_pages' => true,
            ];
            WP_Mock::wpFunction('get_users', [
                'times' => 1,
                'args' => null,
                'return' => [
                    $user1,
                    $user2,
                    $user3
                ]
            ]);
            $result = $this->user->getAllPotentialPageEditors();
            expect($result)->to->equal([$user1, $user3]);
        });
    });
});
