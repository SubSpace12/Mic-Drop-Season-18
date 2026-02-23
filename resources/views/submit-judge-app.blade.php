<!DOCTYPE html>
@vite(['resources/css/submit-judge-app.css'])
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>
<body>
    <x-app-layout>
           @guest
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200 text-center">
                        <h2 class="text-2xl font-bold mb-4">Login Required</h2>
                        <p class="text-gray-600 mb-6">You must be logged in to submit a judge application.</p>
                    </div>
                </div>
            </div>
        </div>
    @endguest
    @auth
    @php
        // Check if the logged-in user has an existing application
        $existingApp = DB::table('apps')
            ->where('user_id', auth()->id())
            ->first();
        
        $isEditing = !is_null($existingApp);
        
        // Parse extra_streaming for "Other" case
        $extraStreaming = old('extra_streaming', $existingApp->extra_streaming ?? '');
        $extraStreamingOther = '';
        $knownServices = ['Spotify', 'Apple Music', 'Tidal', 'Deezer', 'Qobuz', 'None'];
        if ($extraStreaming && !in_array($extraStreaming, $knownServices)) {
            $extraStreamingOther = $extraStreaming;
            $extraStreaming = 'Other';
        }
    @endphp

        

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if($isEditing)
                        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                            <strong>Editing Mode:</strong> You have already submitted a judge application. You can update it below.
                        </div>
                    @endif

                    <h2 class="text-2xl font-bold mb-6">
                        {{ $isEditing ? 'Edit Judge Application' : 'Judge Application' }}
                    </h2>

                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('apps.store') }}" method="POST">
                        @csrf
                        @if($isEditing)
                            <input type="hidden" name="is_editing" value="1">
                        @endif

                        <!-- Question 1 -->
                        <div class="mb-6">
                            <label for="fav_artists" class="block text-sm font-medium text-gray-700 mb-2">
                                1. Who are some of your favourite artists? Provide at least 5.
                            </label>
                            <textarea 
                                id="fav_artists" 
                                name="fav_artists" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 auto-resize"
                                required>{{ old('fav_artists', $existingApp->fav_artists ?? '') }}</textarea>
                        </div>

                        <!-- Question 2 -->
                        <div class="mb-6">
                            <label for="least_fav_artists" class="block text-sm font-medium text-gray-700 mb-2">
                                2. Do you have any least favourite artists, and/or artists that won't score well with you? Try to include at least three.
                            </label>
                            <textarea 
                                id="least_fav_artists" 
                                name="least_fav_artists" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 auto-resize">{{ old('least_fav_artists', $existingApp->least_fav_artists ?? '') }}</textarea>
                        </div>

                        <!-- Question 3 -->
                        <div class="mb-6">
                            <label for="fav_genres" class="block text-sm font-medium text-gray-700 mb-2">
                                3. What are some of your favourite genres? Be as specific as possible.
                            </label>
                            <textarea 
                                id="fav_genres" 
                                name="fav_genres" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 auto-resize"
                                required>{{ old('fav_genres', $existingApp->fav_genres ?? '') }}</textarea>
                        </div>

                        <!-- Question 4 -->
                        <div class="mb-6">
                            <label for="least_fav_genres" class="block text-sm font-medium text-gray-700 mb-2">
                                4. Do you have any least favourite genres? If not, explain why.
                            </label>
                            <textarea 
                                id="least_fav_genres" 
                                name="least_fav_genres" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 auto-resize"
                                required>{{ old('least_fav_genres', $existingApp->least_fav_genres ?? '') }}</textarea>
                        </div>

                        <!-- Question 5 -->
                        <div class="mb-6">
                            <label for="judging_style" class="block text-sm font-medium text-gray-700 mb-2">
                                5. Is there anything else you would like to mention about your judging style? An in-depth explanation about what you look for in songs, the vibe you usually enjoy, etc. will be appreciated.
                            </label>
                            <textarea 
                                id="judging_style" 
                                name="judging_style" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 auto-resize"
                                required>{{ old('judging_style', $existingApp->judging_style ?? '') }}</textarea>
                        </div>

                        <!-- Question 6 -->
                        <div class="mb-6">
                            <label for="safe_pick_criteria" class="block text-sm font-medium text-gray-700 mb-2">
                                6. Describe what you would consider a safe pick. You may include any helpful links here.
                            </label>
                            <textarea 
                                id="safe_pick_criteria" 
                                name="safe_pick_criteria" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 auto-resize"
                                required>{{ old('safe_pick_criteria', $existingApp->safe_pick_criteria ?? '') }}</textarea>
                        </div>

                        <!-- Question 7 (Extra Streaming) -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                7. Aside from browser-based music platforms, is there a streaming service you'd be able to receive submissions on?
                            </label>
                            <div class="flex flex-wrap gap-4">
                                @php
                                    $streamingOptions = ['Spotify', 'Apple Music', 'Tidal', 'Deezer', 'Qobuz', 'None'];
                                @endphp
                                @foreach($streamingOptions as $option)
                                    <label class="inline-flex items-center">
                                        <input 
                                            type="radio" 
                                            name="extra_streaming" 
                                            value="{{ $option }}" 
                                            class="form-radio text-blue-600 uncheckable-radio"
                                            data-group="extra_streaming"
                                            {{ $extraStreaming === $option ? 'checked' : '' }}
                                            required>
                                        <span class="ml-2">{{ $option }}</span>
                                    </label>
                                @endforeach
                                <label class="inline-flex items-center">
                                    <input 
                                        type="radio" 
                                        name="extra_streaming" 
                                        value="Other" 
                                        class="form-radio text-blue-600 uncheckable-radio"
                                        data-group="extra_streaming"
                                        id="extra_streaming_other_radio"
                                        {{ $extraStreaming === 'Other' ? 'checked' : '' }}
                                        required>
                                    <span class="ml-2">Other (specify)</span>
                                </label>
                            </div>
                            <div id="extra_streaming_other_container" class="mt-3 {{ $extraStreaming === 'Other' ? '' : 'hidden' }}">
                                <input 
                                    type="text" 
                                    name="extra_streaming_other" 
                                    id="extra_streaming_other_input"
                                    placeholder="Please specify your streaming service"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    value="{{ $extraStreamingOther }}">
                            </div>
                        </div>

                        <!-- Question 8 -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                8. Will you give a 0.5 bonus to songs you haven't heard before?
                            </label>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center">
                                    <input 
                                        type="radio" 
                                        name="bonus" 
                                        value="1" 
                                        class="form-radio text-blue-600"
                                        {{ old('bonus', $existingApp->bonus ?? null) == '1' || old('bonus', $existingApp->bonus ?? null) === true ? 'checked' : '' }}
                                        required>
                                    <span class="ml-2">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input 
                                        type="radio" 
                                        name="bonus" 
                                        value="0" 
                                        class="form-radio text-blue-600"
                                        {{ old('bonus', $existingApp->bonus ?? null) == '0' || old('bonus', $existingApp->bonus ?? null) === false ? 'checked' : '' }}
                                        required>
                                    <span class="ml-2">No</span>
                                </label>
                            </div>
                        </div>

                        <!-- Question 9 -->
                        <div class="mb-6">
                            <label for="banned_artists" class="block text-sm font-medium text-gray-700 mb-2">
                                9. Provide up to 6 artists you want to ban contestants from submitting. Write N/A if you want to ban none.
                            </label>
                            <textarea 
                                id="banned_artists" 
                                name="banned_artists" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 auto-resize"
                                required>{{ old('banned_artists', $existingApp->banned_artists ?? '') }}</textarea>
                        </div>

                        <!-- Question 10 (Optional, uncheckable) -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                10. Would you prefer to judge more or less submissions in a round? If no preference, leave unchecked. </span>
                            </label>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center">
                                    <input 
                                        type="radio" 
                                        name="longer" 
                                        value="1" 
                                        class="form-radio text-blue-600 uncheckable-radio"
                                        data-group="longer"
                                        {{ old('longer', $existingApp->longer ?? null) == '1' || old('longer', $existingApp->longer ?? null) === true ? 'checked' : '' }}>
                                    <span class="ml-2">More</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input 
                                        type="radio" 
                                        name="longer" 
                                        value="0" 
                                        class="form-radio text-blue-600 uncheckable-radio"
                                        data-group="longer"
                                        {{ old('longer', $existingApp->longer ?? null) == '0' || old('longer', $existingApp->longer ?? null) === false ? 'checked' : '' }}>
                                    <span class="ml-2">Less</span>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end mt-6 mb-8">
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                {{ $isEditing ? 'Update Application' : 'Submit Application' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-resize textarea function
        function autoResize(textarea) {
            // Reset height to auto to get the correct scrollHeight
            textarea.style.height = 'auto';
            // Set the height to the scrollHeight
            textarea.style.height = textarea.scrollHeight + 'px';
        }

        // Initialize auto-resize for all textareas
        document.addEventListener('DOMContentLoaded', function() {
            const textareas = document.querySelectorAll('textarea.auto-resize');
            
            textareas.forEach(function(textarea) {
                // Auto-resize on input
                textarea.addEventListener('input', function() {
                    autoResize(this);
                });

                // Initial resize for pre-filled content (editing mode)
                autoResize(textarea);
            });

            // Uncheckable radio buttons functionality
            const uncheckableRadios = document.querySelectorAll('.uncheckable-radio');
            uncheckableRadios.forEach(function(radio) {
                radio.addEventListener('click', function(e) {
                    const group = this.dataset.group;
                    if (this.dataset.wasChecked === 'true') {
                        this.checked = false;
                        this.dataset.wasChecked = 'false';
                    } else {
                        // Only reset wasChecked for radios in the same group
                        document.querySelectorAll(`.uncheckable-radio[data-group="${group}"]`).forEach(r => r.dataset.wasChecked = 'false');
                        this.dataset.wasChecked = 'true';
                    }
                    // Toggle the "Other" input visibility
                    toggleOtherInput();
                });
                // Initialize wasChecked state
                radio.dataset.wasChecked = radio.checked ? 'true' : 'false';
            });

            // Show/hide "Other" input based on selection
            const otherRadio = document.getElementById('extra_streaming_other_radio');
            const otherContainer = document.getElementById('extra_streaming_other_container');
            const otherInput = document.getElementById('extra_streaming_other_input');

            function toggleOtherInput() {
                if (otherRadio && otherRadio.checked) {
                    otherContainer.classList.remove('hidden');
                    otherInput.setAttribute('required', 'required');
                } else if (otherContainer) {
                    otherContainer.classList.add('hidden');
                    otherInput.removeAttribute('required');
                }
            }

            // Listen for changes on all streaming radios
            document.querySelectorAll('input[name="extra_streaming"]').forEach(function(radio) {
                radio.addEventListener('change', toggleOtherInput);
            });

            // Initial toggle
            toggleOtherInput();
        });

        // Also resize on window resize (for responsive layouts)
        window.addEventListener('resize', function() {
            const textareas = document.querySelectorAll('textarea.auto-resize');
            textareas.forEach(function(textarea) {
                autoResize(textarea);
            });
        });
    </script>
    @endauth
</x-app-layout>
</body>
</html>