<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizationController extends Controller
{
    public function apiIndex()
    {
        return response()->json(['data' => Organization::orderBy('name')->get(['id', 'name'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:160', 'unique:organizations,name'],
        ]);
        Organization::create(['name' => $data['organization_name']]);

        return to_route('accounts.index')->with('success', 'Organization choice added.');
    }

    public function destroy(Organization $organization)
    {
        DB::transaction(function () use ($organization) {
            User::where('organization', $organization->name)->update(['organization' => null]);
            $organization->delete();
        });

        return to_route('accounts.index')->with('success', 'Organization choice removed.');
    }
}
