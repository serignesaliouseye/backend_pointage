 <x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium">Notifications</h2>
                @if($this->getUnreadCount() > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-800">
                        {{ $this->getUnreadCount() }} non lue(s)
                    </span>
                @endif
            </div>

            <div class="space-y-3">
                @forelse($this->getNotifications() as $notification)
                    <div class="p-3 rounded-lg {{ $notification->read_at ? 'bg-gray-50' : 'bg-primary-50' }}">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                @if(($notification->data['type'] ?? '') === 'sanction')
                                    <span>🔴</span>
                                @else
                                    <span>🔔</span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $notification->data['motif'] ?? 'Notification' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">
                        Aucune notification
                    </p>
                @endforelse
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>