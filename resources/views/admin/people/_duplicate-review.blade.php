@if($review = session('duplicate_review'))
    <div class="admin-duplicate-review" role="alert">
        <strong>This phone number is already in the registry.</strong>
        <p>Review the matching records before continuing. A phone may be shared by family members.</p>
        <ul>
            @foreach($review['matches'] as $match)
                <li><strong>#{{ $match['id'] }} — {{ $match['name'] }}</strong>
                    @if($match['archived']) <span>(archived)</span>
                    @else <a href="{{ route('admin.people.show', $match['id']) }}" target="_blank" rel="noopener">Open record</a> @endif
                </li>
            @endforeach
        </ul>
        @if($review['count'] > count($review['matches'])) <p>{{ $review['count'] - count($review['matches']) }} more records also use this number.</p> @endif
        @if($review['nin_cleared']) <p>For privacy, please enter the NIN again before saving.</p> @endif
        <input type="hidden" name="duplicate_token" value="{{ $review['token'] }}">
        <label class="admin-duplicate-confirm"><input type="checkbox" name="confirm_duplicate" value="1" required> I reviewed the matching records and confirm this is a different person or an intentional shared number.</label>
    </div>
@endif
