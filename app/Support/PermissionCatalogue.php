<?php

namespace App\Support;

use Illuminate\Support\Collection;

class PermissionCatalogue
{
    private const DEFINITIONS = [
        'access-admin' => ['Workspace', 'Open the staff dashboard and administration area.'],
        'manage-people' => ['Legacy permissions', 'Retired module-wide key; select individual routes instead.'],
        'manage-distributions' => ['Legacy permissions', 'Retired module-wide key; select individual routes instead.'],
        'manage-inventory' => ['Legacy permissions', 'Retired module-wide key; select individual routes instead.'],
        'manage-staff' => ['Legacy permissions', 'Retired module-wide key; select individual routes instead.'],
        'manage-roles' => ['Legacy permissions', 'Retired module-wide key; select individual routes instead.'],
        'view-reports' => ['Reporting', 'Reserved for reporting; it does not grant additional access on its own yet.'],
    ];

    public static function group(Collection $permissions): Collection
    {
        return $permissions->groupBy(fn ($permission) => str_starts_with($permission->name, 'route-admin-') ? 'Individual routes' : (self::DEFINITIONS[$permission->name][0] ?? 'Custom permissions'));
    }

    public static function byModule(Collection $permissions): Collection
    {
        $prefixes = [
            'Children’s therapy' => ['route-admin-therapy-'],
            'People' => ['route-admin-people-'],
            'Assessments & consent' => ['route-admin-assessments-', 'route-admin-assessment-photos-', 'route-admin-media-consents-'],
            'Distributions' => ['route-admin-distributions-', 'route-admin-historical-events'],
            'Inventory' => ['route-admin-inventory-'],
            'Locations' => ['route-admin-districts-', 'route-admin-subcounties-', 'route-admin-location-options-', 'route-admin-historical-locations'],
            'Staff & registration' => ['route-admin-users-', 'route-admin-registration-requests-', 'route-admin-historical-team-'],
            'Roles & permissions' => ['route-admin-roles-', 'route-admin-permissions-'],
            'Historical records' => ['route-admin-historical-'],
            'Settings' => ['route-admin-settings-'],
        ];

        return $permissions->groupBy(function ($permission) use ($prefixes) {
            foreach ($prefixes as $module => $starts) {
                foreach ($starts as $prefix) if (str_starts_with($permission->name, $prefix)) return $module;
            }
            return self::DEFINITIONS[$permission->name][0] ?? (str_starts_with($permission->name, 'route-admin-') ? 'Other routes' : 'Custom permissions');
        })->sortKeys();
    }

    public static function description(string $name): string
    {
        if (isset(self::DEFINITIONS[$name])) return self::DEFINITIONS[$name][1];
        if ($name === 'route-admin-location-options-subcounties') return 'Lets staff load subcounty options; it is also available when an authorized form needs it.';
        if (! str_starts_with($name, 'route-admin-')) return 'A custom permission only grants access where the system explicitly checks it.';

        $description = match (true) {
            str_ends_with($name, '-create') => 'Allows opening the new-record form; saving requires a separate permission.',
            str_ends_with($name, '-store') => 'Allows saving a new record; opening the form requires a separate permission.',
            str_ends_with($name, '-edit') => 'Allows opening the edit form; saving changes requires a separate permission.',
            str_ends_with($name, '-update') && $name !== 'route-admin-roles-permissions-update' => 'Allows saving changes; opening the edit form requires a separate permission.',
            str_ends_with($name, '-destroy') => 'Allows removing records, subject to the system’s safeguards.',
            str_ends_with($name, '-export') => 'Allows downloading the available records.',
            default => '',
        };
        static $catalogue;
        $catalogue ??= RoutePermissions::catalogue();
        if (str_contains($catalogue[$name] ?? '', 'Super administrators only')) {
            $description = trim($description.' Super administrators only.');
        }
        return $description;
    }

