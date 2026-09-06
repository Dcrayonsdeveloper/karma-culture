{{--
    "This is set on the server, not here."

    Stands in for a credential field that used to be an input. It shows whether
    the value is configured and names the environment variable to set, so an
    admin who came looking for the box is told where the setting went rather
    than left staring at a panel with nothing in it.

    Never render the value itself - the whole point is that a credential is not
    readable from a browser.

    $vars       array<string, bool>  env var name => whether it is set
    $help       string|null          one line on where to get the value
    $configured bool|null            overall state; defaults to "every var set"
--}}
@php
    $vars = $vars ?? [];
    $configured = $configured ?? (count($vars) > 0 && ! in_array(false, array_values($vars), true));
@endphp
<div style="padding: 0.75rem; border: 1px solid #e3e3e3; border-radius: 0.5rem; background: #fafafa;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
        <svg style="width: 14px; height: 14px; color: #616161; flex: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        <span style="font-size: 12px; font-weight: 600; color: #303030;">Set on the server</span>
        @if($configured)
            <span class="badge badge-success" style="font-size: 11px;">Configured</span>
        @else
            <span class="badge badge-neutral" style="font-size: 11px;">Not configured</span>
        @endif
    </div>

    <p style="font-size: 12px; color: #616161; margin: 0 0 0.5rem;">
        These are credentials, so they are kept in the server's <code style="background: #f1f1f1; padding: 0.05rem 0.25rem; border-radius: 0.25rem;">.env</code> file
        and cannot be viewed or changed from the admin panel.
    </p>

    <ul style="margin: 0; padding-left: 1rem; font-size: 12px; color: #303030;">
        @foreach($vars as $name => $isSet)
            <li style="margin-bottom: 0.15rem;">
                <code style="background: #f1f1f1; padding: 0.05rem 0.25rem; border-radius: 0.25rem;">{{ $name }}</code>
                @if($isSet)
                    <span style="color: #0f7b3f;">&check; set</span>
                @else
                    <span style="color: #8a6d00;">not set</span>
                @endif
            </li>
        @endforeach
    </ul>

    @if(!empty($help))
        <p style="font-size: 11px; color: #616161; margin: 0.5rem 0 0;">{{ $help }}</p>
    @endif
</div>
