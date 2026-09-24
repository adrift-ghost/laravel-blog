@extends('blog::layouts.app')

@section('title', 'Team & roles')

@section('content')
<div class="layout-side">
    <div>
        <div class="card flush">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Member</th><th>Role</th><th>Active</th><th></th></tr></thead>
                    <tbody>
                    @forelse($members as $member)
                        <tr>
                            <td>
                                <strong>{{ $blog->userName($member->user) }}</strong>
                                <div class="muted small">{{ data_get($member->user, config('blog.user_email_column', 'email')) }}</div>
                            </td>
                            <td colspan="2">
                                <form method="POST" action="{{ blog_route('team.update', $member) }}" class="row">
                                    @csrf @method('PUT')
                                    <select name="role" style="width:auto">
                                        @foreach($roles as $role)
                                            <option value="{{ $role->value }}" @selected($member->role === $role)>{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                    <label class="check" style="margin:0"><input type="checkbox" name="is_active" value="1" @checked($member->is_active)> Active</label>
                                    <button class="btn btn-sm">Update</button>
                                </form>
                            </td>
                            <td style="text-align:right">
                                <form method="POST" action="{{ blog_route('team.destroy', $member) }}" data-confirm="Remove this member from the blog team?">
                                    @csrf @method('DELETE') <button class="btn btn-sm btn-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted" style="text-align:center;padding:40px">No team members yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($superAdmins)
            <p class="muted small">Super admins from <code>BLOG_SUPER_ADMINS</code>: {{ implode(', ', $superAdmins) }} — they always have Admin access.</p>
        @endif

        <div class="card">
            <h2>What each role can do</h2>
            <div class="table-wrap">
                <table class="small">
                    <thead><tr><th>Permission</th>@foreach($roles as $role)<th>{{ $role->label() }}</th>@endforeach</tr></thead>
                    <tbody>
                    @foreach(\Vitebox\LaravelBlog\BlogServiceProvider::PERMISSIONS as $permission)
                        <tr>
                            <td><code>{{ $permission }}</code></td>
                            @foreach($roles as $role)<td>{!! $role->allows($permission) ? '✔︎' : '<span class="muted">—</span>' !!}</td>@endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p class="muted small" style="margin-bottom:0">Change these in <code>config/blog.php</code> → <code>roles</code>.</p>
        </div>
    </div>

    <div>
        <form method="POST" action="{{ blog_route('team.store') }}" class="card">
            @csrf
            <h2>Add member</h2>
            <div class="field">
                <label for="member-email">User e-mail</label>
                <input type="email" id="member-email" name="email" required value="{{ old('email') }}" placeholder="writer@example.com">
                <div class="help">The user must already have an account on the website.</div>
            </div>
            <div class="field">
                <label for="member-role">Role</label>
                <select id="member-role" name="role">
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role', 'writer') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary btn-block">Add to team</button>
        </form>

        <div class="card">
            <h2>Roles</h2>
            @foreach($roles as $role)
                <div style="margin-bottom:10px"><strong>{{ $role->label() }}</strong><div class="muted small">{{ $role->description() }}</div></div>
            @endforeach
        </div>
    </div>
</div>
@endsection
