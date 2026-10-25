<?php

namespace App\Http\Controllers;

use App\Models\Scope;
use Illuminate\Http\Request;

class ScopeController extends Controller
{
    public function index(Request $request)
    {
        $inScopes = Scope::where('scope_status', 'in_scope')
            ->where('is_active', true)
            ->get();

        $outScopes = Scope::where('scope_status', 'out_of_scope')
            ->where('is_active', true)
            ->get();

        return view('scope.index', compact('inScopes', 'outScopes'));
    }
}
