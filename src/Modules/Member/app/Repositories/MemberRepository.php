<?php

namespace Modules\Member\app\Repositories;

use Modules\Member\app\Models\Member;

class MemberRepository
{
    public function all()
    {
        return Member::all();
    }

    public function find($id)
    {
        return Member::find($id);
    }

    public function create(array $data)
    {
        return Member::create($data);
    }

    public function update($id, array $data)
    {
        $member = Member::find($id);
        if ($member) {
            $member->update($data);
        }
        return $member;
    }

    public function delete($id)
    {
        $member = Member::find($id);
        if ($member) {
            $member->delete();
        }
        return $member;
    }
}