    public static function label(string $name): string
    {
        $special = [
            'access-admin' => 'Access the staff workspace',
            'manage-people' => 'Manage people (legacy)', 'manage-distributions' => 'Manage distributions (legacy)',
            'manage-inventory' => 'Manage inventory (legacy)', 'manage-staff' => 'Manage staff (legacy)',
            'manage-roles' => 'Manage roles (legacy)', 'view-reports' => 'View reports (reserved)',
            'route-admin-assessment-photos-show' => 'View assessment photos',
            'route-admin-assessments-legacy-photo' => 'View historical assessment photos',
            'route-admin-assessments-consents-store' => 'Save photo consent',
            'route-admin-media-consents-download' => 'Download signed consent',
            'route-admin-location-options-subcounties' => 'Load subcounty choices',
            'route-admin-inventory-index' => 'View inventory',
            'route-admin-inventory-items-store' => 'Add inventory items',
            'route-admin-inventory-movements-store' => 'Record stock movements',
            'route-admin-inventory-movements-index' => 'View stock movement history',
            'route-admin-inventory-movements-export' => 'Export stock movement history',
            'route-admin-inventory-historical-index' => 'View historical stock references',
            'route-admin-inventory-historical-export' => 'Export historical stock references',
            'route-admin-roles-permissions-update' => 'Save role permissions',
            'route-admin-settings-intake-code-edit' => 'Open intake code settings',
            'route-admin-settings-intake-code-update' => 'Save intake code settings',
            'route-admin-historical-events' => 'View historical events',
            'route-admin-historical-locations' => 'View historical locations',
            'route-admin-historical-media' => 'View historical media references',
            'route-admin-historical-team-account' => 'Create an account for a historical team member',
            'route-admin-historical-team-link' => 'Link a historical team member to an account',
            'route-admin-historical-team-password' => 'Reset a historical team member’s password',
            'route-admin-registration-requests-approve' => 'Approve staff registration requests',
            'route-admin-registration-requests-reject' => 'Reject staff registration requests',
        ];
        if (isset($special[$name])) return $special[$name];

        $resources = [
            'route-admin-testimonies-' => ['testimony videos', 'testimony video'],
            'route-admin-inventory-items-' => ['inventory items', 'inventory item'],
            'route-admin-therapy-children-' => ['therapy children', 'therapy child'],
            'route-admin-therapy-visits-' => ['therapy visits', 'therapy visit'],
            'route-admin-therapy-documents-' => ['therapy documents', 'therapy document'],
            'route-admin-therapy-services-' => ['therapy services', 'therapy service'],
            'route-admin-therapy-options-' => ['therapy form options', 'therapy form option'],
            'route-admin-therapy-catalogues-' => ['therapy registration settings', 'therapy registration setting'],
            'route-admin-therapy-reports-' => ['therapy reports', 'therapy report'],
            'route-admin-registration-requests-' => ['staff registration requests', 'staff registration request'],
            'route-admin-historical-feedback-' => ['historical feedback', 'historical feedback'],
            'route-admin-historical-team-' => ['historical team members', 'historical team member'],
            'route-admin-assessments-' => ['assessments', 'assessment'],
            'route-admin-distributions-' => ['distribution events', 'distribution event'],
            'route-admin-districts-' => ['districts', 'district'],
            'route-admin-subcounties-' => ['subcounties', 'subcounty'],
            'route-admin-people-' => ['people', 'person'],
            'route-admin-users-' => ['staff accounts', 'staff account'],
            'route-admin-roles-' => ['roles', 'role'],
            'route-admin-permissions-' => ['permissions', 'permission'],
        ];
        foreach ($resources as $prefix => [$plural, $singular]) {
            if (! str_starts_with($name, $prefix)) continue;
            $action = substr($name, strlen($prefix));
            return match ($action) {
                'index' => 'View '.$plural.' list',
                'show' => 'View '.$singular.' details',
                'create' => 'Open new '.$singular.' form',
                'store' => 'Save new '.$singular,
                'edit' => 'Open '.$singular.' edit form',
                'update' => 'Save '.$singular.' changes',
                'destroy' => 'Delete '.$plural,
                'export' => 'Export '.$plural,
                'archive' => 'Archive '.$plural,
                'restore' => 'Restore '.$plural,
                default => ucfirst(str_replace('-', ' ', $action)).' — '.$plural,
            };
        }
        $friendly = str_starts_with($name, 'route-admin-') ? substr($name, strlen('route-admin-')) : $name;
        return ucfirst(str_replace(['-', '_'], ' ', $friendly));
    }

    public static function isBuiltIn(string $name): bool
    {
        return isset(self::DEFINITIONS[$name]) || str_starts_with($name, 'route-admin-');
    }

    public static function assignable(Collection $permissions): Collection
    {
        // Old module-wide keys are retained for provenance, but no longer grant admin routes.
        return $permissions->reject(fn ($permission) => in_array($permission->name, ['manage-staff', 'manage-roles', 'manage-people', 'manage-distributions', 'manage-inventory', 'view-reports'], true))->values();
    }
}
