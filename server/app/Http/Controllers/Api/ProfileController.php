<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The signed-in employee's own profile (currently the avatar photo).
 */
class ProfileController extends Controller
{
    /**
     * POST /api/profile/avatar — upload/replace the profile photo.
     */
    public function uploadAvatar(Request $request): EmployeeResource
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $request->validate([
            'photo' => ['required', 'file', 'max:15360'],
        ]);

        $file = $request->file('photo');
        abort_unless(
            str_starts_with((string) $file->getMimeType(), 'image/'),
            422,
            'The profile photo must be an image.',
        );

        // Drop the previous photo so storage doesn't accumulate orphans.
        if ($employee->avatar_path) {
            Storage::disk('public')->delete($employee->avatar_path);
        }

        $employee->update([
            'avatar_path' => $file->store('avatars', 'public'),
        ]);

        return new EmployeeResource($employee->fresh());
    }

    /**
     * DELETE /api/profile/avatar — remove the profile photo.
     */
    public function deleteAvatar(Request $request): EmployeeResource
    {
        /** @var Employee $employee */
        $employee = $request->user();

        if ($employee->avatar_path) {
            Storage::disk('public')->delete($employee->avatar_path);
            $employee->update(['avatar_path' => null]);
        }

        return new EmployeeResource($employee->fresh());
    }
}
