<x-layouts.admin>
    <x-slot name="title">Blog Posts</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Blog posts</h1>
            <div style="display: flex; gap: 8px;">
                <button type="button" onclick="document.getElementById('importModal').style.display='flex'" class="btn btn-secondary" style="font-size: 13px;">
                    <svg style="width: 16px; height: 16px; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import
                </button>
                <a href="{{ route('admin.blog-posts.create') }}" class="btn btn-primary" style="font-size: 13px;">Create blog post</a>
            </div>
        </div>
    </x-slot>

    {{-- Import Modal --}}
    <div id="importModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;" onclick="if(event.target === this) this.style.display='none'">
        <div style="background: #fff; border-radius: 12px; width: 100%; max-width: 480px; margin: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="padding: 16px 20px; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #303030;">Import Blog Posts</h3>
                <button type="button" onclick="document.getElementById('importModal').style.display='none'" style="background: none; border: none; cursor: pointer; padding: 4px; color: #616161;">
                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form action="{{ route('admin.blog-posts.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="padding: 20px;">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 6px;">CSV File</label>
                        <input type="file" name="csv_file" accept=".csv,.txt" required
                               style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #c9cccf; border-radius: 8px; background: #fff;">
                        <p style="margin: 6px 0 0; font-size: 12px; color: #616161;">Upload a CSV file with blog post data. Max 5MB.</p>
                    </div>
                    <div style="background: #f6f6f7; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                        <p style="margin: 0 0 8px; font-size: 13px; font-weight: 500; color: #303030;">CSV Format</p>
                        <p style="margin: 0 0 8px; font-size: 12px; color: #616161;">Required columns: <strong>title</strong></p>
                        <p style="margin: 0; font-size: 12px; color: #616161;">Optional: slug, excerpt, content, category, tags, meta_title, meta_description, is_published</p>
                    </div>
                    <a href="{{ route('admin.blog-posts.template') }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #005bd3; text-decoration: none;">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download CSV Template
                    </a>
                </div>
                <div style="padding: 12px 20px; border-top: 1px solid #e3e3e3; display: flex; justify-content: flex-end; gap: 8px; background: #fafafa; border-radius: 0 0 12px 12px;">
                    <button type="button" onclick="document.getElementById('importModal').style.display='none'" class="btn btn-secondary" style="font-size: 13px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Import</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Single card with tabs + search + table --}}
    <div class="card" style="overflow: hidden;">

        {{-- Tab filters --}}
        <div style="display: flex; align-items: center; gap: 0; border-bottom: 1px solid #e3e3e3; padding: 0 16px;">
            <a href="{{ route('admin.blog-posts.index') }}"
               style="padding: 10px 16px; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#005bd3' : 'transparent' }}; color: {{ !request('status') ? '#005bd3' : '#616161' }}; margin-bottom: -1px;">
                All
                <span style="background: {{ !request('status') ? '#e3f0ff' : '#f1f1f1' }}; color: {{ !request('status') ? '#005bd3' : '#616161' }}; padding: 1px 7px; border-radius: 10px; font-size: 11px; margin-left: 4px;">{{ $stats['total'] }}</span>
            </a>
            <a href="{{ route('admin.blog-posts.index', ['status' => 'published']) }}"
               style="padding: 10px 16px; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'published' ? '#005bd3' : 'transparent' }}; color: {{ request('status') === 'published' ? '#005bd3' : '#616161' }}; margin-bottom: -1px;">
                Published
                <span style="background: {{ request('status') === 'published' ? '#e3f0ff' : '#f1f1f1' }}; color: {{ request('status') === 'published' ? '#005bd3' : '#616161' }}; padding: 1px 7px; border-radius: 10px; font-size: 11px; margin-left: 4px;">{{ $stats['published'] }}</span>
            </a>
            <a href="{{ route('admin.blog-posts.index', ['status' => 'draft']) }}"
               style="padding: 10px 16px; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'draft' ? '#005bd3' : 'transparent' }}; color: {{ request('status') === 'draft' ? '#005bd3' : '#616161' }}; margin-bottom: -1px;">
                Draft
                <span style="background: {{ request('status') === 'draft' ? '#e3f0ff' : '#f1f1f1' }}; color: {{ request('status') === 'draft' ? '#005bd3' : '#616161' }}; padding: 1px 7px; border-radius: 10px; font-size: 11px; margin-left: 4px;">{{ $stats['drafts'] }}</span>
            </a>
        </div>

        {{-- Search bar --}}
        <div style="padding: 12px 16px; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.blog-posts.index') }}" method="GET" style="display: flex; align-items: center; gap: 8px;">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div style="position: relative; flex: 1;">
                    <svg style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #8a8a8a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" maxlength="100" aria-label="Search" value="{{ request('search') }}"
                           placeholder="Search blog posts"
                           style="width: 100%; padding: 6px 10px 6px 32px; font-size: 13px; border: 1px solid #c9cccf; border-radius: 8px; outline: none; color: #303030; background: #fff;">
                </div>
                @if(request('search'))
                    <a href="{{ route('admin.blog-posts.index', request('status') ? ['status' => request('status')] : []) }}"
                       style="font-size: 13px; color: #005bd3; text-decoration: none; white-space: nowrap;">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 8px 16px; text-align: left; font-weight: 500; color: #616161; font-size: 12px; background: #fafafa;">Post</th>
                        <th style="padding: 8px 16px; text-align: left; font-weight: 500; color: #616161; font-size: 12px; background: #fafafa;">Status</th>
                        <th style="padding: 8px 16px; text-align: left; font-weight: 500; color: #616161; font-size: 12px; background: #fafafa;">Author / Date</th>
                        <th style="padding: 8px 16px; text-align: right; font-weight: 500; color: #616161; font-size: 12px; background: #fafafa;">Views</th>
                        <th style="padding: 8px 16px; text-align: right; font-weight: 500; color: #616161; font-size: 12px; background: #fafafa;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        <tr style="border-bottom: 1px solid #e3e3e3; cursor: pointer;"
                            onclick="window.location='{{ route('admin.blog-posts.edit', $post) }}'"
                            onmouseover="this.style.background='#f6f6f7'"
                            onmouseout="this.style.background='transparent'">

                            {{-- Post with thumbnail --}}
                            <td style="padding: 8px 16px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    @if($post->featured_image)
                                        <img src="{{ asset_v('storage/' . $post->featured_image) }}" alt=""
                                             style="width: 2.5rem; height: 2.5rem; border-radius: 6px; object-fit: cover; flex-shrink: 0; border: 1px solid #e3e3e3;">
                                    @else
                                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 6px; background: #f1f1f1; flex-shrink: 0; border: 1px solid #e3e3e3;"></div>
                                    @endif
                                    <div style="min-width: 0;">
                                        <div style="color: #005bd3; font-weight: 500; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 280px;">{{ $post->title }}</div>
                                        @if($post->category)
                                            <div style="color: #616161; font-size: 12px; margin-top: 1px;">{{ $post->category }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Status badge --}}
                            <td style="padding: 8px 16px;">
                                @if($post->is_published)
                                    <span class="badge badge-success">Published</span>
                                @else
                                    <span class="badge badge-warning">Draft</span>
                                @endif
                            </td>

                            {{-- Author / Date --}}
                            <td style="padding: 8px 16px; color: #616161; font-size: 13px;">
                                @if($post->published_at)
                                    {{ $post->published_at->format('M d, Y') }}
                                @else
                                    <span style="color: #8a8a8a;">-</span>
                                @endif
                            </td>

                            {{-- Views --}}
                            <td style="padding: 8px 16px; text-align: right; color: #616161; font-size: 13px;">
                                {{ number_format($post->view_count) }}
                            </td>

                            {{-- Actions --}}
                            <td style="padding: 8px 16px; text-align: right;" onclick="event.stopPropagation();">
                                {{-- Vertical padding cancelled by a negative margin: a ~36px touch target without changing the row height. --}}
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                                    @if($post->is_published)
                                        <a href="{{ route('blog.show', $post->slug) }}" target="_blank"
                                           style="color: #005bd3; text-decoration: none; font-size: 13px; font-weight: 500; padding: 0.5rem 0; margin: -0.5rem 0;">View</a>
                                    @endif
                                    <a href="{{ route('admin.blog-posts.edit', $post) }}"
                                       style="color: #005bd3; text-decoration: none; font-size: 13px; font-weight: 500; padding: 0.5rem 0; margin: -0.5rem 0;">Edit</a>
                                    <form action="{{ route('admin.blog-posts.toggle-status', $post) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" style="background: none; border: none; color: #005bd3; cursor: pointer; font-size: 13px; font-weight: 500; padding: 0.5rem 0; margin: -0.5rem 0;">
                                            {{ $post->is_published ? 'Unpublish' : 'Publish' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.blog-posts.destroy', $post) }}" method="POST"
                                          onsubmit="return confirm('Delete this blog post?')" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background: none; border: none; color: #d72c0d; cursor: pointer; font-size: 13px; font-weight: 500; padding: 0.5rem 0; margin: -0.5rem 0;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 48px 16px; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                                        <svg style="width: 24px; height: 24px; color: #8a8a8a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                        </svg>
                                    </div>
                                    <p style="font-weight: 500; color: #303030; margin: 0 0 4px 0; font-size: 14px;">No blog posts found</p>
                                    <p style="color: #616161; font-size: 13px; margin: 0 0 16px 0;">
                                        @if(request()->hasAny(['search', 'status']))
                                            No posts match your current filters.
                                        @else
                                            Create your first blog post to get started.
                                        @endif
                                    </p>
                                    @if(request()->hasAny(['search', 'status']))
                                        <a href="{{ route('admin.blog-posts.index') }}" class="btn btn-secondary" style="font-size: 13px;">Clear filters</a>
                                    @else
                                        <a href="{{ route('admin.blog-posts.create') }}" class="btn btn-primary" style="font-size: 13px;">Create blog post</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($posts->hasPages())
            <div style="padding: 12px 16px; border-top: 1px solid #e3e3e3;">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
