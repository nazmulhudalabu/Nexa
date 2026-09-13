<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use App\Models\ConversationMember;
use App\Models\MessageReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChatController extends Controller
{
    private const PAGE_SIZE = 10;

    public function index(): View
    {
        $userId = Auth::id();
        $conversations = Conversation::query()
            ->where(fn ($query) => $query->where(fn ($direct) => $direct->where('user_one_id', $userId)->orWhere('user_two_id', $userId))->orWhereHas('members', fn ($members) => $members->where('user_id', $userId)))
            ->with(['userOne', 'userTwo', 'messages' => fn ($query) => $query->reorder('id', 'desc')->limit(1)])
            ->withCount(['messages as unread_messages_count' => fn ($query) => $query->whereNull('read_at')->where('user_id', '!=', $userId)])
            ->latest('updated_at')
            ->get();

        return view('chat.index', compact('conversations'));
    }

    public function with(User $user): RedirectResponse
    {
        abort_if((int) $user->id === (int) Auth::id(), 404);
        $conversation = $this->conversationFor($user);
        return to_route('chat.show', $conversation);
    }

    public function createGroup(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'member_ids' => ['required', 'array', 'min:1'], 'member_ids.*' => ['integer', 'exists:users,id']]);
        $conversation = Conversation::create(['type' => 'GROUP', 'name' => $data['name'], 'created_by' => Auth::id(), 'user_one_id' => Auth::id(), 'user_two_id' => Auth::id()]);
        $members = collect($data['member_ids'])->push(Auth::id())->unique();
        $members->each(fn (int $userId) => ConversationMember::create(['conversation_id' => $conversation->id, 'user_id' => $userId, 'is_admin' => $userId === Auth::id()]));
        return to_route('chat.show', $conversation);
    }

    public function show(Conversation $conversation): View
    {
        $this->authorizeConversation($conversation);
        $conversation->messages()->whereNull('read_at')->where('user_id', '!=', Auth::id())->update(['read_at' => now()]);
        $conversation->load(['userOne', 'userTwo', 'members']);
        $currentUser = Auth::user();
        $otherUser = $conversation->isGroup() ? null : ((int) $conversation->user_one_id === (int) $currentUser->id
            ? $conversation->userTwo
            : $conversation->userOne);

        abort_if(! $conversation->isGroup() && ! $otherUser, 404);

        $batch = $conversation->messages()->reorder('id', 'desc')->limit(self::PAGE_SIZE + 1)->get();
        $hasMore = $batch->count() > self::PAGE_SIZE;
        $messages = $batch->take(self::PAGE_SIZE)->reverse()->values();

        return view('chat.show', compact('conversation', 'currentUser', 'otherUser', 'messages', 'hasMore'));
    }

    public function older(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($conversation);
        $before = $request->integer('before');
        abort_unless($before > 0, 422);

        $batch = $conversation->messages()
            ->where('id', '<', $before)
            ->reorder('id', 'desc')
            ->limit(self::PAGE_SIZE + 1)
            ->get();
        $hasMore = $batch->count() > self::PAGE_SIZE;
        $messages = $batch->take(self::PAGE_SIZE)->reverse()->values();

        return response()->json([
            'html' => view('chat.partials.messages', ['messages' => $messages])->render(),
            'oldest_id' => $messages->first()?->id,
            'has_more' => $hasMore,
        ]);
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeConversation($conversation);
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => [
                'nullable',
                'file',
                'max:20480',
                'mimes:jpg,jpeg,png,gif,webp,mp4,mov,webm,mp3,wav,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip',
            ],
            'type' => ['nullable', 'in:TEXT,IMAGE,VIDEO,FILE,AUDIO,VOICE,GIF,STICKER,LOCATION'],
            'reply_to_id' => ['nullable', 'exists:messages,id'],
        ]);

        $messageData = ['user_id' => Auth::id(), 'body' => $data['body'] ?? null, 'type' => $data['type'] ?? 'TEXT', 'reply_to_id' => $data['reply_to_id'] ?? null, 'status' => 'SENT'];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $messageData['attachment_path'] = $file->store('chat-attachments', 'public');
            $messageData['attachment_name'] = $file->getClientOriginalName();
            $messageData['attachment_mime'] = (string) $file->getClientMimeType();
            $messageData['attachment_size'] = $file->getSize();
            $messageData['type'] = str_starts_with((string) $file->getClientMimeType(), 'image/') ? 'IMAGE' : (str_starts_with((string) $file->getClientMimeType(), 'video/') ? 'VIDEO' : (str_starts_with((string) $file->getClientMimeType(), 'audio/') ? 'AUDIO' : 'FILE'));
        }

        $conversation->messages()->create($messageData);
        $conversation->touch();
        return back();
    }

    private function conversationFor(User $otherUser): Conversation
    {
        $ids = [Auth::id(), $otherUser->id]; sort($ids);
        $conversation = Conversation::firstOrCreate(['user_one_id' => $ids[0], 'user_two_id' => $ids[1]], ['type' => 'DIRECT']);
        ConversationMember::firstOrCreate(['conversation_id' => $conversation->id, 'user_id' => $ids[0]]);
        ConversationMember::firstOrCreate(['conversation_id' => $conversation->id, 'user_id' => $ids[1]]);
        return $conversation;
    }

    private function authorizeConversation(Conversation $conversation): void
    {
        $userId = (int) Auth::id();
        abort_unless($conversation->isGroup() ? $conversation->members()->where('user_id', $userId)->exists() : $userId === (int) $conversation->user_one_id || $userId === (int) $conversation->user_two_id, 403);
    }

    public function react(Request $request, \App\Models\Message $message, string $type): RedirectResponse
    {
        $this->authorizeConversation($message->conversation); abort_unless(in_array($type, ['LIKE','LOVE','HAHA','WOW'], true), 422);
        $reaction = MessageReaction::firstOrNew(['message_id' => $message->id, 'user_id' => Auth::id()]);
        $reaction->reaction_type === $type && $reaction->exists ? $reaction->delete() : $reaction->fill(['reaction_type' => $type])->save(); return back();
    }

    public function updateMessage(Request $request, \App\Models\Message $message): RedirectResponse
    {
        abort_unless($message->user_id === Auth::id(), 403); $data = $request->validate(['body' => ['required','string','max:5000']]); $message->update(['body' => $data['body'], 'edited_at' => now()]); return back();
    }

    public function deleteMessage(\App\Models\Message $message): RedirectResponse
    {
        abort_unless($message->user_id === Auth::id(), 403); $message->update(['body' => null, 'attachment_path' => null, 'deleted_at' => now()]); return back();
    }

    public function pinMessage(\App\Models\Message $message): RedirectResponse
    {
        $this->authorizeConversation($message->conversation); $message->update(['pinned' => ! $message->pinned]); return back();
    }
}
