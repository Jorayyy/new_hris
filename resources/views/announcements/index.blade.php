@extends('layouts.app')

@section('title', 'Announcements')
@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Announcements</h2>
            <a class="btn primary sm" href="{{ route('announcements.create') }}">+ New announcement</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Title</th><th>Branch</th><th>Published</th><th>Posted by</th><th></th></tr></thead>
                <tbody>
                @forelse($announcements as $announcement)
                    <tr>
                        <td style="font-weight:600">{{ $announcement->title }}</td>
                        <td class="muted">{{ $announcement->branch?->name ?? 'All branches' }}</td>
                        <td>{{ $announcement->published_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="muted">{{ $announcement->author?->name ?? '—' }}</td>
                        <td class="btn-row">
                            <a class="btn sm" href="{{ route('announcements.edit', $announcement) }}">Edit</a>
                            <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" onsubmit="return confirm('Delete this announcement?')">
                                @csrf @method('DELETE')
                                <button class="btn sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No announcements yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $announcements->links() }}
    </div>
@endsection
