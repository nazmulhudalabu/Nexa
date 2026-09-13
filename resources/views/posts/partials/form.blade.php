<form class="post-form" method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="flash flash-error" role="alert">Please check the highlighted fields and try again.</div>
    @endif

    <div class="field">
        <label for="name">Title</label>
        <input id="name" name="name" value="{{ old('name', $post->name ?? '') }}" required maxlength="120" autofocus>
        @error('name')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="field">
        <label for="post_type">Post type</label><select id="post_type" name="post_type"><option value="MEDIA" @selected(old('post_type', isset($post) ? 'MEDIA' : '') === 'MEDIA')>Photos or video</option><option value="TEXT" @selected(old('post_type') === 'TEXT')>Text only</option></select>
    </div>

    <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="9" required maxlength="5000">{{ old('description', $post->description ?? '') }}</textarea>
        @error('description')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-row">
        <div class="field"><label for="visibility">Who can see this?</label><select id="visibility" name="visibility"><option value="PUBLIC" @selected(old('visibility', $post->visibility ?? 'PUBLIC') === 'PUBLIC')>Everyone</option><option value="FRIENDS" @selected(old('visibility', $post->visibility ?? '') === 'FRIENDS')>Friends</option><option value="ONLY_ME" @selected(old('visibility', $post->visibility ?? '') === 'ONLY_ME')>Only me</option></select></div>
        <div class="field"><label for="location">Location</label><input id="location" name="location" value="{{ old('location', $post->location ?? '') }}" placeholder="Dhaka"></div>
    </div>
    <div class="form-row"><div class="field"><label for="feeling">Feeling or activity</label><input id="feeling" name="feeling" value="{{ old('feeling', $post->feeling ?? '') }}" placeholder="Feeling happy 😊"></div><div class="field"><label for="link_url">Link URL</label><input id="link_url" name="link_url" type="url" value="{{ old('link_url', $post->link_url ?? '') }}" placeholder="https://example.com"></div></div>

    <div class="form-row">
        <div class="field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">No category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $post->category_id ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="tags">Tags</label>
            <input id="tags" name="tags" value="{{ old('tags', isset($post) ? $post->tags->pluck('name')->implode(', ') : '') }}" placeholder="ideas, reading, work">
        </div>
    </div>

    @if (!isset($post))
        <div class="media-upload-panel">
            <div><strong>Photos and videos</strong><span>Add up to 12 photos or videos to this post.</span></div>
            <label class="media-picker" for="media"><span aria-hidden="true">＋</span> Choose media</label>
            <input class="sr-only" id="media" name="media[]" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm" multiple>
            <span class="field-hint">Images and videos up to 20 MB each.</span>
            @error('media')<span class="field-error">{{ $message }}</span>@enderror
            @error('media.*')<span class="field-error">{{ $message }}</span>@enderror
        </div>
    @else
        <div class="field">
            <label for="image">Replace cover image (optional)</label>
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
            <span class="field-hint">Existing posts keep their current cover image.</span>
            @error('image')<span class="field-error">{{ $message }}</span>@enderror
        </div>
    @endif

    <div class="form-actions">
        <button class="button button-dark" type="submit">{{ $submitLabel }}</button>
        <a class="button button-quiet" href="{{ isset($post) ? route('posts.show', $post) : route('posts.index') }}">Cancel</a>
    </div>
</form>