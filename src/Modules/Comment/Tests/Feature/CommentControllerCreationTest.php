<?php

namespace Modules\Comment\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Modules\Comment\app\Models\Comment;
use Modules\Post\app\Models\Post;
use Modules\Poster\app\Models\Poster;
use Modules\RestrictedUser\app\Models\RestrictedUser;
use Modules\ThrottleConfig\app\Models\ThrottleConfig;

/**
 * @group parallel
 */
class CommentControllerCreationTest extends CommentControllerTestBase
{
    /** @test */
    public function test_201_response_paid_member_create_comment_successfully(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdmin($admin);
        $post = $this->createPostWithAdmin($admin, $poster);

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        $response = $this->postJson("{$this->endpoint}/{$post->id}/comments", ['comment' => $this->faker->text], $this->generateMockSocialApiHeaders($admin));
        $expectedData = $response->json('data');
        $expectedData['nickname'] = $user['nickname'];
        $expectedData['avatar'] = $user['profile_image'];

        $this->assert201SuccessCreationResponse($response, $this->commentResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_201_response_poster_leave_a_comment_successfully_even_comment_contains_ng_word(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        // Mock Prohibited Words
        $ngWordList = NGWordGenerator::generate(100);
        $this->generateMockNGWords($ngWordList);
        $ngWord = $ngWordList[0];

        $response = $this->postJson("{$this->endpoint}/{$post->id}/comments", ['comment' => $this->faker->text . ' ' . $ngWord . '。' . $this->faker->text], $this->generateMockSocialApiHeaders($admin));
        $expectedData = $response->json('data');
        $expectedData['nickname'] = $user['nickname'];
        $expectedData['avatar'] = $user['profile_image'];

        $this->assert201SuccessCreationResponse($response, $this->commentResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_403_response_free_member_create_comment_fails_due_to_access_denied(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $poster = $this->createPosterWithAdmin($admin);
        $post = $this->createPostWithAdmin($admin, $poster);

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMembers([$this->generateMockCrmMember(['member_status' => MemberStatuses::FREE_MEMBER_STATUS])]);

        $response = $this->postJson("{$this->endpoint}/{$post->id}/comments", ['comment' => $this->faker->text], $this->generateMockSocialApiHeaders($admin));

        $this->assert403ForbiddenResponse($response, Lang::get('comment::errors.access_denied_creation_permission'));
    }

    /** @test */
    public function test_404_response_create_comment_fails_due_to_post_not_found(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $this->createPosterWithAdminAndUserId($admin, $userId);
        $postId = 'non-existing-id';

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS]);

        $response = $this->postJson("{$this->endpoint}/{$postId}/comments", ['comment' => $this->faker->text], $this->generateMockSocialApiHeaders($admin));

        $this->assert404NotFoundResponse($response, Lang::get('post::errors.post_item_not_found', ['id' => $postId]));
    }

    /** @test */
    public function test_400_response_premium_member_create_comment_fails_due_to_comment_contains_ng_word(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdmin($admin);
        $post = $this->createPostWithAdmin($admin, $poster);

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        $ngWords = NGWordGenerator::generate(100);
        $this->generateMockNGWords($ngWords);

        $ngWord = $ngWords[0];
        $comment = $this->faker->text . ' ' . $ngWord . '。' . $this->faker->text;
        $response = $this->postJson(
            "{$this->endpoint}/{$post->id}/comments",
            ['comment' => $comment],
            $this->generateMockSocialApiHeaders($admin)
        );

        $expectedError = Lang::get('comment::errors.error_comment_contain_ng_word', ['word' => $ngWord]);
        $this->assert400BadRequestSimpleResponse($response, $expectedError);
    }

    /** @test */
    public function test_400_response_create_comment_fails_due_to_invalid_request_parameter(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdmin($admin);
        $post = $this->createPostWithAdmin($admin, $poster);

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        $response = $this->postJson(
            "{$this->endpoint}/{$post->id}/comments",
            [],
            $this->generateMockSocialApiHeaders($admin)
        );

        $expectedError = [
            'message' => Config::get('tranauth.exceptions.400.400001'),
            'validation' => [
                [
                    'attribute' => 'comment',
                    'errors' => [
                        [
                            'key' => 'Required',
                            'message' => 'required|The comment field is required.',
                        ],
                    ],
                ],
            ],
        ];

        $this->assert400BadRequestValidationFailureResponse($response, $expectedError);
    }

    /** @test */
    public function test_201_response_poster_can_leave_a_comment_contains_ng_word(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdminAndUserId($admin, $userId);
        $post = $this->createPostWithAdmin($admin, $poster);

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        $ngWords = NGWordGenerator::generate(100);
        $this->generateMockNGWords($ngWords);

        $ngWord = $ngWords[0];
        $comment = $this->faker->text . ' ' . $ngWord . '。' . $this->faker->text;
        $response = $this->postJson(
            "{$this->endpoint}/{$post->id}/comments",
            ['comment' => $comment],
            $this->generateMockSocialApiHeaders($admin)
        );

        $expectedData = $response->json('data');
        $expectedData['nickname'] = $user['nickname'];
        $expectedData['avatar'] = $user['profile_image'];

        $this->assert201SuccessCreationResponse($response, $this->commentResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_401_response_non_registered_user_cannot_leave_comment_due_to_unauthorized(): void
    {
        // Mock Admin and User User
        $admin = $this->generateMockAdmin();
        $this->mockUserAuthenticationService($admin);
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $this->generateMockUserUserSession($userUser);

        // Mock CRM User and Member
        $user = $this->generateMockCrmUser(['user_id' => $userId]);
        $this->generateMockCrmUsers([$user]);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);

        // Mock Poster, Post, and Comment
        Poster::factory(['admin_uuid' => $admin->getUuid()])->create();
        $post = Post::factory()->create(['admin_uuid' => $admin->getUuid()]);

        $comment = $this->faker->text;
        $response = $this->postJson(
            "{$this->endpoint}/{$post->id}/comments",
            ['comment' => $comment],
            $this->generateMockSocialApiHeaders($admin, false)
        );

        $this->assert401UnauthorizedResponse($response);
    }

    /** @test */
    public function test_201_response_create_comment_successfully_within_comment_limitation(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdmin($admin);
        $post = $this->createPostWithAdmin($admin, $poster);

        ThrottleConfig::factory()->create(['admin_uuid' => $admin->getUuid()]);

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        $response = $this->postJson("{$this->endpoint}/{$post->id}/comments", ['comment' => $this->faker->text], $this->generateMockSocialApiHeaders($admin));
        $expectedData = $response->json('data');
        $expectedData['nickname'] = $user['nickname'];
        $expectedData['avatar'] = $user['profile_image'];

        $this->assert201SuccessCreationResponse($response, $this->commentResourceAttributes(), $expectedData);
    }

    /** @test */
    public function test_403_response_create_comment_fails_due_to_exceed_comment_limitation(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdmin($admin);
        $post = $this->createPostWithAdmin($admin, $poster);

        $maxCommentLimit = $this->faker->numberBetween(1, 10);
        $timeFrameMinutes = $this->faker->numberBetween(1, 100000);
        ThrottleConfig::factory([
            'admin_uuid' => $admin->getUuid(),
            'time_frame_minutes' => $timeFrameMinutes,
            'max_comments' => $maxCommentLimit,
        ])->create();

        Comment::factory([
            'post_id' => $post->id,
            'user_id' => $userId,
            'created_at' => Carbon::now()->subMinutes($timeFrameMinutes)->format('Y-m-d H:i:s'),
        ])->count($maxCommentLimit + 1)->create();

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        $response = $this->postJson("{$this->endpoint}/{$post->id}/comments", ['comment' => $this->faker->text], $this->generateMockSocialApiHeaders($admin));
        $this->assert403ForbiddenResponse($response, Lang::get('comment::errors.comment_denied_due_to_reached_to_maximum_comment_number'));
    }

    /** @test */
    public function test_403_response_create_comment_fails_due_to_restricted_user(): void
    {
        $admin = $this->generateMockAdmin();
        $userUser = $this->generateMockUserUser();
        $userId = $userUser['user_id'];
        $poster = $this->createPosterWithAdmin($admin);
        $post = $this->createPostWithAdmin($admin, $poster);

        RestrictedUser::factory(['admin_uuid' => $admin->getUuid(), 'user_id' => $userId])->create();

        $this->mockUserAuthenticationService($admin);
        $this->generateMockUserUserSession($userUser);
        $member = $this->generateMockCrmMember(['member_status' => MemberStatuses::REGULAR_MEMBER_STATUS, 'users' => ['user_id' => $userId]]);
        $this->generateMockCrmMembers([$member]);
        $user = $this->generateMockCrmUser(['user_id' => $userUser['user_id']]);
        $this->generateMockCrmUsers([$user]);

        $response = $this->postJson("{$this->endpoint}/{$post->id}/comments", ['comment' => $this->faker->text], $this->generateMockSocialApiHeaders($admin));
        $this->assert403ForbiddenResponse($response, Lang::get('comment::errors.comment_denied_due_to_restricted_user'));
    }
}
