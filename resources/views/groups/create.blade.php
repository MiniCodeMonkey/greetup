<x-layouts.app title="Create a Group" description="Start a new community group.">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-2xl font-medium text-neutral-900">Create a Group</h1>
        <p class="mt-1 text-sm text-neutral-500">Start a community around a shared interest.</p>

        @if (session('status'))
            <div class="mt-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('groups.store') }}" enctype="multipart/form-data" class="mt-8 space-y-6">
            @csrf

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-neutral-700">Group Name <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                    placeholder="e.g. Copenhagen Laravel Meetup"
                />
                @error('name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-neutral-700">Description</label>
                <p class="mt-0.5 text-xs text-neutral-400">Supports Markdown formatting.</p>
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    maxlength="10000"
                    placeholder="Describe what your group is about..."
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none font-mono"
                >{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Location --}}
            <div>
                <label for="location" class="block text-sm font-medium text-neutral-700">Location</label>
                <input
                    type="text"
                    id="location"
                    name="location"
                    value="{{ old('location') }}"
                    placeholder="e.g. Copenhagen, Denmark"
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                />
                <p class="mt-1 text-xs text-neutral-400">Your location will be geocoded in the background after saving.</p>
                @error('location')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Cover Photo --}}
            <div>
                <label for="cover_photo" class="block text-sm font-medium text-neutral-700">Cover Photo</label>
                <input
                    type="file"
                    id="cover_photo"
                    name="cover_photo"
                    accept="image/jpeg,image/png,image/webp"
                    class="mt-1 block w-full text-sm text-neutral-500 file:mr-4 file:rounded-md file:border-0 file:bg-green-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-green-700 hover:file:bg-green-100"
                />
                <p class="mt-1 text-xs text-neutral-400">JPEG, PNG, or WebP. Max 10MB.</p>
                @error('cover_photo')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Topics/Interests --}}
            <div>
                <label class="block text-sm font-medium text-neutral-700">Topics / Interests</label>
                <p class="mt-0.5 text-xs text-neutral-400">Add comma-separated topics for your group.</p>
                <input
                    type="text"
                    id="topics_input"
                    placeholder="e.g. Laravel, PHP, Web Development"
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                />
                <div id="topics_container" class="mt-2 flex flex-wrap gap-2"></div>
                @error('topics')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
                @error('topics.*')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Visibility --}}
            <div>
                <label class="block text-sm font-medium text-neutral-700">Visibility <span class="text-red-500">*</span></label>
                <div class="mt-2 space-y-2">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="visibility" value="public" {{ old('visibility', 'public') === 'public' ? 'checked' : '' }} class="text-green-500 focus:ring-green-500" />
                        <span class="text-sm text-neutral-700">Public — Anyone can find and view this group</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="visibility" value="private" {{ old('visibility') === 'private' ? 'checked' : '' }} class="text-green-500 focus:ring-green-500" />
                        <span class="text-sm text-neutral-700">Private — Only members can see group content</span>
                    </label>
                </div>
                @error('visibility')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Requires Approval --}}
            <div>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="requires_approval" value="0" />
                    <input type="checkbox" name="requires_approval" value="1" {{ old('requires_approval') ? 'checked' : '' }} class="rounded border-neutral-200 text-green-500 focus:ring-green-500" />
                    <span class="text-sm text-neutral-700">Require approval for new members</span>
                </label>
                @error('requires_approval')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Max Members --}}
            <div>
                <label for="max_members" class="block text-sm font-medium text-neutral-700">Maximum Members</label>
                <input
                    type="number"
                    id="max_members"
                    name="max_members"
                    value="{{ old('max_members') }}"
                    min="2"
                    max="100000"
                    placeholder="Leave blank for unlimited"
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                />
                @error('max_members')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Welcome Message --}}
            <div>
                <label for="welcome_message" class="block text-sm font-medium text-neutral-700">Welcome Message</label>
                <textarea
                    id="welcome_message"
                    name="welcome_message"
                    rows="3"
                    maxlength="5000"
                    placeholder="Message shown to new members when they join..."
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                >{{ old('welcome_message') }}</textarea>
                @error('welcome_message')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Membership Questions --}}
            <div>
                <label class="block text-sm font-medium text-neutral-700">Membership Questions</label>
                <p class="mt-0.5 text-xs text-neutral-400">Ask new members up to 10 questions when they request to join.</p>

                <div id="questions_container" class="mt-3 space-y-3"></div>

                <button
                    type="button"
                    id="add_question_btn"
                    class="mt-3 inline-flex items-center gap-1 rounded-md border border-neutral-200 px-3 py-1.5 text-sm text-neutral-700 hover:bg-neutral-50"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Question
                </button>

                @error('membership_questions')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <div class="flex items-center gap-4 border-t border-neutral-100 pt-6">
                <button type="submit" class="rounded-md bg-green-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none">
                    Create Group
                </button>
                <a href="{{ url('/') }}" class="text-sm text-neutral-500 hover:text-neutral-700">Cancel</a>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Topics management
            const topicsInput = document.getElementById('topics_input');
            const topicsContainer = document.getElementById('topics_container');
            let topics = @json(old('topics', []));

            function renderTopics() {
                topicsContainer.innerHTML = '';
                topics.forEach(function (topic, index) {
                    const tag = document.createElement('span');
                    tag.className = 'inline-flex items-center gap-1 rounded-pill bg-green-50 border border-green-200 px-3 py-1 text-sm text-green-700';
                    tag.innerHTML = '<input type="hidden" name="topics[]" value="' + topic.replace(/"/g, '&quot;') + '" />' +
                        topic +
                        '<button type="button" data-index="' + index + '" class="ml-1 text-green-400 hover:text-green-700">&times;</button>';
                    topicsContainer.appendChild(tag);
                });
            }

            topicsInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const value = this.value.trim().replace(/,/g, '');
                    if (value && !topics.includes(value)) {
                        topics.push(value);
                        renderTopics();
                    }
                    this.value = '';
                }
            });

            topicsContainer.addEventListener('click', function (e) {
                if (e.target.tagName === 'BUTTON') {
                    topics.splice(parseInt(e.target.dataset.index), 1);
                    renderTopics();
                }
            });

            renderTopics();

            // Membership questions management
            const questionsContainer = document.getElementById('questions_container');
            const addQuestionBtn = document.getElementById('add_question_btn');
            let questionCount = 0;

            function addQuestion(question, isRequired) {
                if (questionCount >= 10) return;
                const index = questionCount;
                questionCount++;

                const div = document.createElement('div');
                div.className = 'flex items-start gap-3 rounded-md border border-neutral-200 p-3';
                div.dataset.index = index;
                div.innerHTML =
                    '<div class="flex-1 space-y-2">' +
                        '<input type="text" name="membership_questions[' + index + '][question]" value="' + (question || '').replace(/"/g, '&quot;') + '" placeholder="Enter your question..." maxlength="500" class="block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none" />' +
                        '<label class="flex items-center gap-2">' +
                            '<input type="hidden" name="membership_questions[' + index + '][is_required]" value="0" />' +
                            '<input type="checkbox" name="membership_questions[' + index + '][is_required]" value="1" ' + (isRequired ? 'checked' : '') + ' class="rounded border-neutral-200 text-green-500 focus:ring-green-500" />' +
                            '<span class="text-xs text-neutral-500">Required</span>' +
                        '</label>' +
                    '</div>' +
                    '<button type="button" class="remove-question mt-1 text-neutral-400 hover:text-red-500">' +
                        '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' +
                    '</button>';
                questionsContainer.appendChild(div);

                if (questionCount >= 10) {
                    addQuestionBtn.classList.add('hidden');
                }
            }

            addQuestionBtn.addEventListener('click', function () {
                addQuestion('', true);
            });

            questionsContainer.addEventListener('click', function (e) {
                const btn = e.target.closest('.remove-question');
                if (btn) {
                    btn.closest('[data-index]').remove();
                    questionCount--;
                    if (questionCount < 10) {
                        addQuestionBtn.classList.remove('hidden');
                    }
                    // Re-index remaining questions
                    questionsContainer.querySelectorAll('[data-index]').forEach(function (div, i) {
                        div.dataset.index = i;
                        div.querySelectorAll('[name]').forEach(function (input) {
                            input.name = input.name.replace(/\[\d+\]/, '[' + i + ']');
                        });
                    });
                }
            });

            // Restore old membership questions
            @if(old('membership_questions'))
                @foreach(old('membership_questions') as $q)
                    addQuestion(@json($q['question'] ?? ''), {{ !empty($q['is_required']) ? 'true' : 'false' }});
                @endforeach
            @endif
        });
    </script>
    @endpush
</x-layouts.app>
