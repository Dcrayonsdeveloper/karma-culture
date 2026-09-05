<x-layouts.admin>
    <x-slot name="title">Abandoned Cart Settings</x-slot>

    <x-slot name="header">
        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <a href="{{ route('admin.abandoned-carts.index') }}" style="display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 0.5rem; color: #616161; text-decoration: none;" class="btn-icon">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div style="flex: 1 1 14rem; min-width: 0;">
                <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030;">Abandoned cart settings</h1>
                <p style="font-size: 13px; color: #616161; margin-top: 2px;">These apply to detection, the listing and the reminder emails alike.</p>
            </div>
        </div>
    </x-slot>

    <x-admin.form-errors />

    <form action="{{ route('admin.abandoned-carts.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Detection</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Idle before abandoned (hours)</label>
                        <input type="number" min="1" max="720" name="threshold_hours" value="{{ old('threshold_hours', $settings['threshold_hours']) }}" required class="form-input">
                        <p style="font-size: 12px; color: #999; margin-top: 0.25rem;">How long a cart with items must sit untouched. Also governs the reminder emails, so the list and the mail can never disagree.</p>
                        @error('threshold_hours') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Write off after (days)</label>
                        <input type="number" min="1" max="365" name="expiry_days" value="{{ old('expiry_days', $settings['expiry_days']) }}" required class="form-input">
                        <p style="font-size: 12px; color: #999; margin-top: 0.25rem;">Unrecovered carts older than this are marked expired and drop out of the open list.</p>
                        @error('expiry_days') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">"Recently abandoned" covers (hours)</label>
                        <input type="number" min="1" max="720" name="recent_hours" value="{{ old('recent_hours', $settings['recent_hours']) }}" required class="form-input">
                        @error('recent_hours') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Reminders and recovery links</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Wait between reminders (hours)</label>
                        <input type="number" min="1" max="8760" name="reminder_cooldown_hours" value="{{ old('reminder_cooldown_hours', $settings['reminder_cooldown_hours']) }}" required class="form-input">
                        @error('reminder_cooldown_hours') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Maximum reminders per cart</label>
                        <input type="number" min="1" max="10" name="max_reminders" value="{{ old('max_reminders', $settings['max_reminders']) }}" required class="form-input">
                        <p style="font-size: 12px; color: #999; margin-top: 0.25rem;">Counted per basket, not per customer, so a shopper who abandons twice starts again from zero.</p>
                        @error('max_reminders') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Recovery link valid for (days)</label>
                        <input type="number" min="1" max="365" name="recovery_link_days" value="{{ old('recovery_link_days', $settings['recovery_link_days']) }}" required class="form-input">
                        <p style="font-size: 12px; color: #999; margin-top: 0.25rem;">Measured from the moment the cart was abandoned. An expired link sends the customer to their current cart instead.</p>
                        @error('recovery_link_days') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- The one thing an admin has to know to trust this screen: nothing on
             this host runs on a schedule, so detection happens when somebody
             looks, or when they press Scan now. --}}
        <div class="card" style="margin-top: 1rem; padding: 0.75rem 1rem;">
            <p style="font-size: 13px; color: #303030;">
                Carts are re-scanned when this section is opened (at most once every five minutes) and whenever you press <strong>Scan now</strong>.
                The <code>carts:detect-abandoned</code> command does the same job for a host that runs the Laravel scheduler.
            </p>
        </div>

        <div style="margin-top: 1rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
            <a href="{{ route('admin.abandoned-carts.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
    </form>
</x-layouts.admin>
