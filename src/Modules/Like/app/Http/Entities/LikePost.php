<?php

namespace Modules\Like\app\Http\Entities;

class LikePost
{
    private string $postId;
    private int $userId;
    private string $action;
    private int $totalLikes;
    public const ACTION_LIKED = 'liked';
    public const ACTION_UNLIKED = 'unliked';

    public function __construct(string $postId, int $userId, string $action = self::ACTION_LIKED, int $totalLikes = 0)
    {
        $this->postId = $postId;
        $this->userId = $userId;
        $this->action = $action;
        $this->totalLikes = $totalLikes;
    }

    public function getPostId(): string
    {
        return $this->postId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getTotalLikes(): int
    {
        return $this->totalLikes;
    }
}
