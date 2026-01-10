<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;

class AdminController extends Controller
{
    public function index()
    {
        $authors = Admin::where('role', 'author')
            ->select(
                'name',
                'email',
                'avatar',
                'bio',
                'website',
                'social_facebook',
                'social_twitter',
                'social_instagram',
                'social_linkedin'
            )
            ->paginate(20);
        $admins = Admin::where('role', 'superadmin')
            ->select(
                'name',
                'email',
                'avatar',
                'bio',
                'website',
                'social_facebook',
                'social_twitter',
                'social_instagram',
                'social_linkedin'
            )
            ->paginate(20);
        return response()->json(['authors' => $authors, 'admins' => $admins], 200);
    }
}
