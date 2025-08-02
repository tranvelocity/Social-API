<?php

namespace Modules\Session\App\Resources;

use Modules\Core\app\Resources\JsonResource;

class MembershipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $role = $this->resource['role'];
        $membership = $this->resource['member_data'];

        $response = ['role' => $role];

        if ($membership) {
            $response += ['user_id' => $membership->users->user_id,
                'user_id' => $membership->users->user_id,
                'email' => $membership->users->email,
                'last_name' => $membership->users->last_name,
                'first_name' => $membership->users->first_name,
                'last_name_kana' => $membership->users->last_name_kana,
                'first_name_kana' => $membership->users->first_name_kana,
                'nickname' => $membership->users->nickname,
                'avatar' => $membership->users->profile_image,
                'member_no' => $membership->member_no,
                'member_status' => $membership->member_status,
            ];
        }

        return $response;
    }
}
