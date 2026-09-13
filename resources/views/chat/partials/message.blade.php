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
        @auth @if ((int) $message->user_id === (int) auth()->id() && !$message->deleted_at)<form method="POST" action="{{ route('chat.message.delete', $message) }}">@csrf @method('DELETE')<button class="message-action" type="submit">Delete</button></form>@endif<div class="message-reaction-picker"><button class="message-action message-reaction-toggle" type="button" aria-label="React to message" title="React">☺</button><div class="message-reactions" hidden>@foreach (['LIKE' => '👍', 'LOVE' => '❤️', 'HAHA' => '😂', 'WOW' => '😮'] as $reactionType => $emoji)<form method="POST" action="{{ route('chat.message.react', [$message, 'type' => $reactionType]) }}">@csrf<button class="message-action" type="submit" title="{{ $reactionType }}">{{ $emoji }}</button></form>@endforeach</div></div>@endauth
    </div>
</div>
