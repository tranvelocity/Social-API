<?php

declare(strict_types=1);

namespace Modules\Role\app\Entities;

use Illuminate\Support\Facades\Config;
use Modules\Core\app\Constants\StatusCodeConstant;
use Modules\Core\app\Exceptions\FatalErrorException;

class Role
{
    private int $role;

    public function __construct(int $role)
    {
        $this->role = $role;
    }

    public function getRole(): int
    {
        return $this->role;
    }

    /**
     * Get the name of the role based on the role ID.
     *
     * @param int $role The role ID.
     * @return string The name of the role.
     * @throws FatalErrorException If the role ID is unexpected.
     */
    public static function getRoleName(int $role): string
    {
        return match ($role) {
            self::administrator()->getRole() => Config::get('role.administrator'),
            self::poster()->getRole() => Config::get('role.poster'),
            self::paidMember()->getRole() => Config::get('role.paid_membership'),
            self::freeMember()->getRole() => Config::get('role.free_membership'),
            self::nonRegisteredUser()->getRole() => Config::get('role.non_registered_user'),
            default => throw new FatalErrorException(StatusCodeConstant::INTERNAL_SERVER_ERROR_DEFAULT_CODE, 'Unexpected role ID: ' . $role)
        };
    }

    public static function administrator(): self
    {
        return new self(Config::get('role.roles.administrator'));
    }

    public static function poster(): self
    {
        return new self(Config::get('role.roles.poster'));
    }

    public static function paidMember(): self
    {
        return new self(Config::get('role.roles.paid_member'));
    }

    public static function freeMember(): self
    {
        return new self(Config::get('role.roles.free_member'));
    }

    public static function nonRegisteredUser(): self
    {
        return new self(Config::get('role.roles.non_registered_user'));
    }
}
