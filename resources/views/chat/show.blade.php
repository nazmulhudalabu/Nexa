@extends('layouts.app', ['title' => 'Chat with '.$otherUser->name])

@section('content')
@php
$emojiGroups = [
    'Smileys & people' => ['😀', '😁', '😂', '🤣', '😊', '😍', '😘', '😜', '🤔', '😎', '🥳', '😢', '😭', '😡', '🤯', '😴', '🤒', '🤗', '😇', '🫡'],
    'Gestures' => ['👍', '👎', '👌', '✌️', '🤞', '🤙', '👋', '🫶', '🙏', '👏', '💪', '🤝', '🙌', '🫰', '👆', '👇'],
    'Hearts & symbols' => ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🔥', '✨', '⭐', '💯', '🎉', '🎁', '☕', '🍕', '⚽'],
];
$attachmentAccept = 'image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime,video/webm,audio/mpeg,audio/wav,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/plain,text/csv,application/zip';
@endphp
<div class="chat-shell chat-page">
    <a class="back-link" href="{{ route('chat.index') }}">← All messages</a>
    <header class="chat-person">
        @if ($conversation->isGroup())<div class="group-avatar">◎</div><div><strong>{{ $conversation->name }}</strong><span>{{ $conversation->members->count() }} members</span></div>@else<img src="{{ $otherUser->profile_photo_url }}" alt=""><div><strong>Chat with {{ $otherUser->name }}</strong><span>{{ $otherUser->profession ?: 'Nexa member' }}</span></div>@endif
        <small class="chat-identity">You are {{ $currentUser->name }}</small>
    </header>

    <div class="chatbox">
        <div class="message-history" id="message-history">
            @if ($hasMore)
                <button type="button" class="load-earlier" id="load-earlier" data-url="{{ route('chat.older', $conversation) }}" data-before="{{ $messages->first()->id }}">Load earlier messages</button>
            @endif
            @forelse ($messages as $message)
                @include('chat.partials.message')
            @empty
                <div class="chat-empty"><p>This is the beginning of your conversation.</p><span>Say hello to {{ $otherUser->name }}.</span></div>
            @endforelse
        </div>

        <form class="message-composer" method="POST" action="{{ route('chat.send', $conversation) }}" enctype="multipart/form-data">
            @csrf
            <div class="composer-bar">
                <div class="emoji-wrap">
                    <button type="button" class="icon-button" id="emoji-toggle" aria-label="Add emoji" title="Emoji">☺</button>
                    <div class="emoji-panel" id="emoji-panel" hidden>
                        @foreach ($emojiGroups as $group => $emojis)
                            <div class="emoji-group">
                                <small>{{ $group }}</small>
                                <div class="emoji-grid">
                                    @foreach ($emojis as $emoji)
                                        <button type="button" class="emoji-option" data-emoji="{{ $emoji }}">{{ $emoji }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <label class="icon-button" for="attachment" title="Attach a file">
                    <span aria-hidden="true">📎</span>
                    <input class="sr-only" id="attachment" name="attachment" type="file" accept="{{ $attachmentAccept }}">
                </label>
                <textarea id="message-body" name="body" rows="1" placeholder="Write a message..." maxlength="5000"></textarea>
                <button class="button button-accent" type="submit">Send</button>
            </div>
            <div class="composer-meta">
                <span class="attachment-chip" id="attachment-chip" hidden></span>
                @error('body')<span class="field-error">{{ $message }}</span>@enderror
                @error('attachment')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var history = document.getElementById('message-history');
    var body = document.getElementById('message-body');

    if (history) {
        history.scrollTop = history.scrollHeight;
    }

    var toggle = document.getElementById('emoji-toggle');
    var panel = document.getElementById('emoji-panel');
    if (toggle && panel) {
        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            panel.hidden = !panel.hidden;
        });
        document.addEventListener('click', function (event) {
            if (!panel.hidden && !panel.contains(event.target) && event.target !== toggle) {
                panel.hidden = true;
            }
        });
        panel.addEventListener('click', function (event) {
            var option = event.target.closest('.emoji-option');
            if (option && body) {
                insertAtCursor(body, option.dataset.emoji);
            }
        });
    }

    document.querySelectorAll('.message-reaction-toggle').forEach(function (reactionToggle) {
        reactionToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var reactions = reactionToggle.nextElementSibling;
            if (reactions) {
                reactions.hidden = !reactions.hidden;
            }
        });
    });

    document.addEventListener('click', function (event) {
        var menuToggle = event.target.closest('.message-menu-toggle');
        var editToggle = event.target.closest('.message-edit-toggle');
        var cancelToggle = event.target.closest('.message-edit-cancel');

        if (menuToggle) {
            event.stopPropagation();
            var menu = menuToggle.nextElementSibling;
            document.querySelectorAll('.message-menu:not([hidden])').forEach(function (openMenu) {
                if (openMenu !== menu) {
                    openMenu.hidden = true;
                }
            });
            menu.hidden = !menu.hidden;
            return;
        }

        if (editToggle) {
            var bubble = editToggle.closest('.message-bubble');
            var editForm = bubble && bubble.querySelector('.message-edit-form');
            if (editForm) {
                editForm.hidden = false;
                editForm.querySelector('textarea').focus();
            }
            editToggle.closest('.message-menu').hidden = true;
            return;
        }

        if (cancelToggle) {
            cancelToggle.closest('.message-edit-form').hidden = true;
            return;
        }

        document.querySelectorAll('.message-menu:not([hidden])').forEach(function (menu) {
            if (!menu.parentElement.contains(event.target)) {
                menu.hidden = true;
            }
        });
    });

    document.addEventListener('click', function (event) {
        document.querySelectorAll('.message-reactions:not([hidden])').forEach(function (reactions) {
            if (!reactions.parentElement.contains(event.target)) {
                reactions.hidden = true;
            }
        });
    });

    function insertAtCursor(field, text) {
        var start = field.selectionStart || field.value.length;
        var end = field.selectionEnd || start;
        field.value = field.value.slice(0, start) + text + field.value.slice(end);
        var caret = start + text.length;
        field.focus();
        field.setSelectionRange(caret, caret);
    }

    var input = document.getElementById('attachment');
    var chip = document.getElementById('attachment-chip');
    if (input && chip) {
        input.addEventListener('change', function () {
            var file = input.files[0];
            if (!file) {
                chip.hidden = true;
                chip.textContent = '';
                return;
            }
            chip.innerHTML = '';
            var label = document.createElement('span');
            label.textContent = '📎 ' + file.name;
            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'chip-remove';
            remove.textContent = '×';
            remove.setAttribute('aria-label', 'Remove attachment');
            remove.addEventListener('click', function () {
                input.value = '';
                chip.hidden = true;
                chip.textContent = '';
            });
            chip.appendChild(label);
            chip.appendChild(remove);
            chip.hidden = false;
        });
    }

    var button = document.getElementById('load-earlier');
    if (button && history) {
        button.addEventListener('click', function () {
            if (button.disabled) {
                return;
            }
            button.disabled = true;
            var url = new URL(button.dataset.url, window.location.origin);
            url.searchParams.set('before', button.dataset.before);
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Request failed');
                    }
                    return response.json();
                })
                .then(function (data) {
                    var previousHeight = history.scrollHeight;
                    history.insertAdjacentHTML('afterbegin', data.html);
                    history.scrollTop = history.scrollHeight - previousHeight;
                    if (data.oldest_id) {
                        button.dataset.before = data.oldest_id;
                    }
                    if (data.has_more) {
                        button.disabled = false;
                    } else {
                        button.remove();
                    }
                })
                .catch(function () {
                    button.disabled = false;
                });
        });
    }
})();
</script>
@endsection
