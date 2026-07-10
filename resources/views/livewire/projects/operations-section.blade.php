<div class="space-y-6">
    <!-- Tab header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-slate-100 dark:border-slate-800/40 gap-4">
        <div>
            <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-white">Project Operational Support</h3>
            <p class="text-xs text-slate-400 mt-1">Manage marketing posts, publication calendars, and client reputation reviews.</p>
        </div>
        
        <!-- Sub-tabs selection -->
        @if(config('features.smm', true))
        <div class="flex items-center bg-slate-100 dark:bg-slate-950 p-1 rounded-xl border border-slate-200/40 dark:border-slate-800/50">
            <button type="button" 
                    wire:click="setSubTab('smm')" 
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all duration-150 focus:outline-none {{ $activeSubTab === 'smm' ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
                <i class="fa-solid fa-share-nodes mr-1.5"></i> SMM Tracker
            </button>
            <button type="button" 
                    wire:click="setSubTab('reviews')" 
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all duration-150 focus:outline-none {{ $activeSubTab === 'reviews' ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
                <i class="fa-solid fa-star mr-1.5"></i> Reviews Manager
            </button>
        </div>
        @endif
    </div>

    <!-- Flash message -->
    @if ($flashMessage)
        <div class="p-3.5 rounded-xl border flex items-center justify-between shadow-sm text-xs font-semibold
            {{ $flashType === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-455' : 'bg-rose-50 dark:bg-rose-950/20 border-rose-100 dark:border-rose-800/40 text-rose-800 dark:text-rose-455' }}">
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="dismissFlash" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-350">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if (config('features.smm', true) && $activeSubTab === 'smm')
        <!-- SMM TRACKER SECTION -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Form to Add/Edit SMM Post (5 cols) -->
            <div class="lg:col-span-5 bg-slate-50 dark:bg-slate-950 border border-slate-200/50 dark:border-slate-800/80 rounded-2xl p-5 space-y-4">
                <h4 class="font-outfit font-bold text-sm text-slate-700 dark:text-slate-300">
                    {{ $editingPostId ? 'Edit SMM Post' : 'Schedule SMM Post' }}
                </h4>
                
                <form wire:submit.prevent="addPost" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Platform</label>
                        <select wire:model="smmPlatform" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                            <option value="LinkedIn">LinkedIn</option>
                            <option value="Facebook">Facebook</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Twitter/X">Twitter/X</option>
                            <option value="Telegram">Telegram</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Post Title</label>
                        <input type="text" wire:model="smmTitle" placeholder="e.g. Weekly Compliance Update" class="w-full px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-850 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                        @error('smmTitle') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Post Content / Notes</label>
                        <textarea wire:model="smmContent" rows="4" placeholder="Draft your content or internal scheduling notes..." class="w-full px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-850 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Link URL (Optional)</label>
                        <input type="text" wire:model="smmUrl" placeholder="https://linkedin.com/posts/..." class="w-full px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-850 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                        @error('smmUrl') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Status</label>
                            <select wire:model="smmStatus" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                                <option value="draft">Draft</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Published At</label>
                            <input type="datetime-local" wire:model="smmPublishedAt" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-850 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        @if($editingPostId)
                            <button type="button" wire:click="resetForms" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">Cancel</button>
                        @else
                            <span></span>
                        @endif
                        <button type="submit" class="px-4.5 py-2 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-xl text-xs transition-all shadow-sm">
                            {{ $editingPostId ? 'Save Changes' : 'Schedule Post' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- SMM List (7 cols) -->
            <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm space-y-4">
                <h4 class="font-outfit font-bold text-sm text-slate-700 dark:text-white">Publication Queue</h4>
                
                <div class="space-y-3.5 max-h-[500px] overflow-y-auto pr-1">
                    @forelse($posts as $post)
                        <div class="p-4 bg-slate-50/50 dark:bg-slate-950/20 border border-slate-200/40 dark:border-slate-800/50 rounded-xl relative group transition-all">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase tracking-wider
                                        @if($post->platform === 'LinkedIn') bg-blue-50 text-blue-750 dark:bg-blue-950/20 dark:text-blue-400
                                        @elseif($post->platform === 'Instagram') bg-pink-50 text-pink-700 dark:bg-pink-950/20 dark:text-pink-400
                                        @elseif($post->platform === 'Twitter/X') bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200
                                        @else bg-sky-50 text-sky-700 dark:bg-sky-950/20 dark:text-sky-400 @endif">
                                        <i class="fa-solid fa-share-nodes mr-1 text-[8px]"></i> {{ $post->platform }}
                                    </span>
                                    
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase tracking-wider
                                        @if($post->status === 'published') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400
                                        @elseif($post->status === 'scheduled') bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400
                                        @else bg-slate-100 text-slate-500 dark:bg-slate-850 dark:text-slate-400 @endif">
                                        {{ $post->status }}
                                    </span>
                                </div>

                                <div class="flex items-center space-x-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button type="button" wire:click="editPost({{ $post->id }})" class="p-1.5 text-slate-400 hover:text-amber-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-850 transition-all" title="Edit">
                                        <i class="fa-solid fa-pencil text-xs"></i>
                                    </button>
                                    <button type="button" wire:click="deletePost({{ $post->id }})" wire:confirm="Are you sure you want to delete this SMM post?" class="p-1.5 text-slate-400 hover:text-rose-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-850 transition-all" title="Delete">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <h5 class="font-semibold text-slate-800 dark:text-slate-250 text-xs mt-2">{{ $post->title }}</h5>
                            @if($post->content)
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-normal whitespace-pre-wrap">{{ $post->content }}</p>
                            @endif

                            <div class="flex items-center justify-between text-[9px] text-slate-400 mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-850/60">
                                <span>
                                    @if($post->status === 'published')
                                        Published At: {{ $post->published_at ? $post->published_at->format('d M Y H:i') : '-' }}
                                    @elseif($post->published_at)
                                        Scheduled: {{ $post->published_at->format('d M Y H:i') }}
                                    @else
                                        Draft Created
                                    @endif
                                </span>
                                
                                @if($post->url)
                                    <a href="{{ $post->url }}" target="_blank" class="font-semibold text-sky-600 hover:text-sky-750 dark:text-sky-400 hover:underline flex items-center space-x-1">
                                        <span>View live</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[7px]"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 border-2 border-dashed border-slate-100 dark:border-slate-805 rounded-2xl text-slate-405 dark:text-slate-600">
                            <i class="fa-regular fa-folder-open text-2xl mb-2.5 block text-slate-300 dark:text-slate-700"></i>
                            <p class="text-xs font-semibold">No SMM posts scheduled yet</p>
                            <p class="text-[10px] mt-0.5">Use the scheduler to queue social media publications.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        <!-- REVIEWS MANAGER SECTION -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Form to Add/Edit Review (5 cols) -->
            <div class="lg:col-span-5 bg-slate-50 dark:bg-slate-950 border border-slate-200/50 dark:border-slate-800/80 rounded-2xl p-5 space-y-4">
                <h4 class="font-outfit font-bold text-sm text-slate-700 dark:text-slate-300">
                    {{ $editingReviewId ? 'Edit Review' : 'Register Customer Review' }}
                </h4>
                
                <form wire:submit.prevent="addReview" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Platform</label>
                        <select wire:model="reviewPlatform" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                            <option value="Trustpilot">Trustpilot</option>
                            <option value="Google Reviews">Google Reviews</option>
                            <option value="Sitejabber">Sitejabber</option>
                            <option value="Direct Feedback">Direct Feedback</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Reviewer Name</label>
                        <input type="text" wire:model="reviewerName" placeholder="e.g. Alice Smith" class="w-full px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-850 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                        @error('reviewerName') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Rating (1-5 Stars)</label>
                        <select wire:model="reviewRating" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-805 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                            <option value="5">⭐⭐⭐⭐⭐ 5 Stars</option>
                            <option value="4">⭐⭐⭐⭐ 4 Stars</option>
                            <option value="3">⭐⭐⭐ 3 Stars</option>
                            <option value="2">⭐⭐ 2 Stars</option>
                            <option value="1">⭐ 1 Star</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Review content / text</label>
                        <textarea wire:model="reviewContent" rows="4" placeholder="Type the review content here..." class="w-full px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-850 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Review URL (Optional)</label>
                        <input type="text" wire:model="reviewUrl" placeholder="https://trustpilot.com/reviews/..." class="w-full px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-850 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                        @error('reviewUrl') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Response / Status</label>
                        <select wire:model="reviewStatus" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                            <option value="pending">Pending Response</option>
                            <option value="replied">Replied to Customer</option>
                            <option value="flagged">Flagged / Reported</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        @if($editingReviewId)
                            <button type="button" wire:click="resetForms" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">Cancel</button>
                        @else
                            <span></span>
                        @endif
                        <button type="submit" class="px-4.5 py-2 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-xl text-xs transition-all shadow-sm">
                            {{ $editingReviewId ? 'Save Changes' : 'Register Review' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Reviews List (7 cols) -->
            <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm space-y-4">
                <h4 class="font-outfit font-bold text-sm text-slate-700 dark:text-white">Customer Feedback History</h4>
                
                <div class="space-y-3.5 max-h-[500px] overflow-y-auto pr-1">
                    @forelse($reviews as $rev)
                        <div class="p-4 bg-slate-50/50 dark:bg-slate-950/20 border border-slate-200/40 dark:border-slate-800/50 rounded-xl relative group transition-all">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs font-bold text-slate-805 dark:text-slate-200">{{ $rev->reviewer_name }}</span>
                                        <span class="text-[9px] text-slate-400">{{ $rev->platform }}</span>
                                    </div>
                                    <div class="text-amber-500 font-bold text-[10px] mt-0.5">
                                        {{ str_repeat('⭐', $rev->rating) }}
                                    </div>
                                </div>

                                <div class="flex items-center space-x-0.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[8px] font-bold uppercase tracking-wider mr-2
                                        @if($rev->status === 'replied') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400
                                        @elseif($rev->status === 'flagged') bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400
                                        @else bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 @endif">
                                        {{ $rev->status }}
                                    </span>

                                    <div class="flex items-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button type="button" wire:click="editReview({{ $rev->id }})" class="p-1.5 text-slate-400 hover:text-amber-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-850 transition-all" title="Edit">
                                            <i class="fa-solid fa-pencil text-xs"></i>
                                        </button>
                                        <button type="button" wire:click="deleteReview({{ $rev->id }})" wire:confirm="Are you sure you want to delete this review?" class="p-1.5 text-slate-400 hover:text-rose-500 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-850 transition-all" title="Delete">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            @if($rev->content)
                                <p class="text-xs text-slate-655 dark:text-slate-350 mt-2 leading-relaxed italic">"{{ $rev->content }}"</p>
                            @endif

                            <div class="flex items-center justify-between text-[9px] text-slate-400 mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-850/60">
                                <span>Registered: {{ $rev->created_at->format('d M Y H:i') }}</span>
                                
                                @if($rev->url)
                                    <a href="{{ $rev->url }}" target="_blank" class="font-semibold text-sky-600 hover:text-sky-750 dark:text-sky-400 hover:underline flex items-center space-x-1">
                                        <span>View Review</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[7px]"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 border-2 border-dashed border-slate-100 dark:border-slate-805 rounded-2xl text-slate-405 dark:text-slate-600">
                            <i class="fa-regular fa-star text-2xl mb-2.5 block text-slate-300 dark:text-slate-700"></i>
                            <p class="text-xs font-semibold">No reviews registered yet</p>
                            <p class="text-[10px] mt-0.5">Customer reviews from Trustpilot or Google will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
