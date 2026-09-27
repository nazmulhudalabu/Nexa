<div class="message {{ (int) $message->user_id === (int) auth()->id() ? 'message-own' : '' }}" data-id="{{ $message->id }}">
    <img src="{{ $message->user->profile_photo_url }}" alt="{{ $message->user->name }} profile photo">
    <div class="message-bubble">
        <small class="message-sender">{{ (int) $message->user_id === (int) auth()->id() ? 'You' : $message->user->name }}</small>
        @if ($message->deleted_at)
            <p class="message-text"><em>Message deleted</em></p>
        @elseif (trim((string) $message->body) !== '')
            <p class="message-text">{{ $message->body }}</p>
        @endif
        @if ($message->attachment_path)
            @if (str_starts_with((string) $message->attachment_mime, 'image/'))
                <a href="{{ $message->attachment_url }}" target="_blank" rel="noopener"><img class="message-attachment-image" src="{{ $message->attachment_url }}" alt="{{ $message->attachment_name }}"></a>
            @elseif (str_starts_with((string) $message->attachment_mime, 'video/'))
                <video class="message-attachment-video" controls preload="metadata"><source src="{{ $message->attachment_url }}"></video>
            @else
                <a class="message-attachment-file" href="{{ $message->attachment_url }}" download="{{ $message->attachment_name }}">
                    <span class="file-icon" aria-hidden="true">📄</span>
                    <span><strong>{{ $message->attachment_name }}</strong><small>{{ number_format(($message->attachment_size ?? 0) / 1024) }} KB</small></span>
                </a>
            @endif
        @endif
        <small class="message-time">{{ $message->created_at->format('M j, g:i A') }}</small>
        @if ($message->edited_at)<small>edited</small>@endif @if ($message->pinned)<small>pinned</small>@endif
        @auth
            @if ((int) $message->user_id === (int) auth()->id() && !$message->deleted_at)
                <div class="message-menu-wrap">
                    <button class="message-action message-menu-toggle" type="button" aria-label="Message options" title="Message options">⋮</button>
                    <div class="message-menu" hidden>
                        @if (trim((string) $message->body) !== '')
                            <button class="message-menu-item message-edit-toggle" type="button">Edit</button>
                        @endif
                        <form method="POST" action="{{ route('chat.message.delete', $message) }}" onsubmit="return confirm('Delete this message?');">
                            @csrf
                            @method('DELETE')
                            <button class="message-menu-item message-delete-item" type="submit">Delete</button>
                        </form>
                    </div>
                </div>
                @if (trim((string) $message->body) !== '')
                    <form class="message-edit-form" method="POST" action="{{ route('chat.message.update', $message) }}" hidden>
                        @csrf
                        @method('PUT')
                        <textarea name="body" rows="2" maxlength="5000" required>{{ $message->body }}</textarea>
                        <div class="message-edit-actions">
                            <button class="message-action message-edit-cancel" type="button">Cancel</button>
                            <button class="message-action message-edit-save" type="submit">Save</button>
                        </div>
                    </form>
                @endif
            @endif
            <div class="message-reaction-picker"><button class="message-action message-reaction-toggle" type="button" aria-label="React to message" title="React">☺</button><div class="message-reactions" hidden>@foreach (['LIKE' => '👍', 'LOVE' => '❤️', 'HAHA' => '😂', 'WOW' => '😮'] as $reactionType => $emoji)<form method="POST" action="{{ route('chat.message.react', [$message, 'type' => $reactionType]) }}">@csrf<button class="message-action" type="submit" title="{{ $reactionType }}">{{ $emoji }}</button></form>@endforeach</div></div>
        @endauth
    </div>
</div>
