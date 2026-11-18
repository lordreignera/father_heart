<section class="admin-form-card admin-form-section guide-illustrations"><h2>Visual walkthrough</h2><p>These simplified examples illustrate where to find common actions. They do not contain real beneficiary or staff records.</p>
<div class="guide-illustration-grid">
@foreach([
    ['Sign in', 'Email address', 'Password', 'Sign in'],
    ['Request staff access', 'Full name', 'Email and requested role', 'Submit request'],
    ['Dashboard', 'People registered', 'Assessments and stock', 'Choose a module'],
    ['People registry', 'Search by name or number', 'Example person', 'Register person'],
    ['Distribution events', 'Event, date and location', 'View · Edit · Delete', 'Plan event'],
    ['Inventory', 'Item, unit and available stock', 'View · Edit · Delete', 'Add item'],
] as [$title, $line1, $line2, $action])
<figure><svg viewBox="0 0 600 260" role="img" aria-label="Illustration of {{ $title }}"><rect width="600" height="260" rx="12" fill="#faf5ef"/><rect x="0" y="0" width="600" height="32" rx="12" fill="#39251c"/><circle cx="20" cy="16" r="5" fill="#f37b28"/><circle cx="38" cy="16" r="5" fill="#d7baaa"/><text x="55" y="21" fill="#fff" font-family="Arial,sans-serif" font-size="12">Father's Heart workspace</text><rect x="18" y="49" width="104" height="190" rx="7" fill="#4a3024"/><text x="31" y="72" fill="#fff" font-family="Arial,sans-serif" font-size="12">MENU</text><rect x="30" y="86" width="80" height="8" rx="4" fill="#d5ad96"/><rect x="30" y="109" width="70" height="8" rx="4" fill="#d5ad96"/><rect x="30" y="132" width="76" height="8" rx="4" fill="#d5ad96"/><rect x="30" y="155" width="62" height="8" rx="4" fill="#d5ad96"/><text x="142" y="77" fill="#39251c" font-family="Arial,sans-serif" font-size="19" font-weight="bold">{{ $title }}</text><rect x="142" y="93" width="435" height="45" rx="6" fill="#fff" stroke="#e7d5c9"/><text x="157" y="120" fill="#70584b" font-family="Arial,sans-serif" font-size="13">{{ $line1 }}</text><rect x="142" y="148" width="435" height="45" rx="6" fill="#fff" stroke="#e7d5c9"/><text x="157" y="175" fill="#70584b" font-family="Arial,sans-serif" font-size="13">{{ $line2 }}</text><rect x="421" y="207" width="156" height="32" rx="6" fill="#ec7425"/><text x="499" y="228" fill="#fff" font-family="Arial,sans-serif" text-anchor="middle" font-size="12" font-weight="bold">{{ $action }}</text></svg><figcaption>{{ $title }} — illustrative example</figcaption></figure>
@endforeach
</div></section>
