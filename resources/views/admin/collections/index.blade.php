<x-layouts.admin>
    <x-slot name="title">Collections</x-slot>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
        <div>
            <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Collections</h1>
            <p style="font-size: 13px; color: #616161; margin-top: 0.15rem;">
                Hand-picked groups of products. Tick the products on the product form.
            </p>
        </div>
        <a href="{{ route('admin.collections.create') }}" class="btn btn-primary" style="font-size: 13px;">Add collection</a>
    </div>

    <div class="card">
        @if($collections->isEmpty())
            <div style="padding: 2.5rem 1rem; text-align: center;">
                <p style="font-size: 13px; color: #616161;">No collections yet.</p>
                <p style="font-size: 12px; color: #8a8a8a; margin-top: 0.35rem;">
                    New In, Bestsellers and Introductory Offer are built in and fill themselves.
                    Make a collection when you want to choose the products by hand.
                </p>
            </div>
        @else
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3; text-align: left; color: #616161;">
                        <th style="padding: 0.6rem 1rem; font-weight: 500;">Name</th>
                        <th style="padding: 0.6rem 1rem; font-weight: 500;">URL</th>
                        <th style="padding: 0.6rem 1rem; font-weight: 500;">Products</th>
                        <th style="padding: 0.6rem 1rem; font-weight: 500;">In header</th>
                        <th style="padding: 0.6rem 1rem; font-weight: 500;">Status</th>
                        <th style="padding: 0.6rem 1rem;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($collections as $collection)
                        <tr style="border-bottom: 1px solid #f1f1f1;">
                            <td style="padding: 0.6rem 1rem; color: #303030; font-weight: 500;">{{ $collection->name }}</td>
                            <td style="padding: 0.6rem 1rem; color: #616161;">
                                <a href="{{ route('collection.show', $collection) }}" target="_blank" rel="noopener" style="color: #3A6166;">
                                    /collection/{{ $collection->slug }}
                                </a>
                            </td>
                            <td style="padding: 0.6rem 1rem; color: #616161;">{{ $collection->products_count }}</td>
                            <td style="padding: 0.6rem 1rem;">
                                @if($collection->show_in_header)
                                    <span class="badge badge-success">Yes</span>
                                @else
                                    <span style="color: #8a8a8a;">No</span>
                                @endif
                            </td>
                            <td style="padding: 0.6rem 1rem;">
                                @if($collection->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-warning">Hidden</span>
                                @endif
                            </td>
                            <td style="padding: 0.6rem 1rem; text-align: right; white-space: nowrap;">
                                <a href="{{ route('admin.collections.edit', $collection) }}" style="color: #3A6166; text-decoration: none;">Edit</a>
                                <form action="{{ route('admin.collections.destroy', $collection) }}" method="POST"
                                      style="display: inline;" onsubmit="return confirm('Delete this collection? The products themselves are not affected.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: #d72c0d; cursor: pointer; font-size: 13px; margin-left: 0.75rem;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if($collections->hasPages())
        <div style="margin-top: 1rem;">{{ $collections->links() }}</div>
    @endif
</x-layouts.admin>
