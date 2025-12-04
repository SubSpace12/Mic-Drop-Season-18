<!DOCTYPE html>
@vite(['resources/css/submit-judge-app.css'])
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <x-app-layout>
    @php
        // Check if the logged-in user has an existing application
        $existingApp = DB::table('apps')
            ->where('user_id', auth()->id())
            ->first();
        
        $isEditing = !is_null($existingApp);
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
                                rows="4" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>{{ old('fav_artists', $existingApp->fav_artists ?? '') }}</textarea>
                        </div>

                        <!-- Question 2 -->
                        <div class="mb-6">
                            <label for="least_fav_artists" class="block text-sm font-medium text-gray-700 mb-2">
                                2. Do you have any least favourite artists? This question is optional, write N/A if none.
                            </label>
                            <textarea 
                                id="least_fav_artists" 
                                name="least_fav_artists" 
                                rows="3" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('least_fav_artists', $existingApp->least_fav_artists ?? '') }}</textarea>
                        </div>

                        <!-- Question 3 -->
                        <div class="mb-6">
                            <label for="fav_genres" class="block text-sm font-medium text-gray-700 mb-2">
                                3. What are some of your favourite genres? Be as specific as possible.
                            </label>
                            <textarea 
                                id="fav_genres" 
                                name="fav_genres" 
                                rows="4" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                                rows="3" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                                rows="5" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                                rows="4" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>{{ old('safe_pick_criteria', $existingApp->safe_pick_criteria ?? '') }}</textarea>
                        </div>

                        <!-- Question 7 -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                7. Will you give a 0.5 bonus to songs you haven't heard before?
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

                        <!-- Question 8 -->
                        <div class="mb-6">
                            <label for="banned_artists" class="block text-sm font-medium text-gray-700 mb-2">
                                8. Provide up to 6 artists you want to ban contestants from submitting. Write N/A if you want to ban none.
                            </label>
                            <textarea 
                                id="banned_artists" 
                                name="banned_artists" 
                                rows="3" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>{{ old('banned_artists', $existingApp->banned_artists ?? '') }}</textarea>
                        </div>

                        <!-- Question 9 -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                9. Would you prefer to judge more or less submissions in a round?
                            </label>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center">
                                    <input 
                                        type="radio" 
                                        name="longer" 
                                        value="1" 
                                        class="form-radio text-blue-600"
                                        {{ old('longer', $existingApp->longer ?? null) == '1' || old('longer', $existingApp->longer ?? null) === true ? 'checked' : '' }}
                                        required>
                                    <span class="ml-2">More</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input 
                                        type="radio" 
                                        name="longer" 
                                        value="0" 
                                        class="form-radio text-blue-600"
                                        {{ old('longer', $existingApp->longer ?? null) == '0' || old('longer', $existingApp->longer ?? null) === false ? 'checked' : '' }}
                                        required>
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
</x-app-layout>
</body>
</html>