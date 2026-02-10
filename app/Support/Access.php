<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Access
{
    public static function currentUserId(): ?int
    {
        $id = session('usr_id');
        return $id ? (int) $id : null;
    }

    public static function currentRole(): ?string
    {
        return session('usr_role');
    }

    public static function isAuthenticated(): bool
    {
        return self::currentUserId() !== null;
    }

    public static function hasRole(array $roles): bool
    {
        $role = self::currentRole();
        return $role && in_array($role, $roles, true);
    }

    public static function currentUserBarangayId(): ?int
    {
        $userId = self::currentUserId();
        if (!$userId) {
            return null;
        }

        $userBarangayId = DB::table('users')
            ->where('id', $userId)
            ->value('barangay_id');
        if ($userBarangayId) {
            return (int) $userBarangayId;
        }

        $barangayId = DB::table('residents')
            ->where('user_id', $userId)
            ->value('barangay_id');

        return $barangayId ? (int) $barangayId : null;
    }

    public static function userInSameBarangay(int $targetUserId): bool
    {
        $actorBarangayId = self::currentUserBarangayId();
        if (!$actorBarangayId) {
            return false;
        }

        $targetBarangayId = DB::table('residents')
            ->where('user_id', $targetUserId)
            ->value('barangay_id');

        return $targetBarangayId && (int) $targetBarangayId === (int) $actorBarangayId;
    }
}
