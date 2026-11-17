# Individual administrative route permissions

Administrative routes require `access-admin` and their individual `route-admin-...` permission. Route permissions are listed with their HTTP method and URL in Roles & permissions. A form route (GET) and its save route (POST or PUT) are separate permissions; grant both when staff need both. The dashboard and public or own-profile routes retain their separate access rules.

The previous `manage-people`, `manage-distributions`, `manage-inventory`, `manage-staff`, and `manage-roles` keys are retained only as legacy data and do not grant administrative routes. Existing non-super roles or direct assignments in other installations must be reviewed and assigned the desired new route permissions before rollout. The two built-in super-administrator roles retain full access. Certain historical-team, historical-feedback, and permission-catalogue operations still require a super administrator, and record-level checks also remain in force.

Historical reference categories have separate route permissions even though their URL is handled by a shared route. New admin routes fail closed unless a corresponding permission is installed; add a migration for new route keys and update tests when routes change.

The subcounty lookup is a limited exception: access to the assessment or distribution form automatically permits its dependent district-to-subcounty lookup, so the form can function. The lookup can also be granted explicitly.

CSV download routes have separate permissions. Browser-side current-page CSV controls are restricted to super administrators; other staff can use a separately authorized export route where one exists. Staff who can view data can still copy information visible on screen, so export permissions are not a data-loss-prevention control.
