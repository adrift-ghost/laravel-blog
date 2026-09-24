<?php

namespace Vitebox\LaravelBlog\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Vitebox\LaravelBlog\Enums\BlogRole;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Http\Controllers\Controller;
use Vitebox\LaravelBlog\Models\TeamMember;

class TeamController extends Controller
{
    public function index()
    {
        $this->authorize('blog.team.manage');

        return view('blog::team.index', [
            'members' => TeamMember::query()->with('user')->orderBy('role')->get(),
            'roles' => BlogRole::cases(),
            'superAdmins' => (array) config('blog.super_admins', []),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('blog.team.manage');

        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', Rule::enum(BlogRole::class)],
        ]);

        $user = Blog::findUserByEmail($data['email']);
        if (! $user) {
            return back()->withInput()->withErrors(['email' => 'No user with this e-mail exists in the application. They need an account first.']);
        }

        Blog::assignRole($user, $data['role']);

        return back()->with('blog_success', Blog::userName($user).' is now a '.BlogRole::from($data['role'])->label().'.');
    }

    public function update(Request $request, TeamMember $member)
    {
        $this->authorize('blog.team.manage');

        $data = $request->validate([
            'role' => ['required', Rule::enum(BlogRole::class)],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        if ($this->wouldRemoveLastAdmin($member, BlogRole::from($data['role']), $data['is_active'])) {
            return back()->with('blog_error', 'You cannot remove the last active admin.');
        }

        $member->update($data);
        Blog::flushRoleCache();

        return back()->with('blog_success', 'Team member updated.');
    }

    public function destroy(TeamMember $member)
    {
        $this->authorize('blog.team.manage');

        if ($this->wouldRemoveLastAdmin($member, null, false)) {
            return back()->with('blog_error', 'You cannot remove the last active admin.');
        }

        $member->delete();
        Blog::flushRoleCache();

        return back()->with('blog_success', 'Team member removed.');
    }

    protected function wouldRemoveLastAdmin(TeamMember $member, ?BlogRole $newRole, bool $active): bool
    {
        if ($member->role !== BlogRole::Admin || ($newRole === BlogRole::Admin && $active)) {
            return false;
        }

        if (! empty(config('blog.super_admins'))) {
            return false; // a configured super admin can always get back in
        }

        return TeamMember::query()
            ->where('role', BlogRole::Admin->value)
            ->where('is_active', true)
            ->whereKeyNot($member->getKey())
            ->doesntExist();
    }
}
